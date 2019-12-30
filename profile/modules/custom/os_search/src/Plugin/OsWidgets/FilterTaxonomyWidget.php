<?php

namespace Drupal\os_search\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\search_api\Entity\Index;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class SearchSortWidget.
 *
 * @OsWidget(
 *   id = "filter_taxonomy_widget",
 *   title = @Translation("Filter By Taxonomy")
 * )
 */
class FilterTaxonomyWidget extends OsWidgetsBase implements OsWidgetsInterface {
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
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, CurrentRouteMatch $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->routeMatch = $route_match;
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
      $container->get('current_route_match')
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
      $query->sort('search_api_relevance', 'DESC');
      $results = $query->execute();
      $facets = $results->getExtraData('elasticsearch_response', []);

      // Get indexed bundle types.
      $buckets = $facets['aggregations']['custom_taxonomy']['buckets'];
      $vocabularies = Vocabulary::loadMultiple();

      $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
      foreach ($buckets as $bucket) {
        $term = $termStorage->load($bucket['key']);
        $name = $term->get('name')->value;

        $url = Url::fromRoute($route_name, ['f[0]' => 'custom_taxonomy_text:' . $bucket['key']]);
        $title = $this->t('@app_title (@count)', ['@app_title' => $name, '@count' => $bucket['doc_count']]);
        $vname = $vocabularies[$term->getVocabularyId()]->get('name');
        $items[$vname][] = Link::fromTextAndUrl($title, $url)->toString();
      }

      $build['filter-taxonomy-list'] = [
        '#theme' => 'os_filter_taxonomy_widget',
        '#header' => $this->t('Filter By Post Type'),
        '#items' => $items,
        '#cache' => [
          'max-age' => 0,
        ],

      ];
    }
  }

}
