<?php

namespace Drupal\os_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cp_taxonomy\CpTaxonomyHelperInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\TermInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OsVocabularyResource.
 *
 * @package Drupal\os_rest\Plugin\rest\resource
 *
 * @RestResource(
 *   id = "os_vocabulary",
 *   label = @Translation("OpenScholar vocabularies"),
 *   uri_paths = {
 *     "canonical" = "/api/vocabulary"
 *   }
 * )
 */
class OsVocabularyResource extends ResourceBase {

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
   * Taxonomy helper.
   *
   * @var \Drupal\cp_taxonomy\CpTaxonomyHelperInterface
   */
  protected $cpTaxonomyHelper;

  /**
   * Taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyStorage;

  /**
   * Vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

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
   * @param \Drupal\cp_taxonomy\CpTaxonomyHelperInterface $cpTaxonomyHelper
   *   Taxonomy helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, VsiteContextManagerInterface $vsite_context_manager, EntityTypeManagerInterface $entityTypeManager, CpTaxonomyHelperInterface $cpTaxonomyHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->vsiteContextManager = $vsite_context_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->cpTaxonomyHelper = $cpTaxonomyHelper;
    $this->vocabularyStorage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $this->taxonomyStorage = $this->entityTypeManager->getStorage('taxonomy_term');
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
      $container->get('entity_type.manager'),
      $container->get('cp.taxonomy.helper')
    );
  }

  /**
   * The GET request handler.
   *
   * Return a list with vsite's vocabularies.
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

    $entity_type = $request->get('entity_type');
    $bundle = $request->get('bundle');
    if ($entity_type != 'node') {
      $bundle = '*';
    }
    $bundle_key = $entity_type . ':' . $bundle;

    $vids = $this->cpTaxonomyHelper->searchAllowedVocabulariesByType($bundle_key);
    $vocabularies = $this->vocabularyStorage->loadMultiple($vids);
    $data = [];
    foreach ($vocabularies as $vocabulary) {
      $terms = $this->taxonomyStorage->loadTree($vocabulary->id(), 0, 1, TRUE);
      $tree = [];
      foreach ($terms as $tree_object) {
        $this->buildTree($tree, $tree_object, $vocabulary->id());
      }
      $settings = $this->cpTaxonomyHelper->getVocabularySettings($vocabulary->id());
      $data['rows'][] = [
        'id' => $vocabulary->id(),
        'machine_name' => $vocabulary->id(),
        'label' => $vocabulary->label(),
        'vsite' => $group->id(),
        'form' => $settings['widget_type'],
        'tree' => $tree,
        'bundles' => [],
      ];
    }
    $data['count'] = count($vocabularies);
    $data['pager']['current_page'] = 0;
    $data['pager']['total_pages'] = 1;
    $resource = new ResourceResponse($data);

    $resource->addCacheableDependency('vsite:' . $group->id() . ':' . $bundle_key);
    return $resource;
  }

  /**
   * Build the tree structure as needed for the JS widget.
   *
   * @param array $tree
   *   Array of taxonomy where we want to store terms hierarchy.
   * @param \Drupal\taxonomy\TermInterface $term
   *   Current term.
   * @param string $vocabulary
   *   Vocabulary id.
   */
  protected function buildTree(array &$tree, TermInterface $term, string $vocabulary) {
    if ($term->depth != 0) {
      return;
    }
    $tree_data = [
      'label' => $term->label(),
      'value' => $term->id(),
      'children' => [],
    ];
    $array_children = &$tree_data['children'];

    $tree[] = $tree_data;

    $children = $this->taxonomyStorage->loadChildren($term->id());
    if (!$children) {
      return;
    }

    $child_tree_terms = $this->taxonomyStorage->loadTree($vocabulary, $term->id(), NULL, TRUE);

    foreach ($children as $child) {
      foreach ($child_tree_terms as $child_tree_term) {
        if ($child_tree_term->id() == $child->id()) {
          $this->buildTree($array_children, $child_tree_term, $vocabulary);
        }
      }
    }
  }

}
