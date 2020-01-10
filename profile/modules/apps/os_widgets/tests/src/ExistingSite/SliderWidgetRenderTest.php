<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\block_content\Entity\BlockContent;

/**
 * Class SliderWidgetRenderTest.
 *
 * @group kernel
 * @group widgets-4
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\SliderWidget
 */
class SliderWidgetRenderTest extends OsWidgetsExistingSiteTestBase {

  protected $blockIds;

  /**
   * Defining blocks.
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $block_storage */
    $block_storage = $entity_type_manager->getStorage('block');

    $blocks[] = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test 1',
      ],
      'body' => [
        'Lorem Ipsum content 1',
      ],
      'field_widget_title' => ['Test 1'],
    ]);
    $blocks[] = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test 2',
      ],
      'body' => [
        'Lorem Ipsum content 2',
      ],
      'field_widget_title' => ['Test 2'],
    ]);

    foreach ($blocks as $b) {
      $this->blockIds[]['target_id'] = $b->id();
      $plugin_id = 'block_content:' . $b->uuid();
      $block_id = 'block_content|' . $b->uuid();
      $block = $block_storage->create(['plugin' => $plugin_id, 'id' => $block_id]);
      $block->save();

      $this->group->addContent($b, 'group_entity:block_content');
    }

  }

  /**
   * Test build function for slider widget.
   */
  public function testSliderWidgetBuild() {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $slider_block = $this->createBlockContent([
      'type' => 'slider',
      'info' => [
        'value' => 'Slider test',
      ],
      'field_widget_title' => 'testing slider',
      'field_widget_collection' => $this->blockIds,
    ]);

    $this->group->addContent($slider_block, 'group_entity:block_content');
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $block_content = BlockContent::load($slider_block->id());
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    $this->assertEquals('os_widgets_slider', $render['slider']['#theme']);
    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    // Block shouldn't contain arrows as field_display_arrows is not checked.
    $this->assertNotContains('<button class="slick-prev slick-arrow" aria-label="Previous" type="button" style="">', $markup->__toString());
    $this->assertContains('Lorem Ipsum content 2', $markup->__toString());
  }

}
