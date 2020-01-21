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
    $this->group->addContent($block_content, 'group_entity:block_content');
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $this->assertSame('os_widgets_dataverse_list_widget', $render['dataverse_list_widget']['#theme']);
    $this->assertSame('https://dataverse.harvard.edu/dataverse/king?widget=dataverse%40king', $render['dataverse_list_widget']['#embed_url']);
    $this->assertSame('400px', $render['dataverse_list_widget']['#embed_height']);
  }

  /**
   * Test render build array content.
   */
  public function testRenderModifiedUrl() {
    $this->container->get('vsite.context_manager')->activateVsite($this->group);
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'dataverse_list',
      'field_dataverse_identifier' => 'king',
      'field_embed_height' => 400,
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $new_url = 'http://' . $this->randomMachineName() . '.com/';
    $config = $this->container->get('config.factory')->getEditable('os_widgets.dataverse');
    $config->set('listing_base_url', $new_url);
    $config->save(TRUE);

    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $this->assertSame($new_url . 'king?widget=dataverse%40king', $render['dataverse_list_widget']['#embed_url']);
  }

}
