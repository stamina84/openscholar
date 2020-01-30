<?php

namespace Drupal\Tests\os_pages\ExistingSiteJavascript;

/**
 * OtherBooksFormTest.
 *
 * @group functional-javascript
 * @group pages
 */
class OtherBooksFormTest extends TestBase {

  /**
   * Tests other pages in add-other-books form.
   */
  public function testOtherPages() {
    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $this->group);
    $this->drupalLogin($groupAdmin);
    $web_assert = $this->assertSession();
    /** @var \Drupal\node\NodeInterface $book */
    // Creating book.
    $book = $this->createBookPage([
      'title' => 'First outline book',
    ]);
    // Creating page.
    $standalone_page = $this->createNode([
      'title' => 'Standalone page',
      'type' => 'page',
    ]);

    $this->addGroupContent($book, $this->group);
    $this->addGroupContent($standalone_page, $this->group);

    $this->visitViaVsite("node/{$book->id()}/add-other-books", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $pages_field = $page->findField('edit-add-other-books');
    $pages_field->setValue('Standalone');
    $result = $web_assert->waitForElementVisible('css', '.ui-autocomplete li');
    $this->assertNotNull($result);
    // Click the autocomplete option.
    $result->click();
    $web_assert->pageTextContains('Standalone page');
    $page->findButton('Save')->click();
    $web_assert->statusCodeEquals(200);
    $this->visitViaVsite("node/{$standalone_page->id()}", $this->group);
    $web_assert->statusCodeEquals(200);
    $breadcrumb_links = $page->find('css', '.breadcrumb li a');
    $breadcrumb_links->hasLink('First outline book');
  }

}
