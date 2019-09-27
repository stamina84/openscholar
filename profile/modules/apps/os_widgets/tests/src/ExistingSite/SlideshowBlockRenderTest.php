<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class SlideshowBlockRenderTest.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\SlideshowWidget
 */
class SlideshowBlockRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test render add slideshow link.
   */
  public function testRenderAddSlideshowLink() {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);

    $this->assertNotEmpty($render['add_slideshow_button']);
    $this->assertSame('link', $render['add_slideshow_button']['#type']);
    $this->assertSame('route:os_widgets.add_slideshow;block_id=' . $block_content->id(), $render['add_slideshow_button']['#url']->toUriString());
  }

}
