<?php

namespace Drupal\Tests\os_pages\ExistingSite;

/**
 * Tests book outline form.
 *
 * @group vsite
 * @group functional
 */
class BookOutlineFormTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
  }

  /**
   * Tests Book Outline form.
   */
  public function testBookOutlineForm() {
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

    $this->visitViaVsite("node/{$book->id()}/book-outline", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->selectExists('edit-book-bid');
    $this->assertSession()->optionExists('edit-book-bid', 'Second outline book');
    $this->assertSession()->optionNotExists('edit-book-bid', 'Another vsite book');

    // Checking other vsite book outline.
    $this->addGroupAdmin($this->groupAdmin, $vsite2);
    $this->visitViaVsite("node/{$book3->id()}/book-outline", $vsite2);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->selectExists('edit-book-bid');
    $this->assertSession()->optionExists('edit-book-bid', 'Another vsite book');

    // Checking access for vsite_admin to remove book from Outline.
    $this->visitViaVsite("node/{$book->id()}/outline/remove", $this->group);
    $this->assertSession()->statusCodeEquals(200);
  }

}
