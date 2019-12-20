<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class DataverseSearchBoxRenderTest.
 *
 * @group kernel
 * @group widgets-3
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\DataverseSearchBoxWidget
 */
class DataverseSearchBoxRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test render build array content.
   */
  public function testRenderArrayContent() {
    $placeholder_text = 'Type your search keywords here';
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'dataverse_search_box',
      'field_dataverse_identifier' => 'king',
      'field_search_placeholder_text' => $placeholder_text,
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $this->assertNotEmpty($render['dataverse_search_box_form']['search']);
    $this->assertSame($placeholder_text, $render['dataverse_search_box_form']['search']['#placeholder']);
    $this->assertSame('Enter the terms you wish to search for.', $render['dataverse_search_box_form']['search']['#description']->__toString());
    $this->assertSame('os_widgets/dataverse_search_box', $render['dataverse_search_box_form']['#attached']['library'][0]);
    $this->assertSame('king', $render['dataverse_search_box_form']['#attached']['drupalSettings']['osWidgets']['dataverseIdentifier']);
    $this->assertSame('https://dataverse.harvard.edu/dataverse.xhtml', $render['dataverse_search_box_form']['#attached']['drupalSettings']['osWidgets']['dataverseSearchBaseurl']);
  }

}
