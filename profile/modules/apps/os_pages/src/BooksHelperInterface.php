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

}
