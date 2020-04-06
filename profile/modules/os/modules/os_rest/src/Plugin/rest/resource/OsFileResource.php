<?php

namespace Drupal\os_rest\Plugin\rest\resource;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\rest\resource\FileUploadResource;
use Drupal\media\Entity\Media;
use Psr\Log\LoggerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Config\Config;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

/**
 * File upload resource.
 *
 * This is implemented as a field-level resource for the following reasons:
 *   - Validation for uploaded files is tied to fields (allowed extensions, max
 *     size, etc..).
 *   - The actual files do not need to be stored in another temporary location,
 *     to be later moved when they are referenced from a file field.
 *   - Permission to upload a file can be determined by a users field level
 *     create access to the file field.
 *
 * @RestResource(
 *   id = "file:os:upload",
 *   label = @Translation("OpenScholar File Upload"),
 *   serialization_class = "Drupal\file\Entity\File",
 *   uri_paths = {
 *     "canonical" = "/api/file-upload/{entity}",
 *     "create" = "/api/file-upload"
 *   }
 * )
 */
class OsFileResource extends FileUploadResource {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Constructs a OsFileResource instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently authenticated user.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement instance.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock service.
   * @param \Drupal\Core\Config\Config $system_file_config
   *   The system file configuration.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   The vsite context manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, AccountInterface $current_user, MimeTypeGuesserInterface $mime_type_guesser, Token $token, LockBackendInterface $lock, Config $system_file_config, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $file_system, $entity_type_manager, $entity_field_manager, $current_user, $mime_type_guesser, $token, $lock, $system_file_config);
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('current_user'),
      $container->get('file.mime_type.guesser'),
      $container->get('token'),
      $container->get('lock'),
      $container->get('config.factory')->get('system.file'),
      $container->get('vsite.context_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function post(Request $request, $entity_type_id = '', $bundle = '', $field_name = '') {
    // Check if user has access to upload the file.
    $active_vsite = $this->vsiteContextManager->getActiveVsite();
    if (!$active_vsite || !$active_vsite->hasPermission('create group_entity:media entity', $this->currentUser)) {
      throw new AccessDeniedHttpException('User does not have permission to upload the file.');
    }

    $destination = $this->getUploadLocation();

    // Check the destination file path is writable.
    if (!file_prepare_directory($destination, FILE_CREATE_DIRECTORY)) {
      throw new HttpException(500, 'Destination file path is not writable');
    }

    $validators = $this->getUploadValidators();
    // List all extensions what are in all media types.
    /* @var \Drupal\media\MediaTypeInterface[] $media_types */
    $media_types = \Drupal::entityTypeManager()
      ->getStorage('media_type')
      ->loadMultiple();
    $extensions = [];
    foreach ($media_types as $type) {
      $sourceFieldDefinition = $type->getSource()
        ->getSourceFieldDefinition($type);
      if (is_null($sourceFieldDefinition)) {
        continue;
      }
      $file_extensions = $sourceFieldDefinition->getSetting('file_extensions');
      if (is_null($file_extensions)) {
        continue;
      }
      $extensions[$type->id()] = $file_extensions;
    }
    $validators['file_validate_extensions'][] = implode(' ', $extensions);

    // Save the uploaded file.
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file_raw */
    $file_raw = $request->files->get('file');

    if ($newName = $request->request->get('sanitized')) {
      // Make a new file that's the right name.
      $file_raw = new UploadedFile($file_raw->getPathname(), $newName, $file_raw->getMimeType(), $file_raw->getSize(), $file_raw->getError(), TRUE);
    }

    // Can't use file_save_upload() because it expects all files to be in the
    // files array in the files parameter of the request
    // $request->files->get('files'), which is weird and going to be empty when
    // coming from js.
    $file = _file_save_upload_single($file_raw, 'upload', $validators, $destination, FILE_EXISTS_REPLACE);

    if (!$file) {
      throw new HttpException(500, 'File could not be saved.');
    }

    $extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);

    /** @var \Drupal\file\FileUsage\FileUsageInterface $fileUsage */
    $fileUsage = \Drupal::service('file.usage');
    $usage = $fileUsage->listUsage($file);
    if (isset($usage['file']['media'])) {
      ksort($usage['file']['media']);
      /** @var \Drupal\media\MediaInterface $media */
      $file_usage = array_keys($usage['file']['media']);
      $media = \Drupal::entityTypeManager()->getStorage('media')->load(reset($file_usage));
    }
    else {

      // This next big figures out what type of Media bundle to create around
      // the file.
      /** @var \Drupal\media\MediaTypeInterface[] $mediaTypes */
      $mediaTypes = \Drupal::entityTypeManager()->getStorage('media_type')->loadMultiple();
      foreach ($mediaTypes as $mediaType) {
        $fieldDefinition = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
        if (is_null($fieldDefinition)) {
          continue;
        }
        $exts = explode(' ', $fieldDefinition->getSetting('file_extensions'));
        if (in_array($extension, $exts)) {
          $media = Media::create([
            'bundle' => $mediaType->id(),
            'uid' => \Drupal::currentUser()->id(),
            'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
            $fieldDefinition->getName() => [
              'target_id' => $file->id(),
            ],
          ]);
        }
      }
      if (!$media) {
        $file->delete();
        throw new HttpException(500, 'No Media Type accepts this kind of file.');
      }
    }
    $media->save();

    // 201 Created responses return the newly created entity in the response
    // body. These responses are not cacheable, so we add no cacheability
    // metadata here.
    return new ModifiedResourceResponse($media, 201);
  }

  /**
   * Replace an existing file on disk with the freshly uploaded file.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The file whose contents are being replaced.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response.
   */
  public function put(EntityInterface $entity) {

    // Check if user has access to upload the file.
    $active_vsite = $this->vsiteContextManager->getActiveVsite();
    if (!$active_vsite || !$active_vsite->hasPermission('create group_entity:media entity', $this->currentUser)) {
      throw new AccessDeniedHttpException('User does not have permission to upload the file.');
    }

    $temp_file_path = $this->streamUploadData();
    /** @var \Drupal\file\FileInterface $target */
    $target = $entity;

    if (file_unmanaged_copy($temp_file_path, $target->getFileUri(), FILE_EXISTS_REPLACE) === FALSE) {
      throw new HttpException(500, 'The file could not be replaced.');
    }

    $target->save();
    if (!file_validate_is_image($target)) {
      /** @var \Drupal\image\Entity\ImageStyle[] $imageStyles */
      $imageStyles = \Drupal::entityTypeManager()->getStorage('image_style')->loadMultiple();

      foreach ($imageStyles as $style) {
        $style->flush($target->getFileUri());
      }
    }

    file_unmanaged_delete($temp_file_path);

    return new ModifiedResourceResponse($target, 200);
  }

  /**
   * {@inheritdoc}
   */
  protected function getUploadLocation(array $settings = []) {
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContextManager */
    $vsiteContextManager = \Drupal::service('vsite.context_manager');
    if ($purl = $vsiteContextManager->getActivePurl()) {
      return 'public://' . $purl . '/files';
    }
    return 'public://global';
  }

  /**
   * Returns validators applicable for every field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   Not used. Only here for compatibility.
   *
   * @return array
   *   The validators
   */
  protected function getUploadValidators(FieldDefinitionInterface $field_definition = NULL) {
    $validators = [
      // Add in our check of the file name length.
      'file_validate_name_length' => [],
    ];

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toInt(file_upload_max_size());

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    // $validators['file_validate_extensions'] = [];.
    return $validators;
  }

  /**
   * Return validators applicable for replacing a single file.
   *
   * @param \Drupal\file\FileInterface $target
   *   The target file that is having its content replaced.
   *
   * @return array
   *   All validators applicable for this file.
   */
  protected function getReplacementValidators(FileInterface $target) {
    $validators = [];

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toInt(file_upload_max_size());

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    $uri = $target->getFileUri();
    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    $validators['file_validate_extensions'] = [$extension];

    return $validators;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    $route->setOption('parameters', ['entity' => ['type' => 'entity:file']]);
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRouteRequirements($method) {
    $reqs = parent::getBaseRouteRequirements($method);

    $reqs['_content_type_format'] = '*';

    return $reqs;
  }

}
