<?php

namespace Drupal\Tests\os_search\ExistingSite;

use Drupal\Tests\os_widgets\ExistingSite\OsWidgetsExistingSiteTestBase;

/**
 * Tests Filter Taxonomy block content.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_search\Plugin\OsWidgets\FilterTaxonomyWidget
 */
class FilterTaxonomyWidgetTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test build function to display filter post.
   */
  public function testBuildDisplayFilterTaxonomy() {
    $block_content = $this->createBlockContent([
      'type' => 'filter_taxonomy',
      'info' => [
        'value' => 'Filter Taxonomy Title',
      ],
      'field_widget_title' => ['Filter Taxonomy Title'],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('Filter Taxonomy Title', $markup->__toString());
  }

}
