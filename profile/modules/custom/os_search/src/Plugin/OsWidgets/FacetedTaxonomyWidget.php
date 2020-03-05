<?php

namespace Drupal\os_search\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\CurrentRouteMatch;

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

    $build['#block_content'] = $block_content;
  }

}
