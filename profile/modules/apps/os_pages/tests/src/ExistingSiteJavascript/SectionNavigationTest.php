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
    $page1 = $this->createSubBookPages($book->id(), 'Sub page for book');
    $page2 = $this->createSubBookPages($book->id(), 'Sub page 2');
    $grand_child = $this->createSubBookPages($page1->id(), 'Grand child');

    $this->visit($path_alias_manager->getAliasByPath("/node/{$page1->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Sub page for book');
    $web_assert->linkExists('Sub page for book');
    $web_assert->linkExists('Grand child');
    $web_assert->linkExists('Sub page 2');
    // Check for current page active link.
    $web_assert->elementExists('css', '.active-nav-link');
    $web_assert->elementExists('css', '.block--type-section-navigation');
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
    $page1 = $this->createSubBookPages($book->id(), 'Sub page for book');
    $grand_child = $this->createSubBookPages($page1->id(), 'Grand child');

    $this->visitViaVsite("node/{$book->id()}/section-outline", $this->group);
    $this->getSession()->getPage()->checkField("table[book-admin-{$grand_child->id()}][hide]");
    $this->getSession()->getPage()->pressButton('Save book pages');
    $web_assert->statusCodeEquals(200);

    // Visiting the sub-page 1.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$page1->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->linkExists('Sub page for book');
    $web_assert->linkNotExists('Grand child');
    $this->markEntityForCleanup($book);
    $this->markEntityForCleanup($page1);
    $this->markEntityForCleanup($grand_child);
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

}
