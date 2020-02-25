<?php

namespace Drupal\os_pages;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BooksHelper.
 */
final class BooksHelper implements BooksHelperInterface {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * The visibility helper service.
   *
   * @var \Drupal\os_pages\VisibilityHelperInterface
   */
  protected $visibilityHelper;

  /**
   * BookManager service.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * BooksHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\os_pages\VisibilityHelperInterface $visibility_helper
   *   The visibility helper service.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The BookManager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, VisibilityHelperInterface $visibility_helper, BookManagerInterface $book_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->visibilityHelper = $visibility_helper;
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('os_pages.visibility_helper'),
      $container->get('book.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchingNodes($input): array {
    $query = $this->nodeStorage->getQuery()
      ->condition('type', 'page')
      ->condition('title', $input, 'CONTAINS')
      ->groupBy('nid')
      ->sort('created', 'DESC')
      ->range(0, 10);
    $matching_nids = $query->execute();

    return $matching_nids;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupBookResults($vsite, $matching_nids, $current_node): array {
    $results = [];
    foreach ($vsite->getContent('group_node:page') as $group_content) {
      $vsite_nids[] = $group_content->entity_id->target_id;
    }

    foreach ($matching_nids as $id) {
      if (in_array($id, $vsite_nids)) {
        $node = $this->nodeStorage->load($id);
        if ($node->book['bid'] !== $current_node->book['bid']) {
          $results[] = [
            'value' => EntityAutocomplete::getEntityLabels([$node]),
            'label' => EntityAutocomplete::getEntityLabels([$node]),
          ];
        }
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getVsiteBooks($vsite, $current_node): array {
    $results = ['' => 'Select new section'];
    foreach ($vsite->getContent('group_node:page') as $group_content) {
      $id = $group_content->entity_id->target_id;
      $node = $this->nodeStorage->load($id);
      if ($this->visibilityHelper->isBookPage($node) && $node->book['bid'] == $id && $node->book['bid'] !== $current_node->book['bid']) {
        $results[$id] = $node->label();
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function saveOtherBookPages($selected_book, $book_entity) {
    $book_entity_id = $selected_book->id();
    $book_data = $this->bookManager->bookTreeGetFlat($book_entity->book);
    $last_child_weight = (int) end($book_data)['weight'];
    $link = $this->bookManager->loadBookLink($book_entity_id, FALSE);
    $link['bid'] = $book_entity->book['bid'];
    $link['pid'] = $book_entity->book['bid'];
    $link['weight'] = $last_child_weight + 1;
    $link['has_children'] = $selected_book->book['has_children'];
    $this->bookManager->saveBookLink($link, FALSE);
    if ($selected_book->book['has_children'] > 0) {
      $this->setBidForChildren($selected_book->book, $book_entity->book['bid']);
    }
  }

  /**
   * Looping through children elements to save all of them.
   *
   * @param array $book_link
   *   Parent node entity's book array.
   * @param int $bid
   *   Book Id pages should be added to.
   */
  protected function setBidForChildren(array $book_link, $bid) {
    $flat = $this->bookManager->bookTreeGetFlat($book_link);
    // Walk through the array until we find the current page.
    do {
      $link = array_shift($flat);
    } while ($link && ($link['nid'] != $book_link['nid']));
    // Continue though the array and collect links whose parent is this page.
    while (($link = array_shift($flat)) && $link['pid'] == $book_link['nid']) {
      $child = $this->nodeStorage->load($link['nid']);
      if ($child->book['has_children'] > 0) {
        $this->setBidForChildren($child->book, $bid);
      }
      $link = $this->bookManager->loadBookLink($link['nid'], FALSE);
      $link['bid'] = $bid;
      $link['pid'] = $book_link['nid'];
      $this->bookManager->saveBookLink($link, FALSE);
    }

  }

}
