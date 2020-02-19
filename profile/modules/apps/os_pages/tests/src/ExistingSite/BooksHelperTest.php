<?php

namespace Drupal\Tests\os_pages\ExistingSite;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;
use Drupal\os_pages\Controller\BooksAutocompleteController;
use Drupal\os_pages\BooksHelper;

/**
 * Tests for BooksHelper.
 *
 * @group kernel
 * @group other-2
 * @coversDefaultClass \Drupal\os_pages\BooksHelper
 */
class BooksHelperTest extends TestBase {

  /**
   * The books Helper.
   *
   * @var \Drupal\os_pages\BooksHelper
   */
  protected $booksHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->booksHelper = $this->container->get('os_pages.books_helper');
  }

  /**
   * Tests matching pages in Autocomplete list in add-other-books form.
   */
  public function testMatchingBooks() {
    $data_nids = [];
    /** @var \Drupal\node\NodeInterface $book */
    $book = $this->createBookPage([
      'title' => 'First book',
    ]);

    /** @var \Drupal\node\NodeInterface $first_sub_page */
    $first_sub_page = $this->createBookPage(['title' => 'First sub book'], $book->id());

    /** @var \Drupal\node\NodeInterface $first_sub_sub_page */
    $first_sub_sub_page = $this->createBookPage(['title' => 'First sub sub book'], $book->id(), $first_sub_page->id());
    $this->addGroupContent($book, $this->group);
    $this->addGroupContent($first_sub_page, $this->group);
    $this->addGroupContent($first_sub_sub_page, $this->group);

    /** @var \Drupal\node\NodeInterface $second_sub_page */
    $second_book = $this->createBookPage([
      'title' => 'Second book',
    ]);
    $this->addGroupContent($second_book, $this->group);

    $not_book_page = $this->createNode([
      'title' => 'Not a book page',
      'type' => 'page',
    ]);
    $this->addGroupContent($not_book_page, $this->group);

    $data_nids = [
      $book->id(), $first_sub_page->id(), $first_sub_sub_page->id(), $second_book->id(), $not_book_page->id(),
    ];

    $input = 'book';
    $request = Request::create('os_pages/books-autocomplete/' . $this->group->id() . '/' . $book->id());
    $request->query->set('q', $input);

    $entity_reference_controller = BooksAutocompleteController::create($this->container);
    $result = Json::decode($entity_reference_controller->handleAutocomplete($request, $this->group, $book)->getContent());

    $value1 = [
      'value' => $second_book->getTitle() . ' (' . $second_book->id() . ')',
      'label' => $second_book->getTitle() . ' (' . $second_book->id() . ')',
    ];

    $value2 = [
      'value' => $not_book_page->getTitle() . ' (' . $not_book_page->id() . ')',
      'label' => $not_book_page->getTitle() . ' (' . $not_book_page->id() . ')',
    ];

    $target = [$value1, $value2];

    $this->assertIdentical(sort($result), sort($target));

    // Testing BooksHelper service methods.
    $matching_nids = \Drupal::service('os_pages.books_helper')->getMatchingNodes('book');
    $this->assertIdentical(sort($matching_nids), sort($data_nids));

    $filtered_books = \Drupal::service('os_pages.books_helper')->getGroupBookResults($this->group, $matching_nids, $book);
    $this->assertIdentical(sort($filtered_books), sort($target));

  }

  /**
   * Tests not matching pages in Autocomplete list.
   */
  public function testNoMatchingPages() {
    $book1 = $this->createBookPage([
      'title' => 'First book 1',
    ]);

    /** @var \Drupal\node\NodeInterface $first_sub_page */
    $book2 = $this->createBookPage([
      'title' => 'Second book 2',
    ]);

    $this->addGroupContent($book1, $this->group);
    $this->addGroupContent($book2, $this->group);

    // Creating a page, but not added in this group.
    $standalone_non_book_page = $this->createNode([
      'title' => 'Not a book page in this group',
      'type' => 'page',
    ]);

    $data_nids = [
      $book1->id(), $book2->id(), $standalone_non_book_page->id(),
    ];

    // Even if the page is not added, it will list all pages.
    $matching_nids = \Drupal::service('os_pages.books_helper')->getMatchingNodes('book');
    $this->assertIdentical(sort($matching_nids), sort($data_nids));

    $input = 'book';
    $request = Request::create('os_pages/books-autocomplete/' . $this->group->id() . '/' . $book1->id());
    $request->query->set('q', $input);

    $entity_reference_controller = BooksAutocompleteController::create($this->container);
    $result = Json::decode($entity_reference_controller->handleAutocomplete($request, $this->group, $book1)->getContent());

    // Creating arrays in value-label format.
    $value1 = [
      'value' => $book2->getTitle() . ' (' . $book2->id() . ')',
      'label' => $book2->getTitle() . ' (' . $book2->id() . ')',
    ];
    $value2 = [
      'value' => $standalone_non_book_page->getTitle() . ' (' . $standalone_non_book_page->id() . ')',
      'label' => $standalone_non_book_page->getTitle() . ' (' . $standalone_non_book_page->id() . ')',
    ];

    $target = [$value1, $value2];
    $this->assertIdentical($result[0], $value1);
    $this->assertNotIdentical($result, $target);
  }

  /**
   * Test saveOtherBookPages method.
   */
  public function testSetBidChildren() {
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

    $this->addGroupContent($book1, $this->group);
    $this->addGroupContent($book2, $this->group);
    $this->addGroupContent($sub_page, $this->group);
    $this->addGroupContent($grand_child, $this->group);

    $sub_page_node = $this->getNodeByTitle('Sub page');
    $book2_node = $this->getNodeByTitle('Second book');
    $this->booksHelper->saveOtherBookPages($sub_page_node, $book2_node);
    // Fetching grand child node after moving to book2.
    $gc = $this->getNodeByTitle('Grand child');
    $this->assertEquals($book2->id(), $gc->book['bid']);
    $this->assertEquals($sub_page->id(), $gc->book['pid']);
  }

}
