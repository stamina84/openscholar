<?php

namespace Drupal\cp_import\Helper;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\media\Entity\Media;
use Drupal\os_media\MediaEntityHelper;
use Drupal\pathauto\PathautoGenerator;
use Drupal\vsite\Plugin\VsiteContextManager;
use League\Csv\Reader;
use League\Csv\Writer;

/**
 * Class CpImportHelper.
 *
 * @package Drupal\cp_import\Helper
 */
class CpImportHelper extends CpImportHelperBase {

  /**
   * Csv row limit.
   */
  const CSV_ROW_LIMIT = 100;

  /**
   * Csv row limit string.
   */
  const OVER_LIMIT = 'rows_over_allowed_limit';

  /**
   * Media Helper service.
   *
   * @var \Drupal\os_media\MediaEntityHelper
   */
  protected $mediaHelper;

  /**
   * Current User service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * FileSystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Datetime Time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * PathAutoGenerator service.
   *
   * @var \Drupal\pathauto\PathautoGenerator
   */
  protected $pathAutoGenerator;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * CpImportHelper constructor.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManager $vsiteContextManager
   *   VsiteContextManager instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager instance.
   * @param \Drupal\os_media\MediaEntityHelper $mediaHelper
   *   MediaEntityHelper instance.
   * @param \Drupal\Core\Session\AccountProxy $user
   *   AccountProxy instance.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   LanguageManager instance.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   EntityFieldManager instance.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   FileSystem interface.
   * @param \Drupal\Component\Datetime\Time $time
   *   Time instance.
   * @param \Drupal\pathauto\PathautoGenerator $pathautoGenerator
   *   PathAutoGenerator instance.
   */
  public function __construct(VsiteContextManager $vsiteContextManager, EntityTypeManager $entityTypeManager, MediaEntityHelper $mediaHelper, AccountProxy $user, LanguageManager $languageManager, EntityFieldManager $entityFieldManager, FileSystemInterface $fileSystem, Time $time, PathautoGenerator $pathautoGenerator) {
    parent::__construct($vsiteContextManager, $entityTypeManager);
    $this->mediaHelper = $mediaHelper;
    $this->currentUser = $user;
    $this->languageManager = $languageManager;
    $this->fieldManager = $entityFieldManager;
    $this->fileSystem = $fileSystem;
    $this->time = $time;
    $this->pathAutoGenerator = $pathautoGenerator;
  }

