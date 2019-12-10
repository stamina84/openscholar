<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class DataverseDatasetRenderTest.
 *
 * @group kernel
 * @group widgets-3
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\DataverseDatasetWidget
 */
class DataverseDatasetRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test render build array content.
   */
  public function testRenderArrayContent() {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $persistent_id = '10.7910/DVN/' . $this->randomMachineName(6);
    $block_content = $this->createBlockContent([
      'type' => 'dataverse_dataset',
      'field_dataset_persistent_id' => $persistent_id,
      'field_dataset_persistent_type' => 'doi',
      'field_embed_height' => 800,
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);

    $this->assertSame('os_widgets_dataverse_dataset', $render['embed_dataverse']['#theme']);
    $this->assertContains('https://dataverse.harvard.edu/resources/js/widgets.js', $render['embed_dataverse']['#js_url']);
    $this->assertContains('dvUrl=https%3A//dataverse.harvard.edu/', $render['embed_dataverse']['#js_url']);
    $this->assertContains('widget=iframe', $render['embed_dataverse']['#js_url']);
    $this->assertContains('heightPx=800', $render['embed_dataverse']['#js_url']);
    $this->assertContains('persistentId=doi%3A' . $persistent_id, $render['embed_dataverse']['#js_url']);
  }

}
