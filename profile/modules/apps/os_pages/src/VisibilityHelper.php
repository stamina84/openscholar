<?php

namespace Drupal\os_pages;

use Drupal\book\BookOutlineStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PagesVisibilityHelper.
 */
final class VisibilityHelper implements VisibilityHelperInterface {

  /**
   * The book outline storage.
   *
   * @var \Drupal\book\BookOutlineStorageInterface
   */
  protected $bookOutlineStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

  /**
   * VisibilityHelper constructor.
   *
   * @param \Drupal\book\BookOutlineStorageInterface $book_outline_storage
   *   The book outline storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(BookOutlineStorageInterface $book_outline_storage, EntityTypeManagerInterface $entity_type_manager) {
    $this->bookOutlineStorage = $book_outline_storage;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('book.outline_storage'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isBookFirstPage(EntityInterface $entity) : bool {
    // Book's first page is not considered, if, the page is not the immediate
    // child of the book.
    if ($entity->book['bid'] != $entity->book['pid']) {
      return FALSE;
    }

    /** @var \Drupal\node\NodeInterface $book */
    $book = $this->nodeStorage->load($entity->book['bid']);

    /** @var array $book_pages */
    $book_pages = $this->bookOutlineStorage->loadBookChildren($book->id());

    return (count($book_pages) === 1);
  }

  /**
   * {@inheritdoc}
   */
  public function isBookPage(EntityInterface $entity): bool {
    return ($entity->getEntityType()->id() === 'node' &&
      $entity->bundle() === 'page' &&
      !empty($entity->book['bid']));
  }

}
