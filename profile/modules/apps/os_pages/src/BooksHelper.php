<?php

namespace Drupal\os_pages;

use Drupal\book\BookManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\block_visibility_groups\Entity\BlockVisibilityGroup;

/**
 * Class BooksHelper.
 */
final class BooksHelper implements BooksHelperInterface {

  use StringTranslationTrait;

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
   * EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AliasManager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Vsite manager service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * The VisibilityStorage service.
   *
   * @var \Drupal\os_pages\VisibilityStorageInterface
   */
  protected $visibilityStorage;

  /**
   * BooksHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\os_pages\VisibilityHelperInterface $visibility_helper
   *   The visibility helper service.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The BookManager service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager service.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   The vsite context manager service.
   * @param \Drupal\os_pages\VisibilityStorageInterface $visibility_storage
   *   The visibility storage service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, VisibilityHelperInterface $visibility_helper, BookManagerInterface $book_manager, AliasManagerInterface $alias_manager, VsiteContextManagerInterface $vsite_context_manager, VisibilityStorageInterface $visibility_storage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->visibilityHelper = $visibility_helper;
    $this->bookManager = $book_manager;
    $this->aliasManager = $alias_manager;
    $this->vsiteContextManager = $vsite_context_manager;
    $this->visibilityStorage = $visibility_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('os_pages.visibility_helper'),
      $container->get('book.manager'),
      $container->get('path_alias.manager'),
      $container->get('vsite.context_manager'),
      $container->get('os_pages.visibility_storage')
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
    $current_book = $this->nodeStorage->load($selected_book->book['bid']);
    $tree = $this->bookManager->bookSubtreeData($current_book->book);
    $this->updateChildLayout($tree, $selected_book, $book_entity);
  }

  /**
   * Method to update layout during section-outline form changes.
   *
   * @param array $tree
   *   The selected book complete tree.
   * @param \Drupal\node\NodeInterface $selected_book
   *   The selected book.
   * @param \Drupal\node\NodeInterface $book_entity
   *   The book that selected book will be moved to.
   */
  protected function updateChildLayout(array $tree, NodeInterface $selected_book, NodeInterface $book_entity) {
    foreach ($tree as $data) {
      // From whole tree, update only the selected book page and its children.
      if ($data['link']['nid'] == $selected_book->id() || $data['link']['pid'] == $selected_book->id()) {
        $entity = $this->nodeStorage->load($data['link']['nid']);
        $this->unsetLayoutContext($entity);
        $this->setChildLayoutContext($entity, $book_entity);
      }
      if ($data['below']) {
        $this->updateChildLayout($data['below'], $selected_book, $book_entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setChildLayoutContext($sub_page, $book) {
    $section_id = "os_pages_section_{$book->id()}";
    $layout_context_storage = $this->entityTypeManager->getStorage('layout_context');
    /** @var \Drupal\group\Entity\GroupInterface|null $active_vsite */
    $active_vsite = $this->vsiteContextManager->getActiveVsite();
    /** @var \Drupal\os_widgets\LayoutContextInterface $section_layout */
    $section_layout = $layout_context_storage->load($section_id);
    // No book section yet.
    if (!$section_layout) {
      if ($this->visibilityHelper->isBookFirstPage($sub_page)) {
        $block = BlockContent::create([
          'info' => "Section nav for {$book->label()} - {$book->id()}",
          'type' => 'section_navigation',
          'field_book_reference' => [
            'value' => $book->id(),
          ],
        ]);
        $block->save();
        if ($active_vsite) {
          $active_vsite->addContent($block, 'group_entity:block_content');
        }
        $section_layout = $layout_context_storage->create([
          'id' => $section_id,
          'label' => $this->t('Section Layout For @label', ['@label' => $book->label()]),
          'activationRules' => implode("\n", [
            $this->aliasManager->getAliasByPath("/node/{$book->id()}"),
            $this->aliasManager->getAliasByPath("/node/{$sub_page->id()}"),
          ]),
          'weight' => 50,
          'data' => [
            'section_navigation' => [
              'id' => "block_content|{$block->uuid()}",
              'region' => 'sidebar_first',
              'weight' => 0,
            ],
          ],
        ]);
        $section_layout->save();
      }
    }
    else {
      $paths = $section_layout->getActivationRules();
      $paths = explode("\n", $paths);
      $paths[] = $this->aliasManager->getAliasByPath("/node/{$sub_page->id()}");
      $section_layout->setActivationRules(implode("\n", $paths));
      $section_layout->save();
    }
    // Set Block Visibility groups layout.
    $this->updateBvgLayout($sub_page, $book);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetLayoutContext($entity) {
    $layout_context_storage = $this->entityTypeManager->getStorage('layout_context');
    $bid = $entity->book['bid'];
    $section_id = "os_pages_section_{$bid}";
    $section_layout = $layout_context_storage->load($section_id);
    $paths = $section_layout->getActivationRules();
    $paths = explode("\n", $paths);
    foreach ($paths as $key => $value) {
      if ($this->aliasManager->getAliasByPath("/node/{$entity->id()}") === $value) {
        unset($paths[$key]);
      }
    }
    $section_layout->setActivationRules(implode("\n", $paths));
    $section_layout->save();
  }

  /**
   * Update BlockVisibilityGroup block layouts.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The selected book.
   * @param \Drupal\node\NodeInterface $book
   *   The book that selected book will be moved to.
   */
  protected function updateBvgLayout(NodeInterface $entity, NodeInterface $book) {
    $section_id = "os_pages_section_{$book->id()}";
    /** @var \Drupal\block_visibility_groups\Entity\BlockVisibilityGroup|null $section_visibility_group */
    $section_visibility_group = BlockVisibilityGroup::load($section_id);
    // Create/update section visibility group.
    if (!$section_visibility_group) {
      if ($this->visibilityHelper->isBookFirstPage($entity)) {
        $this->visibilityStorage->create([
          'id' => "os_pages_section_{$book->id()}",
          'label' => $this->t('OS Pages: Section @book_name', [
            '@book_name' => $book->label(),
          ]),
          'status' => TRUE,
          'allow_other_conditions' => TRUE,
          'logic' => 'and',
        ], [
          [
            'id' => 'node_type',
            'bundles' => [
              $entity->bundle() => $entity->bundle(),
            ],
            'negate' => FALSE,
            'context_mapping' => [
              'node' => '@node.node_route_context:node',
            ],
          ],
          [
            'id' => 'request_path',
            'pages' => "/node/{$book->id()}\n/node/{$entity->id()}",
            'negate' => FALSE,
            'context_mapping' => [],
          ],
        ]);
      }
    }
    else {
      // Update the path condition for section visibility group.
      // Making sure that it appears for the newly created page as well.
      /** @var array $conditions */
      $conditions = $section_visibility_group->getConditions()->getConfiguration();

      foreach ($conditions as $condition) {
        if ($condition['id'] === 'request_path') {
          /** @var string $condition_id */
          $condition_id = $condition['uuid'];
          /** @var string $pages */
          $pages = $condition['pages'];
        }
      }

      if (!isset($condition_id) && !isset($pages)) {
        return;
      }

      $section_visibility_group->removeCondition($condition_id);

      $section_visibility_group->addCondition([
        'id' => 'request_path',
        'pages' => "$pages\n/node/{$entity->id()}",
        'negate' => 0,
        'context_mapping' => [],
      ]);

      $section_visibility_group->save();
    }
  }

}
