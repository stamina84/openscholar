<?php

namespace Drupal\os_widgets\Helper;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper class for merging views with different entity types.
 */
class ListOfPostsWidgetHelper implements ListOfPostsWidgetHelperInterface {

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * ListOfPostsWidgetHelper constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Connection instance.
   */
  public function __construct(Connection $database) {
    $this->connection = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getResults(array $fieldData, array $nodesList = NULL, array $pubList = NULL, array $tids = NULL) : array {
    /** @var \Drupal\Core\Database\Query\SelectInterface $nodeQuery */
    $nodeQuery = $this->getNodeQuery($fieldData, $nodesList, $tids);
    /** @var \Drupal\Core\Database\Query\SelectInterface $pubQuery */
    $pubQuery = $this->getPublicationQuery($fieldData, $pubList, $tids);

    // Union of two queries so that we can sort them as one. Join will not work
    // in our case.
    $query = $nodeQuery->union($pubQuery, 'UNION ALL');

    if ($fieldData['sortedBy'] === 'sort_newest') {
      $query->orderBy('created', 'DESC');
    }
    elseif ($fieldData['sortedBy'] === 'sort_oldest') {
      $query->orderBy('created', 'ASC');
    }
    elseif ($fieldData['sortedBy'] === 'sort_alpha') {
      $query->orderBy('title', 'ASC');
    }
    elseif ($fieldData['sortedBy'] === 'sort_random') {
      $pubQuery->addExpression('RAND()', 'random_field');
      $query->orderRandom();
    }
    return $query->execute()->fetchAll();
  }

  /**
   * Builds the node query.
   *
   * @param array $fieldData
   *   Fields Data.
   * @param array|null $nodesList
   *   Nodes list.
   * @param array|null $tids
   *   Term list.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  protected function getNodeQuery(array $fieldData, array $nodesList = NULL, array $tids = NULL) : SelectInterface {
    // Filter nodes based on vsite nids and taxonomy terms.
    /** @var \Drupal\Core\Database\Query\SelectInterface $nodeQuery */
    $nodeQuery = $this->connection->select('node_field_data', 'nfd');
    $nodeQuery->fields('nfd', ['nid', 'created', 'title', 'type'])
      ->condition('nid', $nodesList, 'IN');
    if ($tids) {
      $nodeQuery->join('node__field_taxonomy_terms', 'nftm', 'nfd.nid = nftm.entity_id');
      $nodeQuery->condition('field_taxonomy_terms_target_id', $tids, 'IN');
    }

    // If events node is selected then check if only upcoming or past events
    // need to be shown.
    if ($fieldData['contentType'] === 'events' && $fieldData['showEvents'] !== 'all_events') {
      $to_keep = NULL;
      $eventQuery = clone $nodeQuery;
      $currentTime = new DrupalDateTime('now');
      $eventQuery->join('node__field_recurring_date', 'nfrd', 'nfd.nid = nfrd.entity_id');
      $eventQuery->addField('nfrd', 'field_recurring_date_value');
      $eventResults = $eventQuery->execute()->fetchAll();
      foreach ($eventResults as $eventNode) {
        $dateTime = new DrupalDateTime($eventNode->field_recurring_date_value);
        switch ($fieldData['showEvents']) {
          case 'upcoming_events':
            if ($currentTime >= $dateTime) {
              continue;
            }
            $to_keep[] = $eventNode->nid;
            break;

          case 'past_events':
            if ($currentTime >= $dateTime) {
              $to_keep[] = $eventNode->nid;
            }
        }
      }
      $nodeQuery->condition('nid', $to_keep, 'IN');
    }
    return $nodeQuery;
  }

  /**
   * Builds the publication query.
   *
   * @param array $fieldData
   *   Fields data.
   * @param array|null $pubList
   *   Publication List.
   * @param array|null $tids
   *   Term ids.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  protected function getPublicationQuery(array $fieldData, array $pubList = NULL, array $tids = NULL) : SelectInterface {
    // Filter publications based on vsite ids and taxonomy terms.
    /** @var \Drupal\Core\Database\Query\SelectInterface $pubQuery */
    $pubQuery = $this->connection->select('bibcite_reference', 'pub');
    $pubQuery->fields('pub', ['id', 'created', 'title', 'type'])
      ->condition('id', $pubList, 'IN');
    if ($tids) {
      $pubQuery->join('bibcite_reference__field_taxonomy_terms', 'pubftm', 'pub.id = pubftm.entity_id');
      $pubQuery->condition('field_taxonomy_terms_target_id', $tids, 'IN');
    }

    // Check if only certain publication types are to be displayed.
    if ($fieldData['contentType'] === 'publication') {
      $pubQuery->condition('type', $fieldData['publicationTypes'], 'IN');
    }
    return $pubQuery;
  }

}
