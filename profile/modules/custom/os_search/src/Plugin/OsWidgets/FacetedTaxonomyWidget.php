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
use Drupal\os_search\OsSearchFacetedTaxonomyQueryBuilder;
use Drupal\os_search\OsSearchQueryBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class FacetedTaxonomyWidget.
 *
 * @OsWidget(
 *   id = "faceted_taxonomy_widget",
 *   title = @Translation("Faceted Taxonomy")
 * )
 */
class FacetedTaxonomyWidget extends OsWidgetsBase implements OsWidgetsInterface {

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
   * Os Search query builder Faceted Taxonomy.
   *
   * @var Drupal\os_search\OsSearchFacetedTaxonomyQueryBuilder
   */
  protected $searchFacetedTaxonoQueryBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CurrentRouteMatch $route_match, OsSearchFacetBuilder $os_search_facet_builder, OsSearchQueryBuilder $os_search_query_builder, Connection $connection, RequestStack $request_stack, OsSearchFacetedTaxonomyQueryBuilder $os_search_faceted_taxonomy_query_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->osSearchFacetBuilder = $os_search_facet_builder;
    $this->searchQueryBuilder = $os_search_query_builder;
    $this->requestStack = $request_stack;
    $this->searchFacetedTaxonoQueryBuilder = $os_search_faceted_taxonomy_query_builder;
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
      $container->get('request_stack'),
      $container->get('os_search.os_search_faceted_taxonomy_query_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $route_name = 'os_search.app_global';
    // Load search page.
    $query = $this->searchFacetedTaxonoQueryBuilder->getQuery();
    $query->keys('');

    // Dependent filters.
    $this->searchQueryBuilder->queryBuilder($query);

    $field_id = 'custom_taxonomy';
    $field_label = $block_content->get('field_widget_title')->value ?? '';
    $field_app_content = $block_content->get('field_content_type')->value ?? '';
    $vocab_order_by = $block_content->get('field_vocabulary_order')->value ?? '';
    $term_order_by = $block_content->get('field_term_order')->value ?? '';
    $class = $block_content->get('field_display_styles')->value ?? '';
    $need_count = $block_content->get('field_show_number_of_posts')->value == 1 ? TRUE : FALSE;
    $vocab_filter = $block_content->get('field_vocabularies')->getValue() ?? [];
    $selected_app = $block_content->get('field_content_type')->value ?? '';

    $buckets = $this->osSearchFacetBuilder->getFacetBuckets($field_id, $query);
    $this->osSearchFacetBuilder->prepareFacetLabels($buckets, $field_id);
    $this->osSearchFacetBuilder->prepareFacetLinks($buckets, $field_id);

    $vocab_list = $this->searchFacetedTaxonoQueryBuilder->prepareFacetVocaulbaries($selected_app, $vocab_filter, $vocab_order_by, $term_order_by, $buckets);

    $build[] = $this->renderableTaxonomyArray($vocab_list, $route_name, $field_id, $field_label, $field_app_content, $class, $need_count);

    if (empty($build)) {
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
   * @param string $field_app_content
   *   App type for links.
   * @param string $class
   *   Class for display styles.
   * @param bool $need_count
   *   For displaying count with facet link.
   *
   * @return array
   *   Widget build
   */
  private function renderableTaxonomyArray(array $buckets, $route_name, string $field_name, string $field_label, string $field_app_content, string $class, bool $need_count): array {

    foreach ($buckets as $term_list) {
      $vocab_name = $term_list['name'];
      $build[] = $this->renderableArray($term_list['children'], $route_name, $field_name, $field_label, $vocab_name, $field_app_content, $class, $need_count);
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
   * @param string $field_app_content
   *   App type for links .
   * @param string $class
   *   Class for display styles.
   * @param bool $need_count
   *   For displaying count with facet link.
   *
   * @return array
   *   Widget build
   */
  private function renderableArray(array $buckets, $route_name, string $field_name, string $field_label, string $header = NULL, string $field_app_content, string $class, bool $need_count): array {
    $items = [];
    $keys = $this->requestStack->getCurrentRequest()->attributes->get('keys');
    $filters = $this->requestStack->getCurrentRequest()->query->get('f') ?? [];

    $route_parameters['app'] = $field_app_content;
    foreach ($buckets as $bucket) {
      $item_label = isset($bucket['label']) ? $bucket['label'] : ucwords($bucket['key']);
      $item_label = is_array($item_label) ? reset($item_label) : $item_label;
      $route_parameters['f'] = array_merge($filters, $bucket['query']);
      $route_parameters['keys'] = $keys;

      $path = Url::fromRoute($route_name, $route_parameters);

      if ($need_count) {
        if (isset($bucket['doc_count'])) {
          $items[] = Link::fromTextAndUrl($this->t('@label (@count)', ['@label' => $item_label, '@count' => $bucket['doc_count']]), $path)->toString();
        }
        else {
          $querys = isset($bucket['query']) ? $bucket['query'] : [];
          foreach ($querys as $key => $query) {
            if ($query == $bucket['filter']) {
              unset($querys[$key]);
            }
          }

          $path = Url::fromRoute($route_name, $route_parameters);
          $path_string = Link::fromTextAndUrl("(-)", $path)->toString();
          $items[] = $this->t('@path_string @label', ['@path_string' => $path_string, '@label' => $item_label]);
        }
      }
      else {
        $items[] = Link::fromTextAndUrl($this->t('@label', ['@label' => $item_label]), $path)->toString();
      }

    }

    if ($header) {
      $build[$field_name]['title'] = [
        '#markup' => '<h2 class=' . $class . '>' . $this->t('@header', ['@header' => $header]) . '</h2>',
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

}
