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

    // If events node is selected then check if only upcoming or past events
    // need to be shown with additional sorting.
    if ($fieldData['contentType'] === 'events') {
      $eventQuery = $this->getEventsQuery($fieldData, $nodesList);
      $eventQuery = $this->sortQuery($eventQuery, $fieldData['sortedBy']);
      return $eventQuery->execute()->fetchAll();
    }
    if ($fieldData['contentType'] === 'publications' && $fieldData['sortedBy'] === 'year_of_publication') {
      $data = [];
      $pubQuery = $this->getPublicationQuery($fieldData, $pubList, $tids, TRUE);
      $pubQuery = $this->sortQuery($pubQuery, $fieldData['sortedBy']);
      $results = $pubQuery->execute()->fetchAll();
      // Some modifications needed to resultant array for proper rendering.
      foreach ($results as $result) {
        $item = new \stdClass();
        $item->nid = $result->id;
        $item->type = $result->type;
        $data[] = $item;
      }
      return $data;
    }
    if ($fieldData['contentType'] === 'news' && $fieldData['sortedBy'] === 'news_date') {
      $newsQuery = $this->getNodeQuery($nodesList, $tids, $fieldData['contentType']);
      $newsQuery = $this->sortQuery($newsQuery, $fieldData['sortedBy']);
      return $newsQuery->execute()->fetchAll();
    }
    if ($fieldData['contentType'] === 'presentation' && $fieldData['sortedBy'] === 'recently_presented') {
      $presentQuery = $this->getNodeQuery($nodesList, $tids, $fieldData['contentType']);
      $presentQuery = $this->sortQuery($presentQuery, $fieldData['sortedBy']);
      return $presentQuery->execute()->fetchAll();
    }
    /** @var \Drupal\Core\Database\Query\SelectInterface $nodeQuery */
    $nodeQuery = $this->getNodeQuery($nodesList, $tids);
    /** @var \Drupal\Core\Database\Query\SelectInterface $pubQuery */
    $pubQuery = $this->getPublicationQuery($fieldData, $pubList, $tids);

    // Union of two queries so that we can sort them as one. Join will not work
    // in our case.
    $query = $nodeQuery->union($pubQuery, 'UNION ALL');

    $query = $this->sortQuery($query, $fieldData['sortedBy'], $pubQuery);
    return $query->execute()->fetchAll();
  }

  /**
   * Apply suitable sorting to the query or union of queries.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   Main query.
   * @param string $sortedBy
   *   The type of sorting needed.
   * @param \Drupal\Core\Database\Query\SelectInterface|null $pubQuery
   *   Publications query if included.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Query with sorting applied.
   */
  protected function sortQuery(SelectInterface $query, string $sortedBy, SelectInterface $pubQuery = NULL) : SelectInterface {
    // Sticky should take preference.
    $query->orderBy('sticky', 'DESC');
    if ($sortedBy === 'sort_newest') {
      $query->orderBy('created', 'DESC');
    }
    elseif ($sortedBy === 'sort_oldest') {
      $query->orderBy('created', 'ASC');
    }
    elseif ($sortedBy === 'sort_alpha') {
      $query->orderBy('title', 'ASC');
    }
    elseif ($sortedBy === 'sort_random') {
      if ($pubQuery) {
        $pubQuery->addExpression('RAND()', 'random_field');
      }
      $query->orderRandom();
    }
    elseif ($sortedBy === 'sort_event_asc') {
      $query->orderBy('field_recurring_date_value', 'ASC');
    }
    elseif ($sortedBy === 'sort_event_desc') {
      $query->orderBy('field_recurring_date_value', 'DESC');
    }
    elseif ($sortedBy === 'year_of_publication') {
      $query->orderBy('bibcite_year', 'DESC');
    }
    elseif ($sortedBy === 'news_date') {
      $query->orderBy('field_date_value', 'DESC');
    }
    elseif ($sortedBy === 'recently_presented') {
      $query->orderBy('field_presentation_date_value', 'DESC');
    }
    return $query;
  }

  /**
   * Builds the node query.
   *
   * @param array|null $nodesList
   *   Nodes list.
   * @param array|null $tids
   *   Term list.
   * @param string|null $type
   *   Type of node.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  protected function getNodeQuery(array $nodesList = NULL, array $tids = NULL, string $type = NULL) : SelectInterface {
    // Filter nodes based on vsite nids and taxonomy terms.
    /** @var \Drupal\Core\Database\Query\SelectInterface $nodeQuery */
    $nodeQuery = $this->connection->select('node_field_data', 'nfd');
    $nodeQuery->fields('nfd', ['nid', 'created', 'title', 'type', 'sticky'])
      ->condition('nid', $nodesList, 'IN');

    if ($tids) {
      // And condition for vocabs.
      foreach ($tids as $vid => $terms) {
        $nodeQuery->join('node__field_taxonomy_terms', $vid, "nfd.nid = $vid.entity_id");
        $nodeQuery->condition("$vid.field_taxonomy_terms_target_id", $terms, 'IN');
      }
    }
    if ($type === 'presentation') {
      $nodeQuery->join('node__field_presentation_date', 'nfpd', "nfd.nid = nfpd.entity_id");
      $nodeQuery->addField('nfpd', 'field_presentation_date_value');
      $nodeQuery->condition('nfpd.field_presentation_date_value', '', '!=');
    }
    if ($type === 'news') {
      $nodeQuery->join('node__field_date', 'nfdate', "nfd.nid = nfdate.entity_id");
      $nodeQuery->addField('nfdate', 'field_date_value');
      $nodeQuery->condition('nfdate.field_date_value', '', '!=');
    }
    return $nodeQuery->distinct(TRUE);
  }

  /**
   * Get Events node query.
   *
   * @param array $fieldData
   *   Field data for settings.
   * @param array|null $nodesList
   *   List of nids.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   *
   * @throws \Exception
   */
  protected function getEventsQuery(array $fieldData, array $nodesList = NULL) {
    $eventQuery = $this->getNodeQuery($nodesList);
    $to_keep = NULL;
    $currentTime = new DrupalDateTime('now');
    // Used date_recur__node__field_recurring_date table in place of
    // node__field_recurring_date table to accommodate repeating events.
    $eventQuery->join('date_recur__node__field_recurring_date', 'nfrd', 'nfd.nid = nfrd.entity_id');
    $eventQuery->addField('nfrd', 'field_recurring_date_value');
    $eventQuery->addField('nfrd', 'field_recurring_date_end_value');

    if ($fieldData['showEvents'] !== 'all_events') {
      $eventResults = $eventQuery->execute()->fetchAll();
      foreach ($eventResults as $eventNode) {
        $startDateTime = new DrupalDateTime($eventNode->field_recurring_date_value);
        $endDateTime = new DrupalDateTime($eventNode->field_recurring_date_end_value);
        switch ($fieldData['showEvents']) {
          case 'upcoming_events':
            if ($fieldData['sortedBy'] === 'sort_event_asc') {
              if ($fieldData['eventExpireAppear'] === 'after_event_start') {
                $startDateTime->add(new \DateInterval('PT30M'));
                if ($startDateTime < $currentTime) {
                  continue;
                }
              }
              elseif ($fieldData['eventExpireAppear'] === 'end_of_day') {
                $startDateTime->modify('tomorrow -1 second');
                if ($startDateTime < $currentTime) {
                  continue;
                }
              }
              elseif ($fieldData['eventExpireAppear'] === 'end_of_event') {
                if ($endDateTime < $currentTime) {
                  continue;
                }
                $to_keep[] = $eventNode->field_recurring_date_value;
              }
            }
            if ($currentTime > $startDateTime) {
              continue;
            }
            $to_keep[] = $eventNode->field_recurring_date_value;
            break;

          case 'past_events':
            if ($fieldData['sortedBy'] === 'sort_event_desc') {
              if ($fieldData['eventExpireAppear'] === 'after_event_start') {
                $startDateTime->add(new \DateInterval('PT30M'));
                if ($startDateTime > $currentTime) {
                  continue;
                }
              }
              elseif ($fieldData['eventExpireAppear'] === 'end_of_day') {
                $startDateTime->modify('tomorrow -1 second');
                if ($startDateTime > $currentTime) {
                  continue;
                }
              }
              elseif ($fieldData['eventExpireAppear'] === 'end_of_event') {
                if ($endDateTime > $currentTime) {
                  continue;
                }
                $to_keep[] = $eventNode->field_recurring_date_value;
              }
            }
            if ($currentTime < $startDateTime) {
              continue;
            }
            $to_keep[] = $eventNode->field_recurring_date_value;
            break;
        }
      }
      $eventQuery->condition('field_recurring_date_value', $to_keep, 'IN');
    }
    return $eventQuery;
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
   * @param bool $sortYear
   *   If sorting is by publication year.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  protected function getPublicationQuery(array $fieldData, array $pubList = NULL, array $tids = NULL, bool $sortYear = FALSE) : SelectInterface {
    // Filter publications based on vsite ids and taxonomy terms.
    /** @var \Drupal\Core\Database\Query\SelectInterface $pubQuery */
    $pubQuery = $this->connection->select('bibcite_reference', 'pub');
    $pubQuery->fields('pub', ['id', 'created', 'title', 'type']);
    $pubQuery->addField('pub', 'is_sticky', 'sticky');
    $pubQuery->condition('id', $pubList, 'IN');
    if ($tids) {
      // And condition for vocabs.
      foreach ($tids as $vid => $terms) {
        $pubQuery->join('bibcite_reference__field_taxonomy_terms', $vid, "pub.id = $vid.entity_id");
        $pubQuery->condition("$vid.field_taxonomy_terms_target_id", $terms, 'IN');
      }
    }
    // Check if only certain publication types are to be displayed.
    if ($fieldData['contentType'] === 'publications') {
      $pubQuery->condition('type', $fieldData['publicationTypes'], 'IN');
    }
    if ($sortYear) {
      $pubQuery->addField('pub', 'bibcite_year');
    }
    return $pubQuery->distinct(TRUE);
  }

}
