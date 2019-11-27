<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class AccordionBlockRenderTest.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\AccordionWidget
 */
class AccordionBlockRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test build function to display accordion.
   */
  public function testBuildDisplayAccordion() {
    $block1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test 1',
      ],
      'body' => [
        'Lorem Ipsum content 1',
      ],
      'field_widget_title' => ['Test 1'],
    ]);
    $block_id = $block1->id();
    $this->group->addContent($block1, 'group_entity:block_content');
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'accordion',
      'info' => [
        'value' => 'Accordion test',
      ],
      'field_widget_title' => 'testing accordion',
      'field_widget_collection' => [
        'target_id' => $block_id,
        'section_title' => 'Custom section title',
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');
    $this->assertEquals('os_widgets_accordion', $render['accordion']['#theme']);
    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<div class="panel-body">', $markup->__toString());
    $this->assertContains('Custom section title', $markup->__toString());
  }

}
