<?php

namespace Drupal\Tests\os_faq\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * FaqTest.
 *
 * @group functional-javascript
 * @group faq
 */
class FaqTest extends OsExistingSiteJavascriptTestBase {

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

    $this->visitViaVsite('faq', $this->group);
    $this->assertSession()->statusCodeEquals(200);
    // Get first views-row.
    $this->assertSession()->waitForElementVisible('css', '.view-os-faq .views-row');
    $first_row = $this->getCurrentPage()->find('css', '.view-os-faq .views-row');
    $this->assertContains($faq_sticky->getTitle(), $first_row->getHtml(), 'Sticky faq is not the first.');
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

}
