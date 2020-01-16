<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;
use DateInterval;
use DateTime;

/**
 * Tests os_widgets module Taxonomy widget with contextual.
 *
 * @group functional-javascript
 * @group widgets
 */
class TaxonomyBlockContextualTest extends OsExistingSiteJavascriptTestBase {

  use CpTaxonomyTestTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $vsiteContextManager;

  /**
   * Test vocabulary id.
   *
   * @var string
   */
  protected $vid;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');

    $this->vid = $this->randomMachineName();
    $this->createGroupVocabulary($this->group, $this->vid);
    // Reset vocabulary allowed values.
    $config_vocab = $this->configFactory->getEditable('taxonomy.vocabulary.' . $this->vid);
    $config_vocab->set('allowed_vocabulary_reference_types', [
      'node:blog',
      'node:news',
    ])->save(TRUE);

    // Block content without empty terms.
    $block_content = $this->createBlockContent([
      'type' => 'taxonomy',
      'field_taxonomy_show_empty_terms' => 1,
      'field_taxonomy_behavior' => [
        'contextual',
      ],
      'field_taxonomy_vocabulary' => [$this->vid],
      'field_taxonomy_tree_depth' => [0],
      'field_taxonomy_display_type' => ['classic'],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'sidebar_second');

    $group_admin = $this->createUser();
    $this->addGroupEnhancedMember($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Tests contextual on global page.
   */
  public function testTaxonomyContextualOnGlobalPage() {
    $web_assert = $this->assertSession();

    $term_blog = $this->createGroupTerm($this->group, $this->vid, []);
    $term_news = $this->createGroupTerm($this->group, $this->vid, []);
    $node = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term_blog,
      ],
    ]);
    $this->group->addContent($node, 'group_node:' . $node->bundle());
    $node = $this->createNode([
      'type' => 'news',
      'field_taxonomy_terms' => [
        $term_news,
      ],
    ]);
    $this->group->addContent($node, 'group_node:' . $node->bundle());
    $this->visitViaVsite('', $this->group);
    $web_assert->pageTextContains($term_blog->label());
    $web_assert->pageTextContains($term_news->label());
  }

  /**
   * Tests contextual on blog pages.
   */
  public function testTaxonomyContextualBlogPage() {
    $web_assert = $this->assertSession();

    $term_blog = $this->createGroupTerm($this->group, $this->vid, []);
    $term_other = $this->createGroupTerm($this->group, $this->vid, []);
    $node = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term_blog,
      ],
    ]);
    $this->group->addContent($node, 'group_node:' . $node->bundle());
    $this->visitViaVsite('blog', $this->group);
    $web_assert->pageTextContains($term_blog->label());
    $web_assert->pageTextNotContains($term_other->label());
    $this->visitViaVsite('node/' . $node->id(), $this->group);
    $web_assert->pageTextContains($term_blog->label());
    $web_assert->pageTextNotContains($term_other->label());
  }

  /**
   * Tests contextual on event pages, show only upcoming.
   */
  public function testTaxonomyContextualEventPageUpcoming() {
    $web_assert = $this->assertSession();

    $term1 = $this->createGroupTerm($this->group, $this->vid, []);
    $term2 = $this->createGroupTerm($this->group, $this->vid, []);
    // Create both upcoming and past events.
    $new_datetime = new DateTime();
    $date_interval = new DateInterval('P2D');
    $new_datetime->add($date_interval);
    $date1 = $new_datetime->format("Y-m-d\TH:i:s");
    $new_datetime->add($date_interval);
    $date2 = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNodeUpcoming = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date1,
        'end_value' => $date2,
        'timezone' => 'America/New_York',
        'infinite' => 0,
      ],
      'field_taxonomy_terms' => [
        $term1,
      ],
    ]);
    $new_datetime = new DateTime();
    $date_interval = new DateInterval('P2D');
    $date_interval->invert = 1;
    $new_datetime->add($date_interval);
    $date1 = $new_datetime->format("Y-m-d\TH:i:s");
    $new_datetime->add($date_interval);
    $date2 = $new_datetime->format("Y-m-d\TH:i:s");
    $eventNodePast = $this->createNode([
      'type' => 'events',
      'status' => 1,
      'field_recurring_date' => [
        'value' => $date2,
        'end_value' => $date1,
        'timezone' => 'America/New_York',
        'infinite' => 0,
      ],
      'field_taxonomy_terms' => [
        $term1,
        $term2,
      ],
    ]);
    $this->group->addContent($eventNodeUpcoming, 'group_node:events');
    $this->group->addContent($eventNodePast, 'group_node:events');
    $this->visitViaVsite('node/' . $eventNodeUpcoming->id(), $this->group);

    // Page should render only term 1 label wth count 1.
    $web_assert->pageTextContains($term1->label() . ' (1)');
    $web_assert->pageTextNotContains($term2->label());
    // Widget should link term to upcoming page with argument.
    $this->assertContains('/calendar/upcoming/' . $term1->id(), $this->getCurrentPageContent());
  }

  /**
   * Tests contextual on publication pages.
   */
  public function testTaxonomyContextualPublicationsPage() {
    $web_assert = $this->assertSession();

    $term_pub = $this->createGroupTerm($this->group, $this->vid, []);
    $term_other = $this->createGroupTerm($this->group, $this->vid, []);
    $reference = $this->createReference([
      'field_taxonomy_terms' => [
        $term_pub,
      ],
    ]);
    $this->group->addContent($reference, 'group_entity:bibcite_reference');
    $this->visitViaVsite('publications', $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($term_pub->label());
    $web_assert->pageTextNotContains($term_other->label());
    $this->visitViaVsite('bibcite/reference/' . $reference->id(), $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($term_pub->label());
    $web_assert->pageTextNotContains($term_other->label());
  }

}
