<?php

namespace Drupal\Tests\os_rss\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;

/**
 * Custom Rss Functionality test.
 *
 * @group functional
 * @group cp
 */
class OsRssFunctionalTest extends OsExistingSiteTestBase {

  use CpTaxonomyTestTrait;

  /**
   * Test vocabulary id.
   *
   * @var string
   */
  protected $vid;

  /**
   * Test term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term1;

  /**
   * Test term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $term2;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Create test vocabulary.
    $this->vid = $this->randomMachineName();
    $this->createGroupVocabulary($this->group, $this->vid, ['node:blog']);
    $this->term1 = $this->createGroupTerm($this->group, $this->vid, []);
    $this->term2 = $this->createGroupTerm($this->group, $this->vid, []);
  }

  /**
   * Tests Rss listing page when viewed anonymous user.
   *
   * @covers \Drupal\os_rss\Controller\RssListingController
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRssListing(): void {

    $web = $this->assertSession();
    $this->visitViaVsite("rss", $this->group);

    // Check page title.
    $web->pageTextContains('RSS Feeds');

    // Check apps, publications and vocabulary section.
    $web->pageTextContains('Content Types');
    $web->pageTextContains('Categories');
    $web->pageTextContains($this->vid);

    // Check apps, publications and vocabulary rss links.
    $web->linkByHrefExists("{$this->groupAlias}/rss.xml?type=blog");
    $web->linkByHrefExists("{$this->groupAlias}/rss.xml?type=publications");
  }

  /**
   * Tests Rss listing page when viewed anonymous user.
   *
   * @covers \Drupal\os_rss\Controller\RssListingController
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRssFeeds(): void {

    $web = $this->assertSession();

    // Create blog node.
    $node1 = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $this->term1->id(),
      ],
      'status' => 1,
    ]);
    $this->group->addContent($node1, 'group_node:blog');

    // Visit apps rss feed.
    $this->visitViaVsite("rss.xml?type=blog", $this->group);
    $web->responseContains($node1->get('title')->value);
    $web->responseContains($node1->toUrl()->setAbsolute()->toString());

    $node2 = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $this->term2->id(),
      ],
      'status' => 1,
    ]);
    $this->group->addContent($node2, 'group_node:blog');

    $this->visitViaVsite("rss.xml?term=" . $this->term1->id(), $this->group);
    $web->responseContains($node1->get('title')->value);
    $web->responseContains($node1->toUrl()->setAbsolute()->toString());
    $this->visitViaVsite("rss.xml?term=" . $this->term2->id(), $this->group);
    $web->responseContains($node2->get('title')->value);
    $web->responseContains($node2->toUrl()->setAbsolute()->toString());

    // Create bibcite_reference.
    $title = $this->randomMachineName();
    $reference = $this->createReference([
      'html_title' => $title,
    ]);
    $this->group->addContent($reference, 'group_entity:bibcite_reference');

    // Visit bibcite_reference rss feed.
    $this->visitViaVsite("rss.xml?type=publications", $this->group);
    $web->responseContains($title);
    $web->responseContains($reference->toUrl()->setAbsolute()->toString());
  }

}
