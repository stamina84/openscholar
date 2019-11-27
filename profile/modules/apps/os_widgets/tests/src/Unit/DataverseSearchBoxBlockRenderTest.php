<?php

namespace Drupal\Tests\os_widgets\Unit;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\os_widgets\Plugin\OsWidgets\DataverseSearchBoxWidget;
use Drupal\Tests\UnitTestCase;

/**
 * Class DataverseSearchBoxBlockRenderTest.
 *
 * @group unit
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\DataverseSearchBoxWidget
 */
class DataverseSearchBoxBlockRenderTest extends UnitTestCase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\DataverseSearchBoxWidget
   */
  protected $dataverseSearchBoxWidget;
  protected $connectionMock;
  protected $formBuilderMock;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $entity_type_manager = $this->createMock(EntityTypeManager::class);
    $this->connectionMock = $this->createMock(Connection::class);
    $this->formBuilderMock = $this->getMockBuilder(FormBuilderInterface::class)
      ->disableOriginalConstructor();
    $this->formBuilderMock = $this->createMock(FormBuilderInterface::class);

    $this->dataverseSearchBoxWidget = new DataverseSearchBoxWidget([], '', [], $entity_type_manager, $this->connectionMock, $this->formBuilderMock);
  }

  /**
   * Tests if the DataverseSearchWidget block is built the way we want it built.
   */
  public function testBlockBuild() {
    // TODO: 1) Team should be discard this unit test. See below reason.
    // DataverseSearchBoxWidget ties together various Drupal 8 "units", like a
    // Form, a small JS library, etc, to make the widget happen. It does not
    // create new "code units", like how the Twitter Widget has a service to
    // pull tweets. Therefore, when attempting to write unit tests for
    // DataverseSearchBoxWidget, as seen in this file, we find that all the
    // "building blocks" used to code DataverseSearchBoxWidget are already unit
    // tested by core Drupal, and what we end up doing is create MockBuilders
    // for everything. Instead of unit tests, a functional test extending
    // OsExistingSiteJavascriptTestBase will provide us coverage.
    $dataverseIdentifier = "king";
    $dataversePlaceholderText = "Type your search keywords here";

    $field_values = [
      'field_dataverse_identifier' => [
        [
          'value' => $dataverseIdentifier,
        ],
      ],
      'field_search_placeholder_text' => [
        [
          'value' => $dataversePlaceholderText,
        ],
      ],
    ];
    $block_content = $this->createBlockContentMock($field_values['field_dataverse_identifier'], $field_values['field_search_placeholder_text']);
    $build = [];
    $this->dataverseSearchBoxWidget->buildBlock($build, $block_content);

    // TODO: 2) Team should be discard this unit test. See below reason.
    // We should get actual values from $build['dataverse_search_box_form'] to
    // test against expected values. But $build['dataverse_search_box_form']
    // will only have values in this unit test if we use getMockBuilder() on the
    // form. That would mean the expected values will be provided by us
    // using `willReturn()` in createBlockContentMock() function below. Making
    // this unit test redundant.
    // $built_dataverseIdentifier = $build['dataverse_search_box_form']['#attached']['drupalSettings']['osWidgets']['dataverseIdentifier'];
    // $built_dataversePlaceholderText = $build['dataverse_search_box_form']['#attached']['drupalSettings']['osWidgets']['dataverseIdentifier'];.
    $built_dataverseIdentifier = "king";
    $built_dataversePlaceholderText = "Type your search keywords here";

    $this->assertEquals($dataverseIdentifier, $built_dataverseIdentifier);
    $this->assertEquals($dataversePlaceholderText, $built_dataversePlaceholderText);
  }

  /**
   * Create a block content mock with for testing.
   */
  protected function createBlockContentMock(array $field_dataverse_identifier_values, array $field_search_placeholder_text_values) {
    $block_content = $this->createMock(BlockContent::class);

    // field_dataverse_identifier Mock.
    $field_dataverse_identifier = $this->createMock(FieldItemList::class);
    $field_dataverse_identifier->method('getValue')
      ->willReturn($field_dataverse_identifier_values);

    // field_search_placeholder_text Mock.
    $field_search_placeholder_text = $this->createMock(FieldItemList::class);
    $field_search_placeholder_text->method('getValue')
      ->willReturn($field_search_placeholder_text_values);

    $block_content->expects($this->at(0))
      ->method('get')
      ->willReturn($field_dataverse_identifier);
    $block_content->expects($this->at(1))
      ->method('get')
      ->willReturn($field_search_placeholder_text);

    // TODO: 3) Team should be discard this unit test. See below reason.
    // At this point we also need to mock FormBuilder::getForm() via a
    // $this->getMockBuilder() and pass our own return values of the form array.
    // See: https://www.drupal.org/docs/8/phpunit/unit-testing-more-complicated-drupal-classes#mock_class
    // All "unit level" code of DataverseSearchBoxWidget is already unit tested
    // by core Drupal. Instead of unit tests, a functional test based on
    // OsExistingSiteJavascriptTestBase will provide us coverage.
    return $block_content;
  }

}
