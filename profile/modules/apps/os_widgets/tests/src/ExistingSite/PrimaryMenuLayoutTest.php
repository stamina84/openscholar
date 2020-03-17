<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\os_widgets\Entity\LayoutContext;

/**
 * Class PrimaryMenuLayoutTest.
 *
 * @group kernel
 * @group widgets-3
 * @covers \Drupal\os_widgets\OsWidgetsBlockRepository
 */
class PrimaryMenuLayoutTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Block repository.
   *
   * @var array
   */
  protected $blockRepository;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager->activateVsite($this->group);
    $this->blockRepository = $this->container->get('os_widgets.block.repository');
  }

  /**
   * Test getVisibleBlocksPerRegion function for Primary Menu.
   */
  public function testPrimaryMenuVisibility() {
    $allBlocks = $this->blockRepository->getVisibleBlocksPerRegion();

    $block_ids = [];
    foreach ($allBlocks['navigation_collapsible'] as $nav_block) {
      $block_ids[] = $nav_block->id();
    }
    // Assert Primary Menu is present in navigation region.
    $this->assertTrue(in_array('primarymenu', $block_ids));

    // Remove Primary Menu from Layout.
    $layoutContext = LayoutContext::load('all_pages');
    $blocks = $layoutContext->getBlockPlacements();
    foreach ($blocks as $key => $block) {
      if ($block['id'] == 'primarymenu') {
        unset($blocks[$key]);
      }
    }
    $layoutContext->setBlockPlacements($blocks);
    $layoutContext->save();

    $allBlocks = $this->blockRepository->getVisibleBlocksPerRegion();
    $block_ids = [];
    if (isset($allBlocks['navigation_collapsible'])) {
      foreach ($allBlocks['navigation_collapsible'] as $nav_block) {
        $block_ids[] = $nav_block->id();
      }
    }
    // Assert Primary Menu is not present in navigation region.
    $this->assertFalse(in_array('primarymenu', $block_ids));

    $block_ids = [];
    if (isset($allBlocks[0])) {
      foreach ($allBlocks[0] as $nav_block) {
        $block_ids[] = $nav_block->id();
      }
    }
    // Assert Primary Menu present in widget section.
    $this->assertTrue(in_array('primarymenu', $block_ids));
  }

}
