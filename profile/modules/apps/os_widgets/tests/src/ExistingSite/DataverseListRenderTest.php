<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class DataverseListRenderTest.
 *
 * @group kernel
 * @group widgets-3
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\DataverseListWidget
 */
class DataverseListRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test render build array content.
   */
  public function testRenderArrayContent() {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'dataverse_list',
      'field_dataverse_identifier' => 'king',
      'field_embed_height' => 400,
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $this->assertSame('os_widgets_dataverse_list_widget', $render['dataverse_list_widget']['#theme']);
    $this->assertSame('https://dataverse.harvard.edu/dataverse/king?widget=dataverse%40king', $render['dataverse_list_widget']['#embed_url']);
    $this->assertSame('400px', $render['dataverse_list_widget']['#embed_height']);
  }

}
