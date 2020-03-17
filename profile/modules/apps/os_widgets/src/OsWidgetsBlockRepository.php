<?php

namespace Drupal\os_widgets;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\os_widgets\Entity\LayoutContext;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\vsite\Config\HierarchicalStorage;

/**
 * Decorates core's Block Repositry to take Layout Contexts into account.
 */
class OsWidgetsBlockRepository implements BlockRepositoryInterface {

  /**
   * The deocarted BlockReository object.
   *
   * @var \Drupal\block\BlockRepositoryInterface
   */
  protected $blockRepository;

  /**
   * The entity type manager storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Hierarchical Storage.
   *
   * @var \Drupal\vsite\Config\HierarchicalStorage
   */
  protected $hierarchialStorage;

  /**
   * Constructs a new BlockRepository.
   *
   * @param \Drupal\block\BlockRepositoryInterface $blockRepository
   *   The original block repository being decorated.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   * @param \Drupal\vsite\Config\HierarchicalStorage $hierarchical_storage
   *   Hierarchical Storage.
   */
  public function __construct(BlockRepositoryInterface $blockRepository, EntityTypeManagerInterface $entity_type_manager, ThemeManagerInterface $theme_manager, VsiteContextManagerInterface $vsite_context_manager, RequestStack $request_stack, HierarchicalStorage $hierarchical_storage) {
    $this->blockRepository = $blockRepository;
    $this->entityTypeManager = $entity_type_manager;
    $this->themeManager = $theme_manager;
    $this->vsiteContextManager = $vsite_context_manager;
    $this->requestStack = $request_stack;
    $this->hierarchialStorage = $hierarchical_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibleBlocksPerRegion(array &$cacheable_metadata = []) {
    $output = [];
    $applicable = LayoutContext::getApplicable();

    $limit = $this->requestStack->getCurrentRequest()->query->get('context');

    $limit_found = !$limit;
    $flat = [];
    // TODO: Replace with mechanism to detect when we're on a block_place page.
    $editing = TRUE;

    // Pull down a list of all the blocks in the site.
    if ($editing) {
      $flatWeight = 0;
      /** @var \Drupal\group\Entity\GroupInterface|null $vsite */
      $vsite = $this->vsiteContextManager->getActiveVsite();
      if ($vsite) {
        $blockGCEs = $vsite->getContent('group_entity:block_content');
        foreach ($blockGCEs as $bgce) {
          /** @var \Drupal\block_content\BlockContentInterface $block_content */
          $block_content = $bgce->getEntity();
          $instances = $block_content->getInstances();
          if (!$instances) {
            $plugin_id = 'block_content:' . $block_content->uuid();
            $block_id = 'block_content|' . $block_content->uuid();
            $block = $this->entityTypeManager->getStorage('block')->create(['plugin' => $plugin_id, 'id' => $block_id]);
            $block->save();
          }
          else {
            $block = reset($instances);
          }
          $flat[$block->id()] = [
            'id' => $block->id(),
            'region' => 0,
            'weight' => $flatWeight++,
          ];
        }
      }
    }

    $placed_blocks = [];
    // Take any block in the currently active contexts and
    // place it in the correct region.
    /** @var \Drupal\os_widgets\LayoutContextInterface $a */
    foreach ($applicable as $a) {
      if ($a->id() == $limit) {
        $limit_found = TRUE;
      }
      if ($limit_found) {
        // Get Default layout context blocks.
        $layout_context = $this->hierarchialStorage->readFromLevel('os_widgets.layout_context.' . $a->id(), HierarchicalStorage::GLOBAL_STORAGE);
        foreach ($layout_context['data'] as $block) {
          $flat[$block['id']] = [
            'id' => $block['id'],
            'region' => 0,
            'weight' => 0,
          ];
        }

        $context_blocks = $a->getBlockPlacements();
        foreach ($context_blocks as $b) {
          if (in_array($b['id'], $placed_blocks)) {
            continue;
          }

          $placed_blocks[] = $b['id'];
          $flat[$b['id']] = $b;
        }
      }
    }

    usort($flat, [LayoutContext::class, 'sortWidgets']);

    // Split out the flat list by region while loading the real block.
    foreach ($flat as $b) {
      if ($block = Block::load($b['id'])) {
        $output[$b['region']][] = $block;
      }
    }
    return $output;
  }

}
