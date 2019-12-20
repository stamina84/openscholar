<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\block_content\Entity\BlockContent;

/**
 * Class TabsBlockRenderTest.
 *
 * @group kernel
 * @group widgets-1
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\TabsWidget
 */
class TabsBlockRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test build function tabs display style.
   */
  public function testBuildDisplayTabs() {
    $block1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test tab 1',
      ],
      'body' => [
        'Lorem Ipsum tab content 1',
      ],
      'field_widget_title' => ['Test tab 1'],
    ]);
    $block_id = $block1->id();
    $this->group->addContent($block1, 'group_entity:block_content');
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'tabs',
      'info' => [
        'value' => 'Tabs',
      ],
      'field_widget_title' => 'testing tabs',
      'field_widget_collection' => [
        'target_id' => $block_id,
        'section_title' => 'Custom section title',
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->assertEquals('os_widgets_tabs', $render['tabs']['#theme']);

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('Lorem Ipsum tab content 1', $markup->__toString());
    $this->assertContains('Custom section title', $markup->__toString());
  }

  /**
   * Test Tabs widget after deleting child entities.
   */
  public function testDeletedTabItems() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityStorageInterface $block_storage */
    $block_storage = $entity_type_manager->getStorage('block');

    $blocks[] = $block1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test tab 1',
      ],
      'body' => [
        'Lorem Ipsum tab content 1',
      ],
      'field_widget_title' => ['Test tab 1'],
    ]);

    $blocks[] = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test tab 2',
      ],
      'body' => [
        'Lorem Ipsum tab content 2',
      ],
      'field_widget_title' => ['Test tab 2'],
    ]);

    foreach ($blocks as $b) {
      $block_ids[]['target_id'] = $b->id();
      $plugin_id = 'block_content:' . $b->uuid();
      $block_id = 'block_content|' . $b->uuid();
      $block = $block_storage->create(['plugin' => $plugin_id, 'id' => $block_id]);
      $block->save();

      $this->group->addContent($b, 'group_entity:block_content');
    }
    // Creating Tabs widget.
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $tabsBlock = $this->createBlockContent([
      'type' => 'tabs',
      'info' => [
        'value' => 'Tabs',
      ],
      'field_widget_title' => 'testing tabs',
      'field_widget_collection' => $block_ids,
    ]);
    $this->group->addContent($tabsBlock, 'group_entity:block_content');

    // Now delete the block content entity.
    $block1->delete();

    $block_content = BlockContent::load($tabsBlock->id());
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    $markup = $renderer->renderRoot($render);
    $this->assertNotContains('Lorem Ipsum tab content 1', $markup->__toString(), 'Block1 not found');
    $this->assertContains('Lorem Ipsum tab content 2', $markup->__toString());
  }

}
