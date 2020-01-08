<?php

namespace Drupal\os_search\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\os_search\ListAppsHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;

/**
 * Class FilterDateWidget.
 *
 * @OsWidget(
 *   id = "filter_date_widget",
 *   title = @Translation("Filter By Date")
 * )
 */
class FilterDateWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * Search sort type.
   */
  const SORT_TYPE = ['title', 'type', 'date'];

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
   * Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, CurrentRouteMatch $route_match, ListAppsHelper $app_helper, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->routeMatch = $route_match;
    $this->appHelper = $app_helper;
    $this->requestStack = $request_stack;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
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
      $container->get('os_search.list_app_helper'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {

    $route_name = $this->routeMatch->getRouteName();
    // Declaration of array which will hold facets.
    $items = [];
    if (strpos($route_name, 'search_api_page') !== FALSE) {

      $index = Index::load('os_search_index');
      $query = $index->query();
      $query->keys('');
      $query->setOption('search_api_facets', [
        'custom_date' => [
          'field' => 'custom_date',
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
      $buckets = $facets['aggregations']['custom_date']['buckets'];

      $request = $this->requestStack->getCurrentRequest();

      $query_params = $request->query->all();

      // Declaration of array which will hold required query parameter.
      $gen_query_params = [];

      $year = $query_params['year'];
      $month = $query_params['month'];
      $day = $query_params['day'];
      $hour = $query_params['hour'];
      $minutes = $query_params['minutes'];

      // Generating links from custom_date facets.
      // Using timestamp for condition filter the records to create links.
      foreach ($buckets as $bundle) {
        $created_date = '';
        if (!isset($year)) {
          $created_date = date('Y', $bundle['key']);
          $gen_query_params = [
            'year' => $created_date,
          ];
        }
        elseif (!isset($month)) {
          $condition = $bundle['key'] >= strtotime('01-01-' . $year) &&
            $bundle['key'] <= strtotime('31-12-' . $year);
          if ($condition) {
            $created_date = date('M Y', $bundle['key']);
            $gen_query_params = [
              'month' => date('m', $bundle['key']),
            ];
          }

        }
        elseif (!isset($day)) {
          $condition = $bundle['key'] >= strtotime('01-' . $month . '-' . $year) &&
            $bundle['key'] < strtotime('31-' . $month . '-' . $year);
          if ($condition) {
            $created_date = date('M d, Y', $bundle['key']);
            $gen_query_params = [
              'day' => date('d', $bundle['key']),
            ];
          }

        }
        elseif (!isset($hour)) {
          $condition = $bundle['key'] >= strtotime($day . '-' . $month . '-' . $year . ' 00:00:00') &&
            $bundle['key'] <= strtotime($day . '-' . $month . '-' . $year . ' 23:59:59');
          if ($condition) {
            $created_date = date('h A', $bundle['key']);
            $gen_query_params = [
              'hour' => date('H', $bundle['key']),
            ];
          }
        }
        elseif (!isset($minutes)) {
          $condition = $bundle['key'] >= strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':00:00') &&
            $bundle['key'] <= strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':59:59');
          if ($condition) {
            $created_date = date('h A', $bundle['key']);
            $gen_query_params = [
              'minutes' => date('i', $bundle['key']),
            ];
          }

        }
        else {
          $condition = $bundle['key'] >= strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':' . $minutes . ':00') &&
            $bundle['key'] <= strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':' . $minutes . ':59');
          if ($condition) {
            $created_date = date('h:i A', $bundle['key']);
          }
        }
        $url = Url::fromRoute($route_name, array_merge($query_params, $gen_query_params));
        $items[] = Link::fromTextAndUrl($created_date, $url)->toString();
      }
    }
    $items = array_unique($items);
    $build['date_granular_list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#title' => $this->t('Filter By Post Date'),
      '#items' => $items,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
