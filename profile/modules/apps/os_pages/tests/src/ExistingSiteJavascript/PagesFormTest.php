<?php

namespace Drupal\Tests\os_pages\ExistingSiteJavascript;

/**
 * PagesFormTest.
 *
 * @group functional-javascript
 * @group pages
 */
class PagesFormTest extends TestBase {

  /**
   * Tests the custom code written for node add page form.
   *
   * @covers ::os_pages_node_prepare_form
   * @covers ::os_pages_form_node_page_form_alter
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   * @throws \Behat\Mink\Exception\DriverException
   */
  public function testPageAddForm(): void {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);

    // Test top-page creation.
    $title = $this->randomMachineName();
    $this->visitViaVsite('node/add/page', $this->group);

    $page_add_option = $this->getSession()->getPage()->find('css', '#edit-book summary');
    $this->assertNotNull($page_add_option);
    $page_add_option->click();

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->find('css', 'input[type=checkbox][name="book[checkbox]"]')->check();
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Save');

    $book = $this->getNodeByTitle($title);
    // Test sub-page level 1 creation.
    $title = $this->randomMachineName();
    $this->visitViaVsite("node/add/page?parent={$book->id()}", $this->group);

    $page_add_option = $this->getSession()->getPage()->find('css', '#edit-book summary');
    $this->assertNotNull($page_add_option);
    $page_add_option->click();

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getNodeByTitle($title, TRUE);

    $this->assertEquals($book->id(), $page->book['bid']);

    // Test sub-page level 2 creation.
    $title = $this->randomMachineName();
    $this->visitViaVsite("node/add/page?parent={$page->id()}", $this->group);

    $page_add_option = $this->getSession()->getPage()->find('css', '#edit-book summary');
    $this->assertNotNull($page_add_option);
    $page_add_option->click();

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getNodeByTitle($title);

    $this->assertEquals($book->id(), $node->book['bid']);
    $this->assertEquals($page->id(), $node->book['pid']);

    // Clean up.
    $node->delete();
    $page->delete();
    $book->delete();
  }

  /**
   * Testing Add other book pages form.
   *
   * This tests adding other book pages into current book.
   */
  public function testAddOtherBooksForm() {
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->drupalLogin($this->groupAdmin);
    $page = $this->getCurrentPage();
    /** @var \Drupal\node\NodeInterface $book */
    // Creating book Book-1.
    $book = $this->createBookPage([
      'title' => 'First outline book',
    ]);
    // Creating book Book-2.
    $book2 = $this->createBookPage([
      'title' => 'Second outline book',
    ]);

    $this->addGroupContent($book, $this->group);
    $this->addGroupContent($book2, $this->group);

    // Adding sub-page for Book-2.
    $title = 'Sub page for book 2';
    $this->visitViaVsite("node/add/page?parent={$book2->id()}", $this->group);

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->visitViaVsite("node/{$book->id()}/book-outline", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $result = $this->getSession()->getPage()->findLink('Add other book pages to this outline');
    $this->assertNotEmpty($result);
    $result->click();
    $this->assertSession()->statusCodeEquals(200);

    $page->fillField('add_other_books', substr($title, 0, 3));
    $this->assertSession()->waitOnAutocomplete();
    $this->assertSession()->responseContains($title);
    $this->getSession()->getPage()->find('css', 'ul.ui-autocomplete li:first-child')->click();
    $page->pressButton('Save');
    // Accessing Add other Books form of Book-1.
    $this->visitViaVsite("node/{$book->id()}/add-other-books", $this->group);
    $page->fillField('add_other_books', substr($title, 0, 3));
    $this->assertSession()->waitOnAutocomplete();
    // Since sub-page is added to Book-1, now sub-page won't be visible
    // in Book-1 'Add other books' autocomplete field.
    $this->assertSession()->responseNotContains($title);
  }

  /**
   * Test only vsite books present in Book Outline form.
   */
  public function testNodeVsiteBooksList() {
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->drupalLogin($this->groupAdmin);
    /** @var \Drupal\node\NodeInterface $book */
    $book = $this->createBookPage([
      'title' => 'First outline book',
    ]);
    $book2 = $this->createBookPage([
      'title' => 'Second outline book',
    ]);

    $this->addGroupContent($book, $this->group);
    $this->addGroupContent($book2, $this->group);

    // Creating another book page in another vSite.
    $book3 = $this->createBookPage([
      'title' => 'Another vsite book',
    ]);
    $vsite2 = $this->createGroup();
    $this->addGroupContent($book3, $vsite2);

    $this->visitViaVsite("node/{$book->id()}/edit", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->selectExists('edit-book-bid');
    $this->assertSession()->optionExists('edit-book-bid', 'Second outline book');
    $this->assertSession()->optionNotExists('edit-book-bid', 'Another vsite book');

    // Checking other vsite book outline.
    $this->addGroupAdmin($this->groupAdmin, $vsite2);
    $this->visitViaVsite("node/{$book3->id()}/edit", $vsite2);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->selectExists('edit-book-bid');
    $this->assertSession()->optionExists('edit-book-bid', 'Another vsite book');
  }

  /**
   * Test to check Book checkbox on pages edit.
   */
  public function testOutlineOnPages() {
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->drupalLogin($this->groupAdmin);
    $page = $this->createNode([
      'type' => 'page',
      'title' => 'Standalone page',
    ]);
    $this->addGroupContent($page, $this->group);
    $this->visitViaVsite("node/{$page->id()}/edit", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxNotChecked('edit-book-checkbox');
  }

}
