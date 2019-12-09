<?php

namespace Drupal\Tests\os_office_embed\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class OsOfficeEmbedFormatterTest.
 *
 * @group kernel
 * @group other
 * @covers \Drupal\os_office_embed\Plugin\Field\FieldFormatter\OsOfficeEmbedFormatter
 */
class OsOfficeEmbedFormatterTest extends OsExistingSiteTestBase {

  /**
   * Testing document.
   *
   * @var \Drupal\media\Entity\Media
   */
  protected $document;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->document = $this->createMedia();
  }

  /**
   * Test build embed office values.
   */
  public function testBuildEmbedOffice() {
    $media_files = $this->document->get('field_media_file');
    $entities = $media_files->referencedEntities();
    $build = $media_files->view([
      'type' => 'os_office_embed',
    ]);
    $this->assertSame('os_office_embed', $build[0]['#theme']);
    $this->assertSame($entities[0]->getFileName(), $build[0]['#filename']);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->document = NULL;
  }

}
