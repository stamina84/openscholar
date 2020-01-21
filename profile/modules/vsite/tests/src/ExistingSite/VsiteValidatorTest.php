<?php

namespace Drupal\Tests\vsite\ExistingSite;

use Drupal\block_content\Entity\BlockContent;

/**
 * Class VsiteValidatorTest.
 *
 * @group vsite
 * @group kernel
 *
 * @package Drupal\Tests\vsite\ExistingSite
 */
class VsiteValidatorTest extends VsiteExistingSiteTestBase {

  /**
   * Vsite Validator service.
   *
   * @var \Drupal\vsite\Helper\VsiteFieldValidateHelper
   */
  protected $vsiteValidateHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->vsiteContextManager->activateVsite($this->group);
    $this->vsiteValidateHelper = $this->container->get('vsite.validate_helper');
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'info' => 'Widget 1',
      'field_display_style' => 'title',
      'field_content_type' => 'all',
      'field_sorted_by' => 'sort_newest',
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
  }

  /**
   * Tests validation works in same vsite.
   */
  public function testUniqueFieldValueValidatorInSameVsite() {
    // Tests validation works for positive case.
    $unsaved_block = BlockContent::create([
      'type' => 'list_of_files',
      'info' => 'Widget 1',
    ]);

    $fieldData['field_name'] = $unsaved_block->get('info')->getName();
    $fieldData['item_first'] = $unsaved_block->get('info');

    $result = $this->vsiteValidateHelper->uniqueFieldValueValidator($fieldData, $unsaved_block);
    $this->assertTrue($result);

    // Tests validation works for negative case.
    $unsaved_block = BlockContent::create([
      'type' => 'list_of_files',
      'info' => 'Widget 2',
    ]);

    $fieldData['field_name'] = $unsaved_block->get('info')->getName();
    $fieldData['item_first'] = $unsaved_block->get('info');

    $result = $this->vsiteValidateHelper->uniqueFieldValueValidator($fieldData, $unsaved_block);
    $this->assertFalse($result);
  }

  /**
   * Tests validation returns correct result in different vsites.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testUniqueFieldValueValidatorInDifferentVsites() {

    $this->group2 = $this->createGroup();
    $this->vsiteContextManager->activateVsite($this->group2);

    // Tests validation returns correct result when same name is used in
    // different vsite.
    $unsaved_block = BlockContent::create([
      'type' => 'custom_text_html',
      'info' => 'Widget 1',
    ]);

    $fieldData['field_name'] = $unsaved_block->get('info')->getName();
    $fieldData['item_first'] = $unsaved_block->get('info');

    $result = $this->vsiteValidateHelper->uniqueFieldValueValidator($fieldData, $unsaved_block);
    $this->assertFalse($result);

  }

}
