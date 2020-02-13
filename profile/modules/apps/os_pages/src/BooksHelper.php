<?php

namespace Drupal\os_pages;

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
   * BooksHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\os_pages\VisibilityHelperInterface $visibility_helper
   *   The visibility helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, VisibilityHelperInterface $visibility_helper) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->visibilityHelper = $visibility_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('os_pages.visibility_helper')
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
    $results = [];
    foreach ($vsite->getContent('group_node:page') as $group_content) {
      $id = $group_content->entity_id->target_id;
      $node = $this->nodeStorage->load($id);
      if ($this->visibilityHelper->isBookPage($node) && $node->book['bid'] == $id && $node->book['bid'] !== $current_node->book['bid']) {
        $results[$id] = $node->label();
      }
    }

    return $results;
  }

}
