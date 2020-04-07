<?php

namespace Drupal\os_rest\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cp_taxonomy\CpTaxonomyHelperInterface;
use Drupal\os_rest\OsRestEntitiesDeletedInterface;
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
 *   id = "os_vocabulary_updates",
 *   label = @Translation("OpenScholar vocabulary updates"),
 *   uri_paths = {
 *     "canonical" = "/api/vocabulary-updates/{timestamp}"
 *   }
 * )
 */
class OsVocabularyUpdatesResource extends OsResourceBase {

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
   * @param \Drupal\os_rest\OsRestEntitiesDeletedInterface $entitiesDeleted
   *   Entities deleted.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, VsiteContextManagerInterface $vsite_context_manager, EntityTypeManagerInterface $entityTypeManager, CpTaxonomyHelperInterface $cpTaxonomyHelper, OsRestEntitiesDeletedInterface $entitiesDeleted) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $vsite_context_manager, $entityTypeManager, $entitiesDeleted);
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
      $container->get('cp.taxonomy.helper'),
      $container->get('os_rest.entities_deleted')
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
  public function get(int $timestamp, $a, Request $request): ResourceResponse {

    $vsite_id = $request->get('vsite');
    $group = $this->vsiteContextManager->getActiveVsite();
    if (!$group || $group->id() != $vsite_id) {
      $resource = new ResourceResponse([]);
      $resource->addCacheableDependency('vsite:0');
      return $resource;
    }

    $vocabularies = $this->vocabularyStorage->loadMultiple();
    $data = $this->getVocabulariesData($vocabularies, $group->id());
    $deleted = $this->getDeletedEntities('taxonomy_vocabulary', $timestamp, $request);
    $data = array_merge($data, $deleted);
    $resource = new ResourceResponse($data);

    $resource->addCacheableDependency('vsite:' . $group->id());
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
