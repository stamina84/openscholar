<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use DateInterval;
use DateTime;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class LopHelperTest.
 *
 * @group kernel
 * @group widgets-3
 * @covers \Drupal\os_widgets\Helper\ListWidgetsHelper
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
   * @var \Drupal\os_widgets\Helper\ListWidgetsHelperInterface
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
    $this->lopHelper = $this->container->get('os_widgets.list_widgets_helper');

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
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids);
    $this->assertEquals('News', $results[0]->title);

    // Test Sticky takes preference.
    $presentation1 = $this->createNode([
      'type' => 'presentation',
      'title' => 'Presentation1',
      'field_presentation_date' => '20/06/2019',
      'sticky' => 1,
    ]);
    $nids = $this->nids;
    array_push($nids, $presentation1->id());
    $results = $this->lopHelper->getLopResults($data, $nids);
    $this->assertNotEmpty($results);
    $this->assertEquals('Presentation1', $results[0]->title);

    // Test oldest sort.
    $data['sortedBy'] = 'sort_oldest';
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids);
    $this->assertEquals('Publication1', $results[0]->title);

    // Test alphabetical sort.
    $data['sortedBy'] = 'sort_alpha';
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids);
    $this->assertEquals('Blog', $results[0]->title);

    // Test random sort.
    $data['sortedBy'] = 'sort_random';
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids);
    $this->assertNotEmpty($results);

    // Test News Date sort.
    $news1 = $this->createNode([
      'type' => 'news',
      'title' => 'News1',
      'field_date' => '20/06/2019',
    ]);
    $news2 = $this->createNode([
      'type' => 'news',
      'title' => 'News2',
      'field_date' => '20/07/2019',
    ]);
    $data['contentType'] = 'news';
    $data['sortedBy'] = 'news_date';
    $results = $this->lopHelper->getLopResults($data, [$news1->id(), $news2->id()]);
    $this->assertNotEmpty($results);
    $this->assertEquals('News2', $results[0]->title);

    // Test Year Of Publication sort.
    $ref1 = $this->createReference([
      'type' => 'artwork',
      'html_title' => 'Publication1',
      'bibcite_year' => 2019,
    ]);
    $ref2 = $this->createReference([
      'type' => 'book',
      'html_title' => 'Publication2',
      'bibcite_year' => 2017,
    ]);
    $data['contentType'] = 'publications';
    $data['sortedBy'] = 'year_of_publication';
    $results = $this->lopHelper->getLopResults($data, NULL, [$ref1->id(), $ref2->id()]);
    $this->assertNotEmpty($results);
    $this->assertEquals($ref1->id(), $results[0]->nid);

    // Test Recently Presented sort.
    $presentation1 = $this->createNode([
      'type' => 'presentation',
      'title' => 'Presentation1',
      'field_presentation_date' => '20/06/2019',
    ]);
    $presentation2 = $this->createNode([
      'type' => 'presentation',
      'title' => 'Presentation2',
      'field_presentation_date' => '20/07/2019',
    ]);
    $data['contentType'] = 'presentation';
    $data['sortedBy'] = 'recently_presented';
    $results = $this->lopHelper->getLopResults($data, [$presentation1->id(), $presentation2->id()]);
    $this->assertNotEmpty($results);
    $this->assertEquals('Presentation2', $results[0]->title);

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
      $tids[$term->bundle()][] = $term->id();
    }

    // Tests when no terms attached no results are returned.
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids, $tids);
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
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids, $tids);
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
        'timezone' => 'America/New_York',
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
        'timezone' => 'America/New_York',
        'infinite' => 0,
      ],
    ]);
    $this->group->addContent($eventNode1, 'group_node:events');
    $this->group->addContent($eventNode2, 'group_node:events');

    // Test upcoming events that only future event is returned.
    $results = $this->lopHelper->getLopResults($data, [$eventNode1->id(), $eventNode2->id()]);
    $this->assertEquals($eventNode1->id(), $results[0]->nid);

    // Test past events that only past is returned.
    $data['showEvents'] = 'past_events';
    $results = $this->lopHelper->getLopResults($data, [$eventNode1->id(), $eventNode2->id()]);
    $this->assertEquals($eventNode2->id(), $results[0]->nid);

    // Test all events that all are returned.
    $data['showEvents'] = 'all_events';
    $results = $this->lopHelper->getLopResults($data, [$eventNode1->id(), $eventNode2->id()]);
    $this->assertCount(2, $results);
  }

  /**
   * Tests upcoming events filtering and sorting.
   *
   * @throws \Exception
   */
  public function testEventsUpcomingFilteringSorting(): void {

    $eventsArray = $this->createVsiteEvents($this->group);

    // Test 30 minutes after event start.
    $data['contentType'] = 'events';
    $data['sortedBy'] = 'sort_event_asc';
    $data['showEvents'] = 'upcoming_events';
    $data['eventExpireAppear'] = 'after_event_start';
    $results = $this->lopHelper->getLopResults($data, [$eventsArray[0]->id(), $eventsArray[1]->id()]);
    $this->assertCount(1, $results);
    $this->assertEquals($eventsArray[0]->id(), $results[0]->nid);

    // Test End of Day.
    $data['eventExpireAppear'] = 'end_of_day';
    $results = $this->lopHelper->getLopResults($data, [$eventsArray[1]->id(), $eventsArray[2]->id()]);
    $this->assertCount(1, $results);
    $this->assertEquals($eventsArray[1]->id(), $results[0]->nid);

    // Test End of Event.
    $data['eventExpireAppear'] = 'end_of_event';
    $results = $this->lopHelper->getLopResults($data, [$eventsArray[1]->id(), $eventsArray[2]->id()]);
    $this->assertCount(1, $results);
    $this->assertEquals($eventsArray[2]->id(), $results[0]->nid);
  }

  /**
   * Tests past events filtering and sorting.
   *
   * @throws \Exception
   */
  public function testEventsPastFilteringSorting(): void {

    $eventsArray = $this->createVsiteEvents($this->group);

    // Test 30 minutes after event start.
    $data['contentType'] = 'events';
    $data['sortedBy'] = 'sort_event_desc';
    $data['showEvents'] = 'past_events';
    $data['eventExpireAppear'] = 'after_event_start';
    $results = $this->lopHelper->getLopResults($data, [$eventsArray[0]->id(), $eventsArray[1]->id()]);
    $this->assertCount(1, $results);
    $this->assertEquals($eventsArray[1]->id(), $results[0]->nid);

    // Test End of Day.
    $data['eventExpireAppear'] = 'end_of_day';
    $results = $this->lopHelper->getLopResults($data, [$eventsArray[1]->id(), $eventsArray[2]->id()]);
    $this->assertCount(1, $results);
    $this->assertEquals($eventsArray[2]->id(), $results[0]->nid);

    // Test End of Event.
    $data['eventExpireAppear'] = 'end_of_event';
    $results = $this->lopHelper->getLopResults($data, [$eventsArray[1]->id(), $eventsArray[2]->id()]);
    $this->assertCount(1, $results);
    $this->assertEquals($eventsArray[1]->id(), $results[0]->nid);
  }

  /**
   * Test no Duplicates and (AND)/(OR) filtering by vocabs.
   */
  public function testLopHelperGetResultsVocabAndFiltering() : void {
    $data['sortedBy'] = 'sort_newest';
    $data['contentType'] = 'all';
    $data['publicationTypes'] = ['artwork', 'book'];

    $vocabulary2 = $this->createVocabulary();

    $term1 = $this->createTerm($this->vocabulary, ['name' => 'Lorem1']);
    $term2 = $this->createTerm($vocabulary2, ['name' => 'Lorem2']);
    $term3 = $this->createTerm($vocabulary2, ['name' => 'Lorem2']);

    $this->group->addContent($term1, 'group_entity:taxonomy_term');
    $this->group->addContent($term2, 'group_entity:taxonomy_term');
    $this->group->addContent($term3, 'group_entity:taxonomy_term');

    $blog_with_single_term = $this->createNode([
      'title' => 'Blog1',
      'type' => 'blog',
      'status' => 1,
      'field_taxonomy_terms' => [
        $term1->id(),
      ],
    ]);

    $blog_with_multiple_terms = $this->createNode([
      'title' => 'Blog2',
      'type' => 'blog',
      'status' => 1,
      'field_taxonomy_terms' => [
        $term1->id(),
        $term2->id(),
      ],
    ]);

    $blog_with_single_term2 = $this->createNode([
      'title' => 'Blog3',
      'type' => 'blog',
      'status' => 1,
      'field_taxonomy_terms' => [
        $term2->id(),
      ],
    ]);

    array_push($this->nids, $blog_with_single_term->id());
    array_push($this->nids, $blog_with_multiple_terms->id());
    array_push($this->nids, $blog_with_single_term2->id());
    $this->group->addContent($blog_with_single_term, 'group_node:blog');
    $this->group->addContent($blog_with_multiple_terms, 'group_node:blog');
    $this->group->addContent($blog_with_single_term2, 'group_node:blog');

    // Tests AND between Vocabs.
    foreach ([$term1, $term2] as $term) {
      $tids[$term->bundle()][] = $term->id();
    }
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids, $tids);
    // Assert No duplicates.
    $this->assertCount(1, $results);
    // Assert only nid with both vocab terms (AND) is returned.
    $this->assertEquals('Blog2', $results[0]->title);

    // Tests OR within a Vocab.
    foreach ([$term2, $term3] as $term) {
      $termIds[$term->bundle()][] = $term->id();
    }
    $results = $this->lopHelper->getLopResults($data, $this->nids, $this->pids, $termIds);
    // Assert both nids are returned confirming OR.
    $this->assertCount(2, $results);
  }

}
