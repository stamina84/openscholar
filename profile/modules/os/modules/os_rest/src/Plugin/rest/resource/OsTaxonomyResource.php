<?php

namespace Drupal\os_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OsTaxonomyResource.
 *
 * @package Drupal\os_rest\Plugin\rest\resource
 *
 * @RestResource(
 *   id = "os_taxonomy",
 *   label = @Translation("OpenScholar taxonomy"),
 *   uri_paths = {
 *     "canonical" = "/api/taxonomy"
 *   }
 * )
 */
class OsTaxonomyResource extends ResourceBase {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new OsTaxonomyResource object.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, VsiteContextManagerInterface $vsite_context_manager, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->vsiteContextManager = $vsite_context_manager;
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('vsite.context_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * The GET request handler.
   *
   * This checks the filename for collisions.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  public function get($a, Request $request): ResourceResponse {

    $vsite_id = $request->get('vsite');
    $group = $this->vsiteContextManager->getActiveVsite();
    if (!$group || $group->id() != $vsite_id) {
      $resource = new ResourceResponse([]);
      $resource->addCacheableDependency('vsite:0');
      return $resource;
    }

    $vid = $request->get('vid');
    $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $taxonomy_storage->loadTree($vid);
    $data = [];
    foreach ($terms as $term) {
      $data['rows'][] = [
        'id' => $term->tid,
        'label' => $term->name,
        'vocab' => $term->vid,
        'vid' => $term->vid,
      ];
    }
    $data['count'] = count($terms);
    $data['pager']['current_page'] = 0;
    $data['pager']['total_pages'] = 1;
    $resource = new ResourceResponse($data);

    $resource->addCacheableDependency('vsite:' . $group->id() . ':' . $vid);
    return $resource;
  }

}
