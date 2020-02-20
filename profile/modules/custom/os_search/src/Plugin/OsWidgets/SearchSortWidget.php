<?php

namespace Drupal\os_search\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class SearchSortWidget.
 *
 * @OsWidget(
 *   id = "search_sort_widget",
 *   title = @Translation("Sort By")
 * )
 */
class SearchSortWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * Search sort type.
   */
  const SORT_TYPE = ['relevance', 'title', 'type', 'date'];

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, CurrentRouteMatch $route_match, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->routeMatch = $route_match;
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
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {

    $route_name = $this->routeMatch->getRouteName();
    if (strpos($route_name, 'search_api_page') !== FALSE) {
      $request = $this->requestStack->getCurrentRequest();
      $params = $request->query->all();
      $keys = $request->attributes->get('keys');
      $sort = isset($params['sort']) ? $params['sort'] : FALSE;
      $sort_direction = isset($params['dir']) ? $params['dir'] : FALSE;

      if ($keys && trim($keys) != '') {
        $params['keys'] = $keys;
      }

      $sort_types = self::SORT_TYPE;

      foreach ($sort_types as $sort_type) {
        // Prepare params from beginning.
        $sort_params = $params;
        $class_array = [];

        $sort_params['sort'] = $sort_type;
        $sort_params['dir'] = ($sort_type == 'date') ? 'DESC' : 'ASC';
        $class_array[] = $sort_params['dir'];

        if ($sort && $sort_type == $sort) {
          // Add active class to chosen sort.
          $class_array[] = 'active';

          // Reverse direction only when sort is selected.
          if ($sort_direction) {
            $sort_params['dir'] = ($sort_direction == 'ASC') ? 'DESC' : 'ASC';
          }
        }

        // Search API by default sort by relevance.
        if ($sort_type == 'relevance') {
          unset($sort_params['sort'], $sort_params['dir']);
        }

        // Generate Url from route & params.
        $url = Url::fromRoute($route_name, $sort_params);

        // Add relevant classes.
        $url->setOptions([
          'attributes' => [
            'class' => $class_array,
          ],
        ]);

        // Generate link html.
        $items[] = Link::fromTextAndUrl($this->t('@text', ['@text' => ucfirst($sort_type)]), $url)->toString();
      }

      $build['link-list'] = [
        '#theme' => 'item_list__search_widget',
        '#list_type' => 'ul',
        '#title' => $this->t('Sort by'),
        '#items' => $items,
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
  }

}
