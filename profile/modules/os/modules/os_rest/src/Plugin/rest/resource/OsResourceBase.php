<?php

namespace Drupal\os_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OsResourceBase.
 *
 * @package Drupal\os_rest\Plugin\rest\resource
 */
abstract class OsResourceBase extends ResourceBase {

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
   * Creates a new OsVocabularyResource object.
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
   * Get all angular related data from given array.
   *
   * @param array $vocabularies
   *   Array of vocabularies entities.
   * @param int $vsite
   *   Group id.
   *
   * @return array
   *   Transformed data array for angular.
   */
  protected function getVocabulariesData(array $vocabularies, int $vsite) {
    $data = [];
    foreach ($vocabularies as $vocabulary) {
      $terms = $this->taxonomyStorage->loadTree($vocabulary->id(), 0, 1, TRUE);
      $tree = [];
      foreach ($terms as $tree_object) {
        $this->buildTree($tree, $tree_object, $vocabulary->id());
      }
      $settings = $this->cpTaxonomyHelper->getVocabularySettings($vocabulary->id());
      $saved_entity_types = $settings['allowed_vocabulary_reference_types'];
      $bundles = [];
      foreach ($saved_entity_types as $entity_key) {
        list($vocab_entity_type, $vocab_bundle) = explode(':', $entity_key);
        $bundles[$vocab_entity_type][] = $vocab_bundle;
      }
      $data['rows'][] = [
        'id' => $vocabulary->id(),
        'machine_name' => $vocabulary->id(),
        'label' => $vocabulary->label(),
        'vsite' => $vsite,
        'form' => $settings['widget_type'],
        'tree' => $tree,
        'bundles' => $bundles,
      ];
    }
    $data['count'] = count($vocabularies);
    $data['pager']['current_page'] = 0;
    $data['pager']['total_pages'] = 1;

    return $data;
  }

}
