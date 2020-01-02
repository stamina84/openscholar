<?php

namespace Drupal\os_search\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\os_search\ListAppsHelper;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class FilterPostWidget.
 *
 * @OsWidget(
 *   id = "filter_post_widget",
 *   title = @Translation("Filter By Post Type")
 * )
 */
class FilterPostWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Current User service.
   *
   * @var \Drupal\os_search\ListAppsHelper
   */
  protected $appHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, CurrentRouteMatch $route_match, ListAppsHelper $app_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->routeMatch = $route_match;
    $this->appHelper = $app_helper;
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
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('os_search.list_app_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {

    $route_name = $this->routeMatch->getRouteName();
    if (strpos($route_name, 'search_api_page') !== FALSE) {
      $index = Index::load('os_search_index');
      $query = $index->query();
      $query->keys('');
      $query->setOption('search_api_facets', [
        'custom_search_bundle' => [
          'field' => 'custom_search_bundle',
          'limit' => 90,
          'operator' => 'OR',
          'min_count' => 1,
          'missing' => FALSE,
        ],
      ]);
      $query->sort('search_api_relevance', 'DESC');
      $results = $query->execute();
      $facets = $results->getExtraData('elasticsearch_response', []);

      // Get indexed bundle types.
      $buckets = $facets['aggregations']['custom_search_bundle']['buckets'];

      // Get all available apps.
      $titles = $this->appHelper->getAppLists();

      foreach ($buckets as $bundle) {
        $url = Url::fromRoute($route_name, ['f[0]' => 'custom_bundle_text:' . $bundle['key']]);
        $title = $this->t('@app_title (@count)', ['@app_title' => $titles[$bundle['key']], '@count' => $bundle['doc_count']]);
        $items[] = Link::fromTextAndUrl($title, $url)->toString();
      }

      $build['filter-post-list'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t('Filter By Post Type'),
        '#items' => $items,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

}
