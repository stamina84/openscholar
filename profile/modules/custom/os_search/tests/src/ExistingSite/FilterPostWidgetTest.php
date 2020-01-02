<?php

namespace Drupal\Tests\os_search\ExistingSiteJavascript;

use Drupal\Tests\os_widgets\ExistingSite\OsWidgetsExistingSiteTestBase;

/**
 * Tests filter by post build function.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_search\Plugin\OsWidgets\FilterPostWidget
 */
class FilterPostWidgetTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test build function to display filter post.
   */
  public function testBuildDisplayFilterPost() {
    $block_content = $this->createBlockContent([
      'type' => 'filter_post',
      'info' => [
        'value' => 'Filter Title',
      ],
      'field_widget_title' => ['Filter Title'],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');

    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('Filter Title', $markup->__toString());
  }

}
