<?php

namespace Drupal\Tests\os_pages\ExistingSite;

/**
 * Class OutlineAccessCheckTest.
 *
 * @group kernel
 * @group others-2
 * @covers \Drupal\os_pages\Access\OutlineAccessCheck
 */
class OutlineAccessCheckTest extends TestBase {

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
  }

  /**
   * Test book outline access for book pages.
   */
  public function testOutlineAccessForBooks() {
    $book = $this->createBookPage([
      'title' => 'Book',
    ]);
    $this->addGroupContent($book, $this->group);
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite("node/{$book->id()}/book-outline", $this->group);
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();

    $account = $this->createUser();
    $this->addGroupEnhancedMember($account, $this->group);
    $this->drupalLogin($account);
    $this->visitViaVsite("node/{$book->id()}/book-outline", $this->group);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test book outline access for non-book pages.
   */
  public function testOutlineAccessForPages() {
    $page = $this->createNode([
      'title' => 'Standalone page',
      'type' => 'page',
    ]);
    $this->addGroupContent($page, $this->group);
    $this->drupalLogin($this->groupAdmin);
    // Testing as group admin.
    $this->visitViaVsite("node/{$page->id()}/book-outline", $this->group);
    $this->assertSession()->statusCodeEquals(403);

    // Testing as normal user.
    $account = $this->createUser();
    $this->addGroupEnhancedMember($account, $this->group);
    $this->drupalLogin($account);
    $this->visitViaVsite("node/{$page->id()}/book-outline", $this->group);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test book outline access for non-vsite books.
   */
  public function testAccessForNonVsites() {
    $book = $this->createBookPage([
      'title' => 'Book',
    ]);
    $this->drupalLogin($this->groupAdmin);
    $this->visit("/node/{$book->id()}/book-outline");
    $this->assertSession()->statusCodeEquals(403);
  }

}
