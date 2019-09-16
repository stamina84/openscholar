<?php

namespace Drupal\Tests\os_media\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * MediaHelperTest.
 *
 * @group kernel
 * @group other
 */
class MediaHelperTest extends ExistingSiteBase {

  /**
   * The Media Helper service.
   *
   * @var \Drupal\os_media\MediaEntityHelper
   */
  protected $mediaHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->mediaHelper = $this->container->get('os_media.media_helper');
  }

  /**
   * Tests Field Mappings.
   */
  public function testGetField(): void {
    $actual = $this->mediaHelper->getField('image');
    $this->assertEquals('field_media_image', $actual);

    $actual = $this->mediaHelper->getField('video');
    $this->assertEquals('field_media_video_file', $actual);

    $actual = $this->mediaHelper->getField('document');
    $this->assertEquals('field_media_file', $actual);

    $this->assertEquals('filename', $this->mediaHelper::FILE_FIELDS[0]);
  }

}
