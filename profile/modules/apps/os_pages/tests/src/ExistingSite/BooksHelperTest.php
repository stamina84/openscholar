<?php

namespace Drupal\Tests\os_pages\ExistingSite;

use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;
use Drupal\os_pages\Controller\BooksAutocompleteController;

/**
 * Tests for BooksHelper.
 *
 * @group openscholar
 * @group kernel
 * @group other
 * @coversDefaultClass \Drupal\os_pages\BooksHelper
 */
class BooksHelperTest extends TestBase {

  /**
   * Test addition of new condition in section group.
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

    $data_nids = [
      $book->id(), $first_sub_page->id(), $first_sub_sub_page->id(), $second_book->id(),
    ];

    $input = 'Second';
    $request = Request::create('os_pages/books-autocomplete/' . $this->group->id() . '/' . $book->id());
    $request->query->set('q', $input);

    $entity_reference_controller = BooksAutocompleteController::create($this->container);
    $result = Json::decode($entity_reference_controller->handleAutocomplete($request, $this->group, $book)->getContent());

    $value = $second_book->getTitle() . ' (' . $second_book->id() . ')';
    $target = [
      'value' => $value,
      'label' => $value,
    ];
    $this->assertIdentical(reset($result), $target);

    // Testing BooksHelper service methods.
    $matching_nids = \Drupal::service('os_pages.books_helper')->getMatchingNodes('book');
    $this->assertIdentical(reset($matching_nids), reset($data_nids));

    $filtered_books = \Drupal::service('os_pages.books_helper')->getGroupBookResults($this->group, $matching_nids, $book);
    $this->assertIdentical(reset($filtered_books), $target);

  }

}
