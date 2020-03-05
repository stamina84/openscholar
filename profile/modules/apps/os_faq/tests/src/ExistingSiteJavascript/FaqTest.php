<?php

namespace Drupal\Tests\os_faq\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;
use Drupal\views\Entity\View;

/**
 * FaqTest.
 *
 * @group functional-javascript
 * @group faq
 */
class FaqTest extends OsExistingSiteJavascriptTestBase {

  use CpTaxonomyTestTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Tests faq listing.
   */
  public function testFaqListing(): void {
    $web_assert = $this->assertSession();
    $faq = $this->createNode([
      'type' => 'faq',
    ]);
    $this->addGroupContent($faq, $this->group);
    $faq_unpublished = $this->createNode([
      'type' => 'faq',
      'status' => 0,
    ]);
    $this->addGroupContent($faq, $this->group);

    $this->visitViaVsite('faq', $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($faq->getTitle());
    $web_assert->pageTextNotContains($faq_unpublished->getTitle());
  }

  /**
   * Tests faq sticky listing.
   */
  public function testFaqStickyListing(): void {
    $faq1 = $this->createNode([
      'type' => 'faq',
    ]);
    $this->addGroupContent($faq1, $this->group);
    $faq2 = $this->createNode([
      'type' => 'faq',
    ]);
    $this->addGroupContent($faq2, $this->group);
    $faq_sticky = $this->createNode([
      'type' => 'faq',
      'sticky' => 1,
    ]);
    $this->addGroupContent($faq_sticky, $this->group);

    $view = View::load('os_faq');
    $view_exec = $view->getExecutable();
    $view_exec->setDisplay('page_1');
    $view_exec->preExecute();
    $view_exec->execute();
    $build = $view_exec->render('page_1');
    $rows = $build['#rows']['#rows'][0]['#rows'];

    // Get first views-row.
    $first_row = $rows[0]['#row']->_entity;
    $this->assertContains($faq_sticky->getTitle(), $first_row->getTitle(), 'Sticky faq is not the first.');
  }

  /**
   * Tests faq collapse.
   */
  public function testFaqCollapse(): void {
    $web_assert = $this->assertSession();
    $faq1 = $this->createNode([
      'type' => 'faq',
    ]);
    $this->addGroupContent($faq1, $this->group);
    $faq2 = $this->createNode([
      'type' => 'faq',
    ]);
    $this->addGroupContent($faq2, $this->group);

    $this->visitViaVsite('faq', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    $faq1_body = $faq1->get('body')->getValue()[0]['value'];
    $faq2_body = $faq2->get('body')->getValue()[0]['value'];

    $web_assert->pageTextContains($faq1->getTitle());
    $web_assert->pageTextNotContains($faq1_body);
    $web_assert->pageTextContains($faq2->getTitle());
    $web_assert->pageTextNotContains($faq2_body);

    // Test collapse.
    $this->getCurrentPage()->clickLink($faq1->getTitle());
    // Wait for collapse open.
    $this->getSession()->wait(1000);
    $web_assert->pageTextContains($faq1_body);
    $web_assert->pageTextNotContains($faq2_body);

    // Test collapse and auto close.
    $this->getCurrentPage()->clickLink($faq2->getTitle());
    // Wait for collapse open.
    $this->getSession()->wait(1000);
    $web_assert->pageTextNotContains($faq1_body);
    $web_assert->pageTextContains($faq2_body);
  }

  /**
   * Tests faq infinite scroll.
   */
  public function testFaqInfiniteScroll(): void {
    $web_assert = $this->assertSession();
    for ($i = 0; $i < 10; $i++) {
      $faq = $this->createNode([
        'type' => 'faq',
        'sticky' => 1,
      ]);
      $this->addGroupContent($faq, $this->group);
    }
    // Create one faq in Load more section.
    $faq = $this->createNode([
      'type' => 'faq',
    ]);
    $this->addGroupContent($faq, $this->group);
    $this->visitViaVsite('faq', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    $web_assert->pageTextNotContains($faq->getTitle());
    $this->getCurrentPage()->clickLink('Load More');
    $web_assert->waitForText($faq->getTitle());
    $web_assert->pageTextContains($faq->getTitle());
  }

  /**
   * Tests faq listing taxonomy view format.
   */
  public function testFaqTaxonomyViewFormat(): void {
    $this->createGroupVocabulary($this->group, 'vocab_group', ['node:faq']);
    $term1 = $this->createGroupTerm($this->group, 'vocab_group');
    $term2 = $this->createGroupTerm($this->group, 'vocab_group');
    $web_assert = $this->assertSession();
    $faq = $this->createNode([
      'type' => 'faq',
      'field_taxonomy_terms' => [
        $term1,
        $term2,
      ],
    ]);
    $this->addGroupContent($faq, $this->group);

    $this->visitViaVsite('faq', $this->group);
    $this->assertSession()->statusCodeEquals(200);

    $web_assert->pageTextContains($faq->getTitle());
    $this->getCurrentPage()->clickLink($faq->getTitle());
    // Wait for collapse open.
    $this->getSession()->wait(1000);
    $page = $this->getCurrentPage();
    $this->assertContains('<div class="see-more-tag">', $page->getHtml());
    $this->assertContains('<strong>See also:</strong>', $page->getHtml());
    $web_assert->pageTextContains($term1->label());
    $web_assert->pageTextContains($term2->label());
  }

}
