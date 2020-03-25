<?php

namespace Drupal\Tests\os_pages\ExistingSiteJavascript;

/**
 * SectionOutlineFormTest.
 *
 * @group functional-javascript
 * @group pages
 */
class SectionOutlineFormTest extends TestBase {

  /**
   * Tests section outline form.
   */
  public function testSectionOutlineForm() {
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContextManager */
    $vsiteContextManager = $this->container->get('vsite.context_manager');
    $vsiteContextManager->activateVsite($this->group);

    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');

    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $this->group);
    $this->drupalLogin($groupAdmin);
    $web_assert = $this->assertSession();
    /** @var \Drupal\node\NodeInterface $book */
    // Creating first book.
    $book1 = $this->createBookPage([
      'title' => 'First outline book',
    ]);
    // Creating sub pages.
    $sub_page = $this->createBookPage([
      'title' => 'Sub page',
    ], $book1->id());

    $grand_child = $this->createBookPage([
      'title' => 'Grand child',
    ], $book1->id(), $sub_page->id());

    $sub_page2 = $this->createBookPage([
      'title' => 'Another page',
    ], $book1->id());

    // Second book.
    $book2 = $this->createBookPage([
      'title' => 'Second book',
    ]);

    $this->addGroupContent($book1, $this->group);
    $this->addGroupContent($book2, $this->group);
    $this->addGroupContent($sub_page, $this->group);
    $this->addGroupContent($grand_child, $this->group);
    $this->addGroupContent($sub_page2, $this->group);
    // Visiting book page.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$book1->id()}"));
    $web_assert->statusCodeEquals(200);
    // Checking contextual links.
    $web_assert->waitForElement('css', '.block--type-section-navigation .contextual-links');
    $web_assert->buttonExists('Open configuration options')->click();
    $web_assert->waitForElementVisible('css', '.contextual-links');
    // Checking section outline contextual link redirection.
    $this->getSession()->getPage()->find('css', '.contextual-links .section-outline a')->click();
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $path = $this->getSession()->getCurrentUrl();
    $web_assert->fieldExists("table[book-admin-{$sub_page->id()}][title]");
    $books_field = $page->findField("books-list-book-admin-{$sub_page->id()}");
    $books_field->setValue($book2->id());
    $web_assert->buttonExists("move-btn-book-admin-{$sub_page->id()}")->click();
    $web_assert->assertNoElementAfterWait('css', '.ajax-progress-throbber');
    // Asserting page status is 200 after reload.
    $web_assert->statusCodeEquals(200);
    // Testing path redirection after ajax save.
    $this->assertEquals($this->getSession()->getCurrentUrl(), $path);
    $web_assert->fieldNotExists("table[book-admin-{$sub_page->id()}][title]");
    $web_assert->fieldNotExists("table[book-admin-{$grand_child->id()}][title]");
    $web_assert->fieldExists("table[book-admin-{$sub_page2->id()}][title]");
    $this->visitViaVsite("node/{$sub_page->id()}", $this->group);
    $web_assert->statusCodeEquals(200);
    // Fetching grand child node after moving to book2.
    $gc = $this->getNodeByTitle('Grand child');
    $this->assertEquals($book2->id(), $gc->book['bid']);
    // Fetching sub page node after moving to book2.
    $sp = $this->getNodeByTitle('Sub page');
    $this->assertEquals($book2->id(), $sp->book['bid']);
    $this->assertEquals($sp->id(), $gc->book['pid']);
    // Checking section outline url after moving to Book2.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$sub_page->id()}"));
    $web_assert->statusCodeEquals(200);
    $sub_page_path = $path_alias_manager->getAliasByPath("/node/{$sub_page->id()}");
    // Assuring the Sub page has Book2 section outline link.
    $web_assert->buttonExists('Open configuration options')->click();
    $web_assert->waitForElementVisible('css', '.contextual-links');
    /** @var \Behat\Mink\Element\NodeElement|null $section_outline_link */
    $section_outline_link = $this->getSession()->getPage()->find('css', '.contextual-links .section-outline a');
    $this->assertNotNull($section_outline_link);
    $web_assert->linkByHrefExists("{$this->groupAlias}/node/{$book2->id()}/section-outline?destination={$sub_page_path}");
  }

  /**
   * Testing layouts after moving sub pages to another book.
   */
  public function testLayoutChanges() {
    $region = "content";
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContextManager */
    $vsiteContextManager = $this->container->get('vsite.context_manager');
    $vsiteContextManager->activateVsite($this->group);

    /** @var \Drupal\path_alias\AliasManager $path_alias_manager */
    $path_alias_manager = $this->container->get('path_alias.manager');

    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $this->group);
    $this->drupalLogin($groupAdmin);
    $web_assert = $this->assertSession();
    /** @var \Drupal\node\NodeInterface $book */
    // Creating first book.
    $book1 = $this->createBookPage([
      'title' => 'First outline book',
    ]);
    // Creating sub pages.
    $sub_page = $this->createBookPage([
      'title' => 'Sub page',
    ], $book1->id());

    $grand_child = $this->createBookPage([
      'title' => 'Grand child',
    ], $book1->id(), $sub_page->id());

    // Second book.
    $book2 = $this->createBookPage([
      'title' => 'Second book',
    ]);

    $sub_page2 = $this->createBookPage([
      'title' => 'Another page',
    ], $book2->id());

    $this->addGroupContent($book1, $this->group);
    $this->addGroupContent($book2, $this->group);
    $this->addGroupContent($sub_page, $this->group);
    $this->addGroupContent($grand_child, $this->group);
    $this->addGroupContent($sub_page2, $this->group);

    $book_context = "os_pages_section_{$book1->id()}";
    $book_context2 = "os_pages_section_{$book2->id()}";
    // Content setup.
    $widget1 = $this->createBlockContent([
      'info' => 'Apple Widget',
      'field_widget_title' => [
        'value' => 'Apple Widget',
      ],
    ]);
    $this->group->addContent($widget1, 'group_entity:block_content');
    $this->placeBlockContentToRegion($widget1, $region, $book_context, 1);

    $widget2 = $this->createBlockContent([
      'info' => 'The Doors Widget',
      'field_widget_title' => [
        'value' => 'The Doors Widget',
      ],
    ]);
    $this->group->addContent($widget2, 'group_entity:block_content');
    $this->placeBlockContentToRegion($widget2, $region, $book_context2, 1);

    // Visiting book page.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$sub_page->id()}"));
    $web_assert->statusCodeEquals(200);
    // Checking contextual links.
    $web_assert->pageTextContains('Apple Widget');
    $web_assert->pageTextNotContains('The Doors Widget');
    $web_assert->waitForElement('css', '.block--type-section-navigation .contextual-links');
    $web_assert->buttonExists('Open configuration options')->click();
    $web_assert->waitForElementVisible('css', '.contextual-links');
    // Checking section outline contextual link redirection.
    $this->getSession()->getPage()->find('css', '.contextual-links .section-outline a')->click();
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $web_assert->fieldExists("table[book-admin-{$sub_page->id()}][title]");
    $books_field = $page->findField("books-list-book-admin-{$sub_page->id()}");
    $books_field->setValue($book2->id());
    $web_assert->buttonExists("move-btn-book-admin-{$sub_page->id()}")->click();
    $web_assert->assertNoElementAfterWait('css', '.ajax-progress-throbber');
    // Asserting page status is 200 after reload.
    $web_assert->statusCodeEquals(200);
    // Checking layouts after moving to Book2.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$sub_page->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextNotContains('Apple Widget');
    $web_assert->pageTextContains('The Doors Widget');
    // Asserting the same also in Sub page's child page.
    $this->visit($path_alias_manager->getAliasByPath("/node/{$grand_child->id()}"));
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextNotContains('Apple Widget');
    $web_assert->pageTextContains('The Doors Widget');
  }

}
