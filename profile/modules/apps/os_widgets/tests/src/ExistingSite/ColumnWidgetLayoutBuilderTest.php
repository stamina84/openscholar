<?php

declare(strict_types = 1);

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\layout_builder\SectionComponent;

/**
 * @coversDefaultClass \Drupal\os_widgets\Helper\ColumnWidgetLayoutBuilder
 *
 * @group kernel
 * @group widgets-4
 */
class ColumnWidgetLayoutBuilderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Column widget layout builder.
   *
   * @var \Drupal\os_widgets\Helper\ColumnWidgetLayoutBuilder
   */
  protected $columnWidgetLayoutBuilder;

  /**
   * The test widget.
   *
   * @var \Drupal\os_widgets\Entity\OsBlockContent
   */
  protected $widget;

  /**
   * The block instance of the test widget.
   *
   * @var \Drupal\block\Entity\Block
   */
  protected $widgetBlock;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->columnWidgetLayoutBuilder = $this->container->get('os_widgets.column_widget_layout_builder');
    $this->widget = $this->createBlockContent();
    $this->widgetBlock = $this->createBlockForBlockContent($this->widget);
  }

  /**
   * @covers ::widgetsToSectionComponents
   */
  public function testWidgetsToSectionComponents(): void {
    $section_components = $this->columnWidgetLayoutBuilder->widgetsToSectionComponents([$this->widget]);

    $this->assertNotEmpty($section_components);
    $this->assertTrue(isset($section_components[$this->widgetBlock->uuid()]));

    $section_component = $section_components[$this->widgetBlock->uuid()];
    $this->assertInstanceOf(SectionComponent::class, $section_component);

    $section_component_as_array = $section_component->toArray();
    $this->assertEquals($this->widgetBlock->uuid(), $section_component_as_array['uuid']);
    $this->assertEquals('first', $section_component_as_array['region']);
    $configuration = $section_component_as_array['configuration'];
    $this->assertEquals("block_content:{$this->widget->uuid()}", $configuration['id']);
  }

}
