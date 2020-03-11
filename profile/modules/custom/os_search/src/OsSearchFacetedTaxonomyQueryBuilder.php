<?php

namespace Drupal\os_search;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\vsite\Plugin\VsiteContextManager;
use Drupal\os_app_access\AppLoader;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\vsite\Plugin\AppManagerInterface;

/**
 * Helper class for Faceted Taxonomy Widget.
 */
class OsSearchFacetedTaxonomyQueryBuilder {

  /**
   * Entity manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Configuration Factory.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Vsite context manager.
   *
   * @var Drupal\vsite\Plugin\VsiteContextManager
   */
  protected $vsiteContext;

  /**
   * App loader for current user.
   *
   * @var Drupal\os_app_access\AppLoader
   */
  protected $appLoader;

  /**
   * Current user.
   *
   * @var Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Facet builder.
   *
   * @var Drupal\os_search\OsSearchFacetBuilder
   */
  protected $facetBuilder;

  /**
   * Search Helper.
   *
   * @var Drupal\os_search\OsSearchHelper
   */
  protected $searchHelper;

  /**
   * App manager.
   *
   * @var Drupal\vsite\Plugin\AppManagerInterface
   */
  protected $appManager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
  VsiteContextManager $vsite_context,
    AppLoader $app_loader,
  AccountProxy $current_user,
    OsSearchFacetBuilder $facet_builder,
  CurrentRouteMatch $route_match,
    OsSearchHelper $search_helper,
  AppManagerInterface $app_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->blockContent = $entity_type_manager->getStorage('block_content');
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->requestStack = $request_stack;
    $this->vsiteContext = $vsite_context;
    $this->appLoader = $app_loader;
    $this->currentUser = $current_user;
    $this->facetBuilder = $facet_builder;
    $this->routeMatch = $route_match;
    $this->searchHelper = $search_helper;
    $this->appManager = $app_manager;
  }

  /**
   * Generates Search Api Query.
   *
   * Load data from elasttic search for Faceted Widget..
   *
   * @return Drupal\search_api\Query\QueryInterface
   *   Search Api query.
   */
  public function getQuery() {
    $search_page_index = $this->entityTypeManager->getStorage('search_api_index')->load('os_search_index');
    $query = $search_page_index->query();
    return $query;
  }

  /**
   * Prepare list of terms for Filter Taxonomy.
   *
   * @param array $vocab_filter
   *   Filter for vocabulary.
   * @param string $vocab_order_by
   *   Order by key for Taxonomy Vocabulary.
   * @param string $term_order_by
   *   Order by key for Taxonomy Terms.
   * @param array $buckets
   *   Facets loaded for a field.
   * @param string $field_processor
   *   Field processor name.
   *
   * @return array
   *   List of terms.
   */
  public function prepareFacetVocaulbaries(array $vocab_filter, string $vocab_order_by, string $term_order_by, array $buckets, $field_processor = 'taxonomy_term') {
    $vocab_list = [];

    $taxonomy_vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');

    $query = $taxonomy_vocabulary_storage->getQuery();
    if (!in_array('_none', $vocab_filter) && count($vocab_list) != 0) {
      $query->condition('vid', $vocab_filter, 'IN');
    }
    $query->sort($vocab_order_by, 'ASC');
    $vids = $query->execute();
    $vocabularies = $taxonomy_vocabulary_storage->loadMultiple($vids);

    $taxonomy_term_storage = $this->entityTypeManager->getStorage($field_processor);

    $bucket_key = [];

    if ($buckets) {
      foreach ($buckets as $key => $bucket) {
        $bucket_key[$key] = $bucket['key'];
      }

      $query = $taxonomy_term_storage->getQuery();
      $query->condition('tid', $bucket_key, 'IN');

      // When relevance selected removing sort_by.
      if ($term_order_by != 'rev') {
        $query->sort($term_order_by, 'DESC');
      }

      $tids = $query->execute();
      $terms = $taxonomy_term_storage->loadMultiple($tids);
      foreach ($vocabularies as $vocabulary) {
        foreach ($terms as $term) {
          foreach ($buckets as $bucket) {
            if ($term->id() == $bucket['key'] && $term->bundle() === $vocabulary->id()) {
              $vocab_list[$term->bundle()]['children'][] = $bucket;
              $vocab_list[$term->bundle()]['name'] = $vocabularies[$term->bundle()]->get('name');
            }
          }
        }
      }
    }
    return $vocab_list;
  }

  /**
   * Load Vocabularies based on app.
   *
   * @param string $app
   *   Vocabularies.
   *
   * @return array
   *   List of vocabularies name.
   */
  public function prepareVocabulariesList($app = '') {
    $taxonomy_vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
    $vocabularies = $taxonomy_vocabulary_storage->loadMultiple();
    // List if app is not there in vocabularies reference entity list.
    $vocabularies_name = [
      '_none' => '--None--',
    ];
    foreach ($vocabularies as $vocabulary) {
      if (($app != '' && in_array('node:' . $app, $vocabulary->allowed_vocabulary_reference_types)) || $app == '') {
        $vocabularies_name[$vocabulary->id()] = $vocabulary->label();
      }
    }

    return $vocabularies_name;

  }

}
