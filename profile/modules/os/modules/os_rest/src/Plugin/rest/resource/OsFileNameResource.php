<?php

namespace Drupal\os_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides resources for filename.
 *
 * @RestResource(
 *   id = "os_filename_resource",
 *   label = @Translation("Os Filename Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/media/filename/{filename}"
 *   }
 * )
 */
final class OsFileNameResource extends ResourceBase {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Creates a new OsFileNameResource object.
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
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, VsiteContextManagerInterface $vsite_context_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
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
      $container->get('vsite.context_manager')
    );
  }

  /**
   * The GET request handler.
   *
   * This checks the filename for collisions.
   *
   * @param string $filename
   *   The filename to check for collisions.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get(string $filename): ResourceResponse {
    $file_default_scheme = file_default_scheme();
    $directory = "{$file_default_scheme}://global/";
    /** @var string $purl */
    $purl = $this->vsiteContextManager->getActivePurl();

    if ($purl) {
      $directory = "{$file_default_scheme}://{$purl}/files/";
    }

    $new_filename = strtolower($filename);
    $new_filename = preg_replace('|[^a-z0-9\-_\.]|', '_', $new_filename);
    $new_filename = str_replace('__', '_', $new_filename);
    $new_filename = preg_replace('|_\.|', '.', $new_filename);
    $invalidChars = FALSE;
    if ($filename != $new_filename) {
      $invalidChars = TRUE;
    }

    $full_name = $directory . $new_filename;
    $counter = 0;
    $collision = FALSE;
    while (file_exists($full_name)) {
      $collision = TRUE;
      $pos = strrpos($new_filename, '.');
      if ($pos !== FALSE) {
        $name = substr($new_filename, 0, $pos);
        $ext = substr($new_filename, $pos);
      }
      else {
        $name = basename($full_name);
        $ext = '';
      }

      $full_name = sprintf('%s%s_%02d%s', $directory, $name, ++$counter, $ext);
    }
    $resource = new ResourceResponse([
      'expectedFileName' => basename($full_name),
      'collision' => $collision,
      'invalidChars' => $invalidChars,
    ]);
    $resource->addCacheableDependency($filename);

    return $resource;
  }

}
