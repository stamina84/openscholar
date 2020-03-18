<?php

namespace Drupal\Tests\os_pages\ExistingSiteJavascript;

/**
 * Class SectionNavigationTest.
 *
 * @group functional-javascript
 * @group pages
 */
class SectionNavigationTest extends TestBase {

  /**
   * Test build function to check section book links.
   */
  public function testSectionBookLinks() {
    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $this->group);
    $this->drupalLogin($groupAdmin);

    $web_assert = $this->assertSession();

    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');

    // Creating book.
    $book_title = 'First book';
    $this->visitViaVsite("node/add/page", $this->group);
    $this->getSession()->getPage()->fillField('title[0][value]', $book_title);
    $this->getSession()->getPage()->checkField('book[checkbox]');
    $web_assert->assertNoElementAfterWait('css', '.ajax-progress ajax-progress-throbber');
    $web_assert->assertWaitOnAjaxRequest(10000);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);
    // Fetching book details.
    $book = $this->getNodeByTitle($book_title);
    $this->visitViaVsite("node/{$book->id()}", $this->group);
    $web_assert->statusCodeEquals(200);
    // Adding sub-page for Book.
    $page1 = $this->createSubBookPages($book->id(), 'First sub page');
    $page2 = $this->createSubBookPages($book->id(), 'Another sub page');
    $grand_child = $this->createSubBookPages($page1->id(), 'Grand child 1');

    // Check section nav widget on book main page.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$book->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->elementExists('css', '.block--type-section-navigation');
    $web_assert->linkExists('First book');

    $this->visit($path_alias_manager->getAliasByPath("/node/{$page1->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('First sub page');
    $web_assert->linkExists('First sub page');
    $web_assert->linkExists('Grand child 1');
    $web_assert->linkExists('Another sub page');
    // Check for current page active link.
    $web_assert->elementExists('css', '.active-nav-link');
    $web_assert->elementExists('css', '.block--type-section-navigation');
    // Asserting same block on sub-pages for a book.
    $block_id = $this->getSession()->getPage()->find('css', '.block--type-section-navigation')->getAttribute('id');
    $this->visit($path_alias_manager->getAliasByPath("/node/{$page2->id()}"));

    $web_assert->statusCodeEquals(200);
    $block_id2 = $this->getSession()->getPage()->find('css', '.block--type-section-navigation')->getAttribute('id');
    $this->assertEquals($block_id, $block_id2);
    $this->markEntityForCleanup($book);
    $this->markEntityForCleanup($page1);
    $this->markEntityForCleanup($page2);
    $this->markEntityForCleanup($grand_child);
  }

  /**
   * Tests to check if hidden_section_nav is enabled for book pages.
   */
  public function testHideSectionNav() {
    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $this->group);
    $this->drupalLogin($groupAdmin);

    $web_assert = $this->assertSession();

    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');

    // Creating book.
    $book_title = 'Another book';
    $this->visitViaVsite("node/add/page", $this->group);
    $this->getSession()->getPage()->fillField('title[0][value]', $book_title);
    $this->getSession()->getPage()->checkField('book[checkbox]');
    $web_assert->assertNoElementAfterWait('css', '.ajax-progress ajax-progress-throbber');
    $web_assert->assertWaitOnAjaxRequest(10000);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);
    // Fetching book details.
    $book = $this->getNodeByTitle($book_title);
    $this->visitViaVsite("node/{$book->id()}", $this->group);
    $web_assert->statusCodeEquals(200);
    // Assert no section navigation widget if book main page is created.
    $web_assert->elementNotExists('css', '.block--type-section-navigation');

    // Adding sub-page for Book.
    $page1 = $this->createSubBookPages($book->id(), 'Sub page for book');
    // Created another sub-pages.
    $page2 = $this->createSubBookPages($book->id(), 'Another sub page');
    $grand_child = $this->createSubBookPages($page1->id(), 'Grand child');
    $grand_child2 = $this->createSubBookPages($page2->id(), 'Grand child 2');

    $this->visitViaVsite("node/{$book->id()}/section-outline", $this->group);
    $web_assert->statusCodeEquals(200);
    // Also setting Another sub page (parent) to hidden in section-nav.
    // If sub-page is hidden, its children will be hidden from section-nav.
    $this->getSession()->getPage()->checkField("table[book-admin-{$page2->id()}][hide]");
    $this->getSession()->getPage()->checkField("table[book-admin-{$grand_child->id()}][hide]");
    $this->getSession()->getPage()->pressButton('Save book pages');
    $web_assert->statusCodeEquals(200);

    // Visiting the sub-page 1.
    $sub_page_path = $path_alias_manager->getAliasByPath("/node/{$page1->id()}");
    $this->visit($sub_page_path);
    $web_assert->statusCodeEquals(200);
    $web_assert->linkExists('Sub page for book');
    $web_assert->linkNotExists('Grand child');
    // Asserting no sub-page 2 and grand child 2 links visible.
    $web_assert->linkNotExists('Another sub page');
    $web_assert->linkNotExists('Grand child 2');
    // Test custom contextual links.
    $web_assert->waitForElement('css', '.block--type-section-navigation .contextual-links');
    $web_assert->buttonExists('Open configuration options')->click();
    $web_assert->waitForElementVisible('css', '.contextual-links');
    /** @var \Behat\Mink\Element\NodeElement|null $section_outline_link */
    $section_outline_link = $this->getSession()->getPage()->find('css', '.contextual-links .section-outline a');
    $this->assertNotNull($section_outline_link);
    $web_assert->linkByHrefExists("{$this->groupAlias}/node/{$book->id()}/section-outline?destination={$sub_page_path}");

    // Entities clean up.
    $this->markEntityForCleanup($book);
    $this->markEntityForCleanup($page1);
    $this->markEntityForCleanup($grand_child);
    $this->markEntityForCleanup($page2);
    $this->markEntityForCleanup($grand_child2);
  }

  /**
   * Method to create sub pages via admin UI.
   *
   * @param int $parent_id
   *   The book id.
   * @param string $title
   *   The page title.
   */
  protected function createSubBookPages($parent_id, $title) {
    $this->visitViaVsite("node/add/page?parent={$parent_id}", $this->group);
    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    // Fetching page details.
    $page = $this->getNodeByTitle($title);
    return $page;
  }

  /**
   * Check section nav widget not present in standalone pages.
   */
  public function testNoWidgetOnPages() {
    $standalone_page = $this->createNode([
      'title' => 'Standalone page',
      'type' => 'page',
    ]);
    $this->addGroupContent($standalone_page, $this->group);
    $this->visitViaVsite("node/{$standalone_page->id()}", $this->group);
    // Assert widget is not created on standalone pages.
    $this->assertSession()->elementNotExists('css', '.block--type-section-navigation');
  }

}
