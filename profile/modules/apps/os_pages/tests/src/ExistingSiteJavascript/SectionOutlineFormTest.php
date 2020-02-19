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

    $this->visitViaVsite("node/{$book1->id()}/section-outline", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $web_assert->fieldExists("table[book-admin-{$sub_page->id()}][title]");
    $books_field = $page->findField("books-list-book-admin-{$sub_page->id()}");
    $books_field->setValue($book2->id());
    $web_assert->buttonExists("move-btn-book-admin-{$sub_page->id()}")->click();
    $web_assert->assertNoElementAfterWait('css', '.ajax-progress-throbber');
    // Asserting page status is 200 after reload.
    $web_assert->statusCodeEquals(200);
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
  }

}
