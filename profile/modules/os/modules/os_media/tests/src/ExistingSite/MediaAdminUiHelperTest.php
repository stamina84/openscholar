<?php

namespace Drupal\Tests\os_media\ExistingSite;

use Drupal\node\NodeInterface;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * MediaAdminUiHelperTest.
 *
 * @group kernel
 * @group other
 * @coversDefaultClass \Drupal\os_media\MediaAdminUiHelper
 */
class MediaAdminUiHelperTest extends OsExistingSiteTestBase {

  /**
   * Media admin UI helper.
   *
   * @var \Drupal\os_media\MediaAdminUiHelper
   */
  protected $mediaAdminUiHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->mediaAdminUiHelper = $this->container->get('os_media.media_admin_ui_helper');
  }

  /**
   * @covers ::getMediaUsageInNodes
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testGetMediaUsageInNodes(): void {
    $pdf_media = $this->createMedia([], 'pdf');
    $tar_media = $this->createMedia([
      'bundle' => [
        'target_id' => 'executable',
      ],
    ], 'tar');
    $non_existing_media_id = INF;
    $non_matching_title = 'Charles';

    // Negative tests.
    // Assert for unpublished node.
    $faq = $this->createNode([
      'type' => 'faq',
      'title' => "Witch's Cauldron",
      'field_attached_media' => [
        'target_id' => $pdf_media->id(),
      ],
      'status' => NodeInterface::NOT_PUBLISHED,
    ]);

    $usages = $this->mediaAdminUiHelper->getMediaUsageInNodes($pdf_media->id());
    $this->assertEmpty($usages);

    // Assert non-existing media.
    $usages = $this->mediaAdminUiHelper->getMediaUsageInNodes($non_existing_media_id);
    $this->assertEmpty($usages);

    // Assert non-existing title.
    $usages = $this->mediaAdminUiHelper->getMediaUsageInNodes($pdf_media->id(), $non_matching_title);
    $this->assertEmpty($usages);

    // Positive tests.
    // Assert for field_attached_media.
    $faq->setPublished(TRUE)->save();
    $usages = $this->mediaAdminUiHelper->getMediaUsageInNodes($pdf_media->id());
    $this->assertCount(1, $usages);
    $this->assertEqual($usages[0]->id(), $faq->id());

    // Assert for field_attached_media OR field_presentation_slides.
    $presentation = $this->createNode([
      'type' => 'presentation',
      'title' => "Witch's Cauldron",
      'field_presentation_slides' => [
        'target_id' => $pdf_media->id(),
      ],
    ]);

    $usages = $this->mediaAdminUiHelper->getMediaUsageInNodes($pdf_media->id());
    $this->assertCount(2, $usages);
    $usage_nids = array_map(static function (NodeInterface $node) {
      return $node->id();
    }, $usages);
    $this->assertContains($faq->id(), $usage_nids);
    $this->assertContains($presentation->id(), $usage_nids);

    // Assert for field_software_package.
    $software_release = $this->createNode([
      'type' => 'software_release',
      'field_software_package' => [
        'target_id' => $tar_media->id(),
      ],
    ]);

    $usages = $this->mediaAdminUiHelper->getMediaUsageInNodes($tar_media->id());
    $this->assertCount(1, $usages);
    $this->assertEqual($usages[0]->id(), $software_release->id());

    // Assert for title.
    $usages = $this->mediaAdminUiHelper->getMediaUsageInNodes($pdf_media->id(), 'itch');
    $this->assertCount(2, $usages);
    $usage_nids = array_map(static function (NodeInterface $node) {
      return $node->id();
    }, $usages);
    $this->assertContains($faq->id(), $usage_nids);
    $this->assertContains($presentation->id(), $usage_nids);
  }

}
