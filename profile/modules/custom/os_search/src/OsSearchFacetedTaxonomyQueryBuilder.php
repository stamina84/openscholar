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
   * @param string $vocab_order_by_dir
   *   Vocab order by direction settings.
   * @param string $term_order_by_dir
   *   Term order by direction settings.
   * @param string $selected_app
   *   Selected app for which vocab will be listed.
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
  public function prepareFacetVocaulbaries(string $vocab_order_by_dir, string $term_order_by_dir, string $selected_app, array $vocab_filter, string $vocab_order_by, string $term_order_by, array $buckets, $field_processor = 'taxonomy_term') {
    $vocab_list = [];

    $taxonomy_vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');

    $query = $taxonomy_vocabulary_storage->getQuery();

    $vocab_filter = count($vocab_filter) == 1 ? $vocab_filter[0] : $vocab_filter;
    if (count($vocab_filter) > 1) {
      foreach ($vocab_filter as $key => $value) {
        $vocab_filter[$key] = $value['value'];
      }
    }
    if (in_array('all', $vocab_filter)) {
      $vocab_filter = $this->prepareVocabulariesList($selected_app);
      $vocab_filter = array_keys($vocab_filter);
    }

    if (!in_array('_none', $vocab_filter) && count($vocab_filter) > 0) {
      $query->condition('vid', $vocab_filter, 'IN');
    }
    $query->sort($vocab_order_by, $vocab_order_by_dir);
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
        $query->sort($term_order_by, $term_order_by_dir);
      }

      $tids = $query->execute();
      $terms = $taxonomy_term_storage->loadMultiple($tids);
      foreach ($vocabularies as $vocabulary) {
        $vocab_types = isset($vocabulary->allowed_vocabulary_reference_types) ? $vocabulary->allowed_vocabulary_reference_types : [];
        if (in_array('node:' . $selected_app, $vocab_types)) {
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
    $vocabularies_name = [];
    foreach ($vocabularies as $vocabulary) {
      $vocab_types = isset($vocabulary->allowed_vocabulary_reference_types) ? $vocabulary->allowed_vocabulary_reference_types : [];
      if (($app != '' && in_array('node:' . $app, $vocab_types)) || $app == '') {
        $vocabularies_name[$vocabulary->id()] = $vocabulary->label();
      }
    }

    return $vocabularies_name;

  }

  /**
   * Generate remove (-) links for already applied filters using query string.
   *
   * @param string $field_id
   *   Field for which summary is needed.
   *
   * @return array
   *   Array of current search summary.
   */
  public function getCurrentSearchSummary($field_id) {
    $filters = $this->requestStack->getCurrentRequest()->query->get('f') ?? [];
    $available_facets = $this->searchHelper->getAllowedFacetIds() ?? [];

    $summary = [
      'reduced_filter' => [],
      'needed' => FALSE,
    ];

    if (!isset($available_facets[$field_id])) {
      return $summary;
    }

    $i = 0;
    $field_processor = 'taxonomy_term';
    foreach ($filters as $filter) {
      if (strpos($filter, $field_id) !== FALSE) {
        $value = str_replace("{$field_id}:", '', $filter);
        $summary['needed'] = TRUE;
        $summary['reduced_filter'][$i]['filter'] = $filter;
        $summary['reduced_filter'][$i]['label'] = $this->prepareSingleLabel($field_processor, $field_id, $value);
        $summary['reduced_filter'][$i]['query'] = $filters;
        $summary['reduced_filter'][$i]['key'] = $value;
        $i++;
      }
    }
    return $summary;
  }

  /**
   * Convert value to label.
   *
   * @param string $field_label_processor
   *   Processor to be used.
   * @param string $field_id
   *   Field ID.
   * @param string $value
   *   Value of the filter.
   *
   * @return string
   *   Label string.
   */
  protected function prepareSingleLabel($field_label_processor, $field_id, $value) {
    $entity_storage = $this->entityTypeManager->getStorage($field_label_processor);
    $label = $entity_storage->load($value)->label();

    return $label;
  }

}
