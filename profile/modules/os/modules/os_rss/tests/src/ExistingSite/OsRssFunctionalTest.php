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
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');

    // Create test vocabulary.
    $this->vid = $this->randomMachineName();
    $this->createGroupVocabulary($this->group, $this->vid);
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

    // Check apps, publications and vocabulary rss links.
    $web->linkByHrefExists("{$this->groupAlias}/rss.xml?type=blog");
    $web->linkByHrefExists("{$this->groupAlias}/rss.xml?type=artwork");
    $web->linkByHrefExists("{$this->groupAlias}/rss.xml?term=" . $this->vid);
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

    // Create taxonomy terms.
    $term1 = $this->createGroupTerm($this->group, $this->vid, []);
    $term2 = $this->createGroupTerm($this->group, $this->vid, []);

    // Visit term rss feeds.
    $this->visitViaVsite("rss.xml?term=" . $this->vid, $this->group);

    // Check Term label and url for vocabulary.
    $web->responseContains($term1->label());
    $web->responseContains($term1->toUrl()->setAbsolute()->toString());
    $web->responseContains($term2->label());
    $web->responseContains($term2->toUrl()->setAbsolute()->toString());

    // Create blog node.
    $node = $this->createNode([
      'type' => 'blog',
    ]);

    // Visit apps rss feed.
    $this->visitViaVsite("rss.xml?type=blog", $this->group);
    $web->responseContains($node->get('title')->value);
    $web->responseContains($node->toUrl()->setAbsolute()->toString());

    // Create bibcite_reference.
    $title = $this->randomMachineName();
    $reference = $this->createReference([
      'html_title' => $title,
    ]);

    // Visit bibcite_reference rss feed.
    $this->visitViaVsite("rss.xml?type=artwork", $this->group);
    $web->responseContains($title);
    $web->responseContains($reference->toUrl()->setAbsolute()->toString());
  }

}
