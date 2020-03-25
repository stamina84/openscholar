<?php

namespace Drupal\os_pages;

use Drupal\Group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Helper service for block visibility group.
 */
interface BooksHelperInterface {

  /**
   * Get matching nodes for search query.
   *
   * @param string $input
   *   Search string.
   *
   * @return array
   *   Returns matching node ids.
   */
  public function getMatchingNodes($input) : array;

  /**
   * The book to be excluded from results.
   *
   * @param \Drupal\Group\Entity\GroupInterface $vsite
   *   The current vsite.
   * @param array $matching_nids
   *   String matching node ids.
   * @param \Drupal\node\NodeInterface $node
   *   Current node entity.
   *
   * @return array
   *   Returns vsite matching nodes.
   */
  public function getGroupBookResults(GroupInterface $vsite, array $matching_nids, NodeInterface $node) : array;

  /**
   * Get all books except current book.
   *
   * @param \Drupal\Group\Entity\GroupInterface $vsite
   *   The current vsite.
   * @param \Drupal\node\NodeInterface $node
   *   Current node entity.
   *
   * @return array
   *   Returns vsite books.
   */
  public function getVsiteBooks(GroupInterface $vsite, NodeInterface $node): array;

  /**
   * Save selected book page into book.
   *
   * @param \Drupal\node\NodeInterface $selected_book
   *   Selected book page node entity.
   * @param \Drupal\node\NodeInterface $book_entity
   *   Book entity - pages should be added to.
   */
  public function saveOtherBookPages(NodeInterface $selected_book, NodeInterface $book_entity);

  /**
   * Create/update a child's layout.
   *
   * @param \Drupal\node\NodeInterface $sub_page
   *   Existing sub-page of a book entity.
   * @param \Drupal\node\NodeInterface $book
   *   Parent book entity.
   */
  public function setChildLayoutContext(NodeInterface $sub_page, NodeInterface $book);

  /**
   * Unset entity from book's layout context.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   Book node entity.
   */
  public function unsetLayoutContext(NodeInterface $entity);

}
