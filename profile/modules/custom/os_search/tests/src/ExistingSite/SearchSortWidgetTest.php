<?php

namespace Drupal\Tests\os_search\ExistingSite;

use Drupal\Tests\os_widgets\ExistingSite\OsWidgetsExistingSiteTestBase;

/**
 * Tests Search sort block content.
 *
 * @group kernel
 * @group os-search
 * @covers \Drupal\os_search\Plugin\OsWidgets\SearchSortWidget
 */
class SearchSortWidgetTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test build function to display filter post.
   */
  public function testBuildDisplaySearchSort() {
    $block_content = $this->createBlockContent([
      'type' => 'search_sort',
      'info' => [
        'value' => 'Search Sort Title',
      ],
      'field_widget_title' => ['Search Sort Title'],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('Search Sort Title', $markup->__toString());
  }

}
