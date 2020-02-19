<?php

namespace Drupal\os_search\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Database\Connection;
use Drupal\os_search\OsSearchFacetBuilder;
use Drupal\os_search\OsSearchQueryBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class FacetWidget.
 *
 * @OsWidget(
 *   id = "facet_widget",
 *   title = @Translation("Facets")
 * )
 */
class FacetWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * Entity manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Configuration Factory.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Block content.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $blockStorage;

  /**
   * Facets builder.
   *
   * @var \Drupal\os_search\OsSearchFacetBuilder
   */
  protected $osSearchFacetBuilder;

  /**
   * Os Search query builder.
   *
   * @var Drupal\os_search\OsSearchQueryBuilder
   */
  protected $searchQueryBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $route_match, OsSearchFacetBuilder $os_search_facet_builder, OsSearchQueryBuilder $os_search_query_builder, Connection $connection, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->osSearchFacetBuilder = $os_search_facet_builder;
    $this->searchQueryBuilder = $os_search_query_builder;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('os_search.os_search_facet_builder'),
      $container->get('os_search.os_search_query_builder'),
      $container->get('database'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $route_name = $this->routeMatch->getRouteName();
    $buckets = [];
    $reduced_filters = [];
    $field_id = '';
    $field_label = '';

    if (strpos($route_name, 'search_api_page') !== FALSE || strpos($route_name, 'os_search.app_global') !== FALSE) {
      // Load search page.
      $query = $this->searchQueryBuilder->getQuery();
      $search_page_index = $query->getIndex();

      // Dependent filters.
      $this->searchQueryBuilder->queryBuilder($query);

      $field_id = $block_content->get('field_facet_id')->value;
      $field_label = $search_page_index->getField($field_id)->getLabel();
      $field_type = $search_page_index->getField($field_id)->getType();
      $buckets = $this->osSearchFacetBuilder->getFacetBuckets($field_id, $query);
      $this->osSearchFacetBuilder->prepareFacetLabels($buckets, $field_id);
      $this->osSearchFacetBuilder->prepareFacetLinks($buckets, $field_id);

      $field_processor = $this->osSearchFacetBuilder->getFieldProcessor($field_id);

      // Get current search summary with (-) link.
      $reduced_filters = $this->osSearchFacetBuilder->getCurrentSearchSummary($field_id);
      $build = [];
      $build['current_summary'] = $this->renderReducedfilter($reduced_filters, $field_label, $route_name);

      if ($field_type != 'date') {
        $buckets = (count($buckets) > 1) ? $buckets : [];
      }

      switch ($field_id) {
        case 'custom_taxonomy':
          $vocab_list = $this->osSearchFacetBuilder->prepareFacetVocaulbaries($buckets, $field_processor);
          $build[] = $this->renderableTaxonomyArray($vocab_list, $route_name, $field_id, $field_label);
          break;

        default:
          $build[] = $this->renderableArray($buckets, $route_name, $field_id, $field_label);
          break;
      }

    }
    else {
      $build['empty_build'] = [
        '#theme' => 'item_list__search_widget',
        '#empty' => '',
        '#list_type' => 'ul',
        '#title' => $this->t('Not search context.'),
        '#items' => [],
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }

    $build['#block_content'] = $block_content;
  }

  /**
   * Building links for future facets.
   *
   * @param array $buckets
   *   Facets buckets.
   * @param string $route_name
   *   Current route name.
   * @param string $field_name
   *   Facets field name.
   * @param string $field_label
   *   Label of facet field.
   * @param string $header
   *   Vocabulary name.
   *
   * @return array
   *   Widget build
   */
  private function renderableArray(array $buckets, $route_name, string $field_name, string $field_label, string $header = NULL): array {
    $items = [];
    $keys = $this->requestStack->getCurrentRequest()->attributes->get('keys');
    $filters = $this->requestStack->getCurrentRequest()->query->get('f') ?? [];
    $route_parameters = $this->routeMatch->getParameters()->all();

    foreach ($buckets as $bucket) {
      $item_label = isset($bucket['label']) ? $bucket['label'] : ucwords($bucket['key']);
      $item_label = is_array($item_label) ? reset($item_label) : $item_label;
      $route_parameters['f'] = array_merge($filters, $bucket['query']);
      $route_parameters['keys'] = $keys;
      $path = Url::fromRoute($route_name, $route_parameters);

      $items[] = Link::fromTextAndUrl($this->t('@label (@count)', ['@label' => $item_label, '@count' => $bucket['doc_count']]), $path)->toString();
    }

    if ($header) {
      $build[$field_name]['title'] = [
        '#markup' => $this->t('@header', ['@header' => $header]),
      ];
    }

    $build[$field_name]['facets'] = [
      '#theme' => 'item_list__search_widget',
      '#empty' => $this->t('No filters available'),
      '#list_type' => 'ul',
      '#items' => $items,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    if ($header) {
      unset($build[$field_name]['facets']['#title']);
    }

    return $build;
  }

  /**
   * Building links for taxonomy facets.
   *
   * @param array $buckets
   *   Facets buckets.
   * @param string $route_name
   *   Current route name.
   * @param string $field_name
   *   Facets field name.
   * @param string $field_label
   *   Label of facet field.
   *
   * @return array
   *   Widget build
   */
  private function renderableTaxonomyArray(array $buckets, $route_name, string $field_name, string $field_label): array {

    foreach ($buckets as $term_list) {
      $vocab_name = $term_list['name'];

      $build[] = $this->renderableArray($term_list['children'], $route_name, $field_name, $field_label, $vocab_name);
    }
    if (empty($buckets)) {
      $build[$field_name]['empty-filter'] = [
        '#markup' => '<div>' . $this->t('No filters available') . '</div>',
      ];
    }
    $build['#cache'] = [
      'max-age' => 0,
    ];
    return $build;
  }

  /**
   * Build render array for reduced filter.
   *
   * @param array $reduced_filters
   *   Reduced filters.
   * @param string $field_label
   *   Label of facet field.
   * @param string $route_name
   *   Current route name.
   *
   * @return array
   *   Widget build
   */
  public function renderReducedfilter(array $reduced_filters = NULL, string $field_label, string $route_name): array {
    $keys = $this->requestStack->getCurrentRequest()->attributes->get('keys');
    if ($reduced_filters['needed']) {
      foreach ($reduced_filters['reduced_filter'] as $reduced_filter) {
        $querys = isset($reduced_filter['query']) ? $reduced_filter['query'] : [];
        foreach ($querys as $key => $query) {
          if ($query == $reduced_filter['filter']) {
            unset($querys[$key]);
          }
        }

        $item_label = isset($reduced_filter['label']) ? $reduced_filter['label'] : '';
        $item_label = is_array($item_label) ? reset($item_label) : $item_label;
        $path = Url::fromRoute($route_name, ['f' => $querys, 'keys' => $keys]);
        $path_string = Link::fromTextAndUrl("(-)", $path)->toString();
        $summary_items[] = $this->t('@path_string @label', ['@path_string' => $path_string, '@label' => $item_label]);
      }
    }
    $build['current_summary']['title'] = [
      '#markup' => '<h2 class="block-title">' . $this->t('Filter By @field_label', ['@field_label' => $field_label]) . '</h2>',
    ];
    $build['current_summary'][] = [
      '#theme' => 'item_list__search_widget',
      '#empty' => '',
      '#list_type' => 'ul',
      '#items' => $summary_items,
    ];
    return $build;
  }

}
