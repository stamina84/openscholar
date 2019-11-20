<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use DateInterval;
use DateTime;

/**
 * Class LopHelperTest.
 *
 * @group kernel
 * @group widgets-3
 * @covers \Drupal\os_widgets\Helper\ListOfPostsWidgetHelper
 */
class LopHelperTest extends OsWidgetsExistingSiteTestBase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\ListOfPostsWidget
   */
  protected $lopWidget;

  /**
   * View builder service.
   *
   * @var \Drupal\os_widgets\Helper\ListOfPostsWidgetHelperInterface
   */
  protected $lopHelper;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * Node ids.
   *
   * @var array
   */
  protected $nids;

  /**
   * Publication ids.
   *
   * @var array
   */
  protected $pids;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->lopHelper = $this->container->get('os_widgets.lop_helper');

    // Activate vsite and create a vocabulary.
    $this->vsiteContextManager->activateVsite($this->group);
    $this->vocabulary = $this->createVocabulary();
    $this->config = $this->container->get('config.factory');
    $config_vocab = $this->config->getEditable('taxonomy.vocabulary.' . $this->vocabulary->id());
    $config_vocab->set('allowed_vocabulary_reference_types',
      ['bibcite_reference:*', 'node:blog', 'node:news'])->save(TRUE);

    // Create entities and get ids.
    $entities = $this->createVsiteContent($this->group);
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      if ($entity->getEntityTypeId() !== 'bibcite_reference') {
        $this->nids[] = $entity->id();
      }
      else {
        $this->pids[] = $entity->id();
      }
    }
  }

  /**
   * Test Get results sorting.
   */
  public function testLopHelperGetResultsSorting() : void {

    $data['sortedBy'] = 'sort_newest';
    $data['contentType'] = 'all';
    $data['publicationTypes'] = ['artwork', 'book'];

    // Tests Newest Sort.
    $results = $this->lopHelper->getResults($data, $this->nids, $this->pids);
    $this->assertEquals('News', $results[0]->title);

    // Test oldest sort.
    $data['sortedBy'] = 'sort_oldest';
    $results = $this->lopHelper->getResults($data, $this->nids, $this->pids);
    $this->assertEquals('Publication1', $results[0]->title);

    // Test alphabetical sort.
    $data['sortedBy'] = 'sort_alpha';
    $results = $this->lopHelper->getResults($data, $this->nids, $this->pids);
    $this->assertEquals('Blog', $results[0]->title);

    // Test random sort.
    $data['sortedBy'] = 'sort_random';
    $results = $this->lopHelper->getResults($data, $this->nids, $this->pids);
    $this->assertNotEmpty($results);
  }

  /**
   * Test filtering by terms.
   */
  public function testLopHelperGetResultsTermFiltering() : void {
    $data['sortedBy'] = 'sort_newest';
    $data['contentType'] = 'all';
    $data['publicationTypes'] = ['artwork', 'book'];

    $term1 = $this->createTerm($this->vocabulary, ['name' => 'Lorem1']);
    $term2 = $this->createTerm($this->vocabulary, ['name' => 'Lorem2']);

    $this->group->addContent($term1, 'group_entity:taxonomy_term');
    $this->group->addContent($term2, 'group_entity:taxonomy_term');

    foreach ([$term1, $term2] as $term) {
      $tids[] = $term->id();
    }

    // Tests when no terms attached no results are returned.
    $results = $this->lopHelper->getResults($data, $this->nids, $this->pids, $tids);
    $this->assertEmpty($results);

    // Tests when a term is attached this node is returned.
    $blog_with_term = $this->createNode([
      'type' => 'blog',
      'status' => 1,
      'field_taxonomy_terms' => [
        $term1->id(),
      ],
    ]);
    array_push($this->nids, $blog_with_term->id());
    $this->group->addContent($blog_with_term, 'group_node:blog');
    $results = $this->lopHelper->getResults($data, $this->nids, $this->pids, $tids);
    $this->assertCount(1, $results);
  }

  /**
   * Test special cases for events.
   *
   * @throws \Exception
   */
  public function testLopHelperGetResultsEvents() : void {

    $data['sortedBy'] = 'sort_alpha';
    $data['contentType'] = 'events';
    $data['showEvents'] = 'upcoming_events';

    // Create both upcoming and past events.
    $new_datetime = new DateTime();
    $date_interval = new DateInterval('P2D');
    $new_datetime->add($date_interval);
    $date = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNode1 = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date,
        'end_value' => $date,
        'timezone' => 'America/Anguilla',
        'infinite' => 0,
      ],
    ]);
    $new_datetime = new DateTime();
    $date_interval = new DateInterval('P2D');
    $date_interval->invert = 1;
    $new_datetime->add($date_interval);
    $date = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNode2 = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date,
        'end_value' => $date,
        'timezone' => 'America/Anguilla',
        'infinite' => 0,
      ],
    ]);
    $this->group->addContent($eventNode1, 'group_node:events');
    $this->group->addContent($eventNode2, 'group_node:events');

    // Test upcoming events that only future event is returned.
    $results = $this->lopHelper->getResults($data, [$eventNode1->id(), $eventNode2->id()]);
    $this->assertEquals($eventNode1->id(), $results[0]->nid);

    // Test past events that only past is returned.
    $data['showEvents'] = 'past_events';
    $results = $this->lopHelper->getResults($data, [$eventNode1->id(), $eventNode2->id()]);
    $this->assertEquals($eventNode2->id(), $results[0]->nid);

    // Test all events that all are returned.
    $data['showEvents'] = 'all_events';
    $results = $this->lopHelper->getResults($data, [$eventNode1->id(), $eventNode2->id()]);
    $this->assertCount(2, $results);
  }

}
