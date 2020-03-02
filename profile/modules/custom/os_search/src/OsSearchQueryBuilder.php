<?php

namespace Drupal\os_search;

use Drupal\search_api\Query\QueryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Html;
use Drupal\vsite\Plugin\VsiteContextManager;
use Drupal\os_app_access\AppLoader;
use Drupal\Core\Session\AccountProxy;
use Drupal\os_search\Plugin\OsWidgets\SearchSortWidget;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\vsite\Plugin\AppManagerInterface;

/**
 * Helper class for search query builder.
 */
class OsSearchQueryBuilder {

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, VsiteContextManager $vsite_context, AppLoader $app_loader, AccountProxy $current_user, OsSearchFacetBuilder $facet_builder, CurrentRouteMatch $route_match, OsSearchHelper $search_helper, AppManagerInterface $app_manager) {
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
   * Provides basic search api query either for current page.
   * Or default (If current page is not search api page).
   *
   * @return Drupal\search_api\Query\QueryInterface
   *   Search Api query.
   */
  public function getQuery() {
    // Default Index.
    $search_page_index_id = 'os_search_index';

    // Default conjunction.
    $conjunction = 'OR';
    $search_page_id = $this->routeMatch->getParameter('search_api_page_name');

    if ($search_page_id) {
      // Load search page, if exists.
      // Find better method to load search page object.
      $search_page = $this->entityTypeManager->getStorage('search_api_page')->load($search_page_id);
      $search_page_index_id = $search_page->getIndex();
      $conjunction = $search_page->getParseModeConjunction();
    }
    $search_page_index = $this->entityTypeManager->getStorage('search_api_index')->load($search_page_index_id);
    $query = $search_page_index->query();

    // Set conjunction based on search page settings.
    $query->getParseMode()->setConjunction($conjunction);

    // Allow to alter query based on tags.
    $query->addTag('get_all_facets');

    // Adding tag for terms condition.
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name == 'os_search.app_global') {
      $query->addTag('group_terms_by_taxonomy');
    }

    return $query;
  }

  /**
   * Build query based on f filters found in path.
   *
   * @param Drupal\search_api\Query\QueryInterface $query
   *   Query object to be altered.
   */
  public function queryBuilder(QueryInterface $query) {
    $group = $this->vsiteContext->getActiveVsite();
    $keys = $query->getKeys();

    if (!$keys) {
      $keys = $this->requestStack->getCurrentRequest()->attributes->get('keys');
      $query->keys($keys);
    }
    // Consider only 'f' array key as facet filters from querystring.
    $filters = $this->requestStack->getCurrentRequest()->query->get('f') ?? [];
    // Add unreal group condition for global search with no keys.
    if (!$group && !$keys && !$query->hasTag('get_all_facets') && !$filters) {
      $filters = ["custom_search_group:0"];
    }
    elseif ($group) {
      // Load group condition if search belongs to vsite.
      $group_id = $group->id();
      $filters[] = "custom_search_group:{$group_id}";
    }

    if ($filters) {
      $this->validateFacetFilters($filters);
      // Apply conditions.
      $this->applyFilterConditions($filters, $query);
    }

    $this->applySortConditions($query);

  }

  /**
   * Strips unallowed or unknown facet filters.
   *
   * @param array $filters
   *   Array of filters from querystring.
   */
  protected function validateFacetFilters(array &$filters = []) {
    $available_facets = $this->searchHelper->getAllowedFacetIds() ?? [];
    $enabled_facets = array_keys($available_facets);

    foreach ($filters as $key => $filter) {
      $field_name = substr($filter, 0, strpos($filter, ':'));
      if (!in_array($field_name, $enabled_facets)) {
        unset($filters[$key]);
      }
    }
  }

  /**
   * Apply allowed filters.
   *
   * @param array $filters
   *   Array of allowed/enabled filters.
   * @param Drupal\search_api\Query\QueryInterface $query
   *   Query object to be altered.
   */
  protected function applyFilterConditions(array $filters, QueryInterface $query) {
    $req_criteria = [
      'year' => '1970',
      'month' => '1',
      'day' => '1',
      'hour' => '00',
      'minute' => '00',
    ];
    $date_field = FALSE;
    if ($query->hasTag('group_by_faceted_taxonomy')) {
      $this->applyFacetedTaxonomyAppFilterConditions($filters, $query);
    }

    foreach ($filters as $filter) {
      $criteria = explode(':', Html::escape($filter));
      $query_processor = $this->facetBuilder->getFieldProcessor($criteria[0]);

      if ($query_processor == 'date' && count($criteria) > 1) {
        $date_parts = explode('-', $criteria[1]);
        if (count($date_parts) > 1) {
          $date_field = $criteria[0];
          $req_criteria[$date_parts[0]] = $date_parts[1];
        }
      }
      elseif (count($criteria) > 1) {
        $query->addCondition($criteria[0], $criteria[1]);
      }
    }

    if ($date_field) {
      $this->applyDateConditions($date_field, $req_criteria, $query);
    }

    $this->applyEnabledAppsConditions($query);
  }

  /**
   * Apply enabled apps conditions.
   *
   * @param Drupal\search_api\Query\QueryInterface $query
   *   Query object to be altered.
   */
  private function applyEnabledAppsConditions(QueryInterface $query) {
    $enabled_apps = $this->appLoader->getAppsForUser($this->currentUser);
    $enabled_apps_list = [];

    foreach ($enabled_apps as $enabled_app) {
      if (isset($enabled_app['bundle']) && count($enabled_app['bundle']) == 1) {
        $enabled_apps_list[] = reset($enabled_app['bundle']);
      }
      elseif (isset($enabled_app['bundle']) && count($enabled_app['bundle']) > 1) {
        foreach ($enabled_app['bundle'] as $bundle) {
          $enabled_apps_list[] = $bundle;
        }
      }
      else {
        $enabled_apps_list[] = $enabled_app['entityType'];
      }
    }

    if ($enabled_apps_list) {
      $query->addCondition('custom_search_bundle', $enabled_apps_list, 'IN');
    }
  }

  /**
   * Apply sort conditions.
   *
   * @param Drupal\search_api\Query\QueryInterface $query
   *   Query object to be altered.
   */
  private function applySortConditions(QueryInterface $query) {

    // Get the sort url parameter.
    $sort = $this->requestStack->getCurrentRequest()->query->get('sort');
    $sort_direction = $this->requestStack->getCurrentRequest()->query->get('dir') ?? 'ASC';
    if ($sort) {
      $sort_type = SearchSortWidget::SORT_TYPE;
      if (in_array($sort, $sort_type)) {
        $query->sort('custom_' . $sort, $sort_direction);
      }
    }
  }

  /**
   * Apply date filter conditions.
   *
   * @param string $date_field
   *   Date field for which condition is to be applied.
   * @param array $req_criteria
   *   Date time array to be processed as filters.
   * @param Drupal\search_api\Query\QueryInterface $query
   *   Query object to be altered.
   */
  private function applyDateConditions(string $date_field, array $req_criteria, QueryInterface $query) {
    $filters = $this->requestStack->getCurrentRequest()->query->get('f');
    $filter_string = implode('|', $filters);

    $start_timestamp = (int) strtotime("{$req_criteria['year']}-{$req_criteria['month']}-{$req_criteria['day']} {$req_criteria['hour']}:{$req_criteria['minute']}:00");
    $end_timestamp = (int) strtotime("{$req_criteria['year']}-12-31 11:59:59");

    if (strpos($filter_string, 'month-') !== FALSE) {
      $end_timestamp = (int) strtotime("{$req_criteria['year']}-{$req_criteria['month']}-31 11:59:59");
    }

    if (strpos($filter_string, 'day-') !== FALSE) {
      $end_timestamp = (int) strtotime("{$req_criteria['year']}-{$req_criteria['month']}-{$req_criteria['day']} 11:59:59");
    }

    if (strpos($filter_string, 'hour-') !== FALSE) {
      $end_timestamp = (int) strtotime("{$req_criteria['year']}-{$req_criteria['month']}-{$req_criteria['day']} {$req_criteria['hour']}:59:59");
    }

    if (strpos($filter_string, 'minute-') !== FALSE) {
      $end_timestamp = (int) strtotime("{$req_criteria['year']}-{$req_criteria['month']}-{$req_criteria['day']} {$req_criteria['hour']}:{$req_criteria['minute']}:59");
    }

    $query->addCondition($date_field, $start_timestamp, '>=');
    $query->addCondition($date_field, $end_timestamp, '<=');
  }

  /**
   * Applied this fucntion in order to support 'OR within 'AND' between vocabs.
   *
   * @param array $filters
   *   Array of allowed/enabled filters.
   * @param Drupal\search_api\Query\QueryInterface $query
   *   Query object to be altered.
   */
  protected function applyTaxonomyFilterConditions(array $filters, QueryInterface $query) {
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $term_ids = [];

    foreach ($filters as $key => $filter) {
      $filter_part = explode(':', Html::escape($filter));
      $term_ids[$key] = end($filter_part);
    }

    $terms = $termStorage->loadMultiple($term_ids);
    $vocabs = [];

    foreach ($terms as $key => $term) {
      $vid = $term->getVocabularyId();
      $vocabs[$vid][] = $term->id();
    }

    foreach ($vocabs as $vocab) {
      $query->addCondition('custom_taxonomy', $vocab, 'IN');
    }

  }

  /**
   * Apply allowed filters.
   *
   * @param array $filters
   *   Array of allowed/enabled filters.
   * @param Drupal\search_api\Query\QueryInterface $query
   *   Query object to be altered.
   */
  protected function applyFacetedTaxonomyAppFilterConditions(array $filters, QueryInterface $query) {

    $app_requested = $this->requestStack->getCurrentRequest()->attributes->get('app');

    $enabled_apps = $this->appManager->getDefinitions();
    $enabled_apps_list = [];
    if (isset($enabled_apps[$app_requested]['bundle'])) {
      $enabled_apps_list = array_merge($enabled_apps_list, $enabled_apps[$app_requested]['bundle']);
    }
    else {
      $enabled_apps_list[] = $enabled_apps[$app_requested]['entityType'];
    }
    if ($enabled_apps_list) {
      $query->addCondition('custom_search_bundle', $enabled_apps_list, 'IN');
    }

    $term_filters = [];
    foreach ($filters as $key => $filter) {
      if (strpos($filter, 'custom_taxonomy') !== FALSE) {
        $term_filters[] = $filter;
        unset($filters[$key]);
      }
    }
    $this->applyTaxonomyFilterConditions($term_filters, $query);
  }

}