  /**
   * Get the media to be attached to the node.
   *
   * @param string $media_val
   *   The media value entered in the csv.
   * @param string $contentType
   *   Content type.
   * @param string $field_name
   *   Media field name.
   *
   * @return \Drupal\media\Entity\Media|null
   *   Media entity/Null when not able to fetch/download media.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getMedia($media_val, $contentType, $field_name) : ?Media {
    $media = NULL;
    // Only load the bundles which are enabled for the content type's field.
    $bundle_fields = $this->fieldManager->getFieldDefinitions('node', $contentType);
    $field_definition = $bundle_fields[$field_name];
    $settings = $field_definition->getSettings();
    if (!empty($settings['handler_settings'])) {
      $bundles = $settings['handler_settings']['target_bundles'];
    }
    elseif ($settings['target_type'] === 'file') {
      $bundles = [
        'image' => 'image',
      ];
    }
    /** @var \Drupal\media\Entity\MediaType[] $mediaTypes */
    $mediaTypes = $this->entityTypeManager->getStorage('media_type')->loadMultiple($bundles);
    $item = get_headers($media_val, 1);
    $type = $item['Content-Type'];
    // If there is a redirection then only get the value of the last page.
    $type = is_array($type) ? end($type) : $type;
    if (strpos($type, 'text/html') !== FALSE) {
      $media = $this->createOembedMedia($media_val, $mediaTypes);
    }
    else {
      $media = $this->createMediaWithFile($media_val, $mediaTypes);
    }
    return $media;
  }

  /**
   * Handles content path to uniquify or create aliases if needed.
   *
   * @param string $entityType
   *   Entity type id.
   * @param int $id
   *   Entity id in context for which to update alias.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function handleContentPath(string $entityType, int $id): void {
    $entity = $this->entityTypeManager->getStorage($entityType)->load($id);
    $this->pathAutoGenerator->updateEntityAlias($entity, 'update', ['force' => TRUE]);
  }

  /**
   * Helper method to convert csv to array.
   *
   * @param string $filename
   *   File uri.
   * @param string $encoding
   *   Encoding of the file.
   *
   * @return array|string
   *   Data as an array or error string.
   *
   * @throws \League\Csv\CannotInsertRecord
   * @throws \League\Csv\Exception
   */
  public function csvToArray($filename, $encoding) {

    if (!file_exists($filename) || !is_readable($filename)) {
      return FALSE;
    }
    $header = NULL;
    $data = [];

    $csv = Reader::createFromPath($filename, 'r');
    // Let's set the output BOM.
    $csv->setOutputBOM(Reader::BOM_UTF8);
    // Let's convert the incoming data to utf-8.
    $csv->addStreamFilter("convert.iconv.$encoding/utf-8");

    foreach ($csv as $row) {
      if (!$header) {
        $header = $row;
      }
      else {
        // If header and row column numbers don't match , csv file structure is
        // incorrect and needs to be updated.
        if (count($header) !== count($row)) {
          return [];
        }
        $data[] = array_combine($header, $row);
      }
    }

    // If no data rows then we do not need to proceed but throw error.
    if (!$data) {
      return [];
    }
    if (count($data) > self::CSV_ROW_LIMIT) {
      return self::OVER_LIMIT;
    }

    // Put values encoded to utf-8 in the csv source file so that it can be
    // used during migration as it does not support all encodings out of
    // the box.
    $writer = Writer::createFromPath($filename);
    // We use pseudo current timestamp field in the csv to allow same content
    // to be imported on some other vsite which might otherwise will not be
    // imported due to migration unique id requirements.
    $time = $this->time->getCurrentTime();
    array_unshift($header, 'Timestamp');
    $writer->insertOne($header);
    foreach ($data as $row) {
      array_unshift($row, $time);
      $writer->insertOne(array_values($row));
    }
    return $data;
  }

  /**
   * Creates Oembed type of media entity.
   *
   * @param string $url
   *   Url obtained from csv.
   * @param array $mediaTypes
   *   Media types for this entity type.
   *
   * @return \Drupal\media\Entity\Media|null
   *   Media entity/Null if not able to fetch embedly resource.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see os_media_media_insert()
   */
  protected function createOembedMedia($url, array $mediaTypes) : ?Media {
    $media_entity = NULL;
    if (!in_array('oembed', array_keys($mediaTypes))) {
      return $media_entity;
    }
    $data = $this->mediaHelper->fetchEmbedlyResource($url);
    if ($data) {
      /** @var \Drupal\media\Entity\Media $media_entity */
      $media_entity = Media::create([
        'bundle' => 'oembed',
        // Name changes later via a presave hook in os_media.
        'name' => 'Placeholder',
        'uid' => $this->currentUser->id(),
        'langcode' => $this->languageManager->getDefaultLanguage()->getId(),
        'field_media_oembed_content' => [
          'value' => $url,
        ],
      ]);
      $media_entity->save();
    }
    return $media_entity;
  }

  /**
   * Creates a Media entity which has a file attached to it.
   *
   * @param string $media_val
   *   Media value from csv.
   * @param array $mediaTypes
   *   Media types for this entity type.
   *
   * @return \Drupal\media\Entity\Media|null
   *   Media entity/Null if not able to download the file.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMediaWithFile($media_val, array $mediaTypes) : ?Media {
    $media_entity = NULL;
    $file = FALSE;
    /** @var \Drupal\file\FileInterface $file */
    $file = system_retrieve_file($media_val, $this->getUploadLocation(), TRUE);

    // Map and attach file to appropriate Media bundle.
    if ($file) {
      $extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
      $langCode = $this->languageManager->getDefaultLanguage()->getId();
      foreach ($mediaTypes as $mediaType) {
        $fieldDefinition = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
        if (is_null($fieldDefinition)) {
          continue;
        }
        $exts = explode(' ', $fieldDefinition->getSetting('file_extensions'));
        if (in_array($extension, $exts)) {
          /** @var \Drupal\media\Entity\Media $media_entity */
          $media_entity = Media::create([
            'bundle' => $mediaType->id(),
            'name' => $file->getFilename(),
            'uid' => $this->currentUser->id(),
            'langcode' => $langCode,
            $fieldDefinition->getName() => [
              'target_id' => $file->id(),
            ],
          ]);
          $media_entity->save();
        }
      }
    }
    return $media_entity;
  }

  /**
   * Get the file download/save location.
   *
   * @return string
   *   The path.
   */
  protected function getUploadLocation(): string {
    if ($purl = $this->vsiteManager->getActivePurl()) {
      $path = 'public://' . $purl . '/files';
      if ($this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY)) {
        return $path;
      }
    }
    return 'public://global';
  }

}
