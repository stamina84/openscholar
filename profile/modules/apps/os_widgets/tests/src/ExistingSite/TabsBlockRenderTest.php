<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

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

}
