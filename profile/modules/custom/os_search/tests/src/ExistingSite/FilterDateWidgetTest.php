<?php

namespace Drupal\Tests\os_search\ExistingSite;

use Drupal\Tests\os_widgets\ExistingSite\OsWidgetsExistingSiteTestBase;

/**
 * Tests Filter Post Date block content.
 *
 * @group kernel
 * @group widgets-4
 * @covers \Drupal\os_search\Plugin\OsWidgets\FilterDateWidget
 */
class FilterDateWidgetTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test build function to display filter post.
   */
  public function testBuildDisplayFilterDate() {
    $block_content = $this->createBlockContent([
      'type' => 'filter_date',
      'info' => [
        'value' => 'Filter Post by Date',
      ],
      'field_widget_title' => ['Filter by Date'],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('Filter by Date', $markup->__toString());

  }

}
