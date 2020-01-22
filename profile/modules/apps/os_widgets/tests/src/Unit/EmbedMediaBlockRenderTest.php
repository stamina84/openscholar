<?php

namespace Drupal\Tests\os_widgets\Unit;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldItemList;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\os_widgets\Plugin\OsWidgets\EmbedMediaWidget;
use Drupal\Tests\UnitTestCase;

/**
 * Class EmbedMediaWidget.
 *
 * @group unit
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\EmbedMediaWidget
 */
class EmbedMediaBlockRenderTest extends UnitTestCase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\EmbedMediaWidget
   */
  protected $embedMediaWidget;
  protected $connectionMock;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $entity_type_manager = $this->createMock(EntityTypeManager::class);
    $this->connectionMock = $this->createMock(Connection::class);
    $this->embedMediaWidget = new EmbedMediaWidget([], '', [], $entity_type_manager, $this->connectionMock);
  }

  /**
   * Test build function with width.
   */
  public function testBuildWithWidth() {
    $field_values = [
      'field_max_width' => [
        [
          'value' => 333,
        ],
      ],
      'field_media_select' => [
        [
          'alt' => 'Alt test',
          'title' => 'Title test',
        ],
      ],
    ];
    $block_content = $this->createBlockContentImageMock($field_values['field_max_width'], $field_values['field_media_select']);
    $build = [];
    $this->embedMediaWidget->buildBlock($build, $block_content);
    $this->assertSame(333, $build['embed_media'][0]['#width']);
    $this->assertSame('Alt test', $build['embed_media'][0]['#alt']);
    $this->assertSame('Title test', $build['embed_media'][0]['#title']);
  }

  /**
   * Test build function without width.
   */
  public function testBuildWithoutWidth() {
    $field_values = [
      'field_max_width' => [
        [
          'value' => NULL,
        ],
      ],
      'field_media_select' => [
        [
          'alt' => 'Alt test',
          'title' => 'Title test',
        ],
      ],
    ];
    $block_content = $this->createBlockContentImageMock($field_values['field_max_width'], $field_values['field_media_select']);
    $build = [];
    $this->embedMediaWidget->buildBlock($build, $block_content);
    $this->assertSame(0, $build['embed_media'][0]['#width']);
    $this->assertSame('Alt test', $build['embed_media'][0]['#alt']);
    $this->assertSame('Title test', $build['embed_media'][0]['#title']);
  }

  /**
   * Test build function with Video embed media type.
   */
  public function testBuildWithVideoEmbed() {
    $field_values = [
      'field_max_width' => [],
    ];
    $entity_view_builder = $this->createMock(EntityViewBuilder::class);
    $entity_view_builder->method('view')
      ->willReturn(['#view_mode' => 'default']);
    $entity_type_manager = $this->createMock(EntityTypeManager::class);
    $entity_type_manager->method('getViewBuilder')
      ->willReturn($entity_view_builder);

    $block_content = $this->createBlockContentVideoMock($field_values['field_max_width']);
    $build = [];
    $embed_media_widget = new EmbedMediaWidget([], '', [], $entity_type_manager, $this->connectionMock);
    $embed_media_widget->buildBlock($build, $block_content);
    $this->assertSame('default', $build['embed_media'][0]['#view_mode']);
  }

  /**
   * Create a block content mock with image media type for testing.
   */
  protected function createBlockContentImageMock(array $field_max_width_values, array $field_media_select_values) {
    $block_content = $this->createMock(BlockContent::class);

    // field_max_width Mock.
    $field_max_width = $this->createMock(FieldItemList::class);
    $field_max_width->method('getValue')
      ->willReturn($field_max_width_values);

    // field_media_select Mock.
    $field_media_select = $this->createMock(EntityReferenceFieldItemList::class);
    $media = $this->createMock(Media::class);
    $media->method('bundle')
      ->willReturn('image');
    $file = $this->createMock(File::class);
    $file->method('getFileUri')
      ->willReturn('public://file.jpg');
    $field_media_image = $this->createMock(EntityReferenceFieldItemList::class);
    $field_media_image->method('referencedEntities')
      ->willReturn([$file]);
    $field_media_image->method('getValue')
      ->willReturn($field_media_select_values);
    $media->method('get')
      ->willReturn($field_media_image);
    $field_media_select->method('referencedEntities')
      ->willReturn([$media]);

    // Can't work on with(field_max_width).
    $block_content->expects($this->at(0))
      ->method('get')
      ->willReturn($field_max_width);
    $block_content->expects($this->at(1))
      ->method('get')
      ->willReturn($field_media_select);

    return $block_content;
  }

  /**
   * Create a block content mock with video media type for testing.
   */
  protected function createBlockContentVideoMock(array $field_max_width_values) {
    $block_content = $this->createMock(BlockContent::class);

    // field_max_width Mock.
    $field_max_width = $this->createMock(FieldItemList::class);
    $field_max_width->method('getValue')
      ->willReturn($field_max_width_values);

    // field_media_select Mock.
    $field_media_select = $this->createMock(EntityReferenceFieldItemList::class);
    $media = $this->createMock(Media::class);
    $media->method('bundle')
      ->willReturn('remote');
    $field_media_select->method('referencedEntities')
      ->willReturn([$media]);

    // Can't work on with(field_max_width).
    $block_content->expects($this->at(0))
      ->method('get')
      ->willReturn($field_max_width);
    $block_content->expects($this->at(1))
      ->method('get')
      ->willReturn($field_media_select);

    return $block_content;
  }

}