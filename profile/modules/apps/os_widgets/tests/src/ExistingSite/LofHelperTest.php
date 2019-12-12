<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Class LofHelperTest.
 *
 * @group kernel
 * @group widgets-4
 * @covers \Drupal\os_widgets\Helper\ListWidgetsHelper
 */
class LofHelperTest extends OsWidgetsExistingSiteTestBase {

  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\ListOfFilesWidget
   */
  protected $lofWidget;

  /**
   * View builder service.
   *
   * @var \Drupal\os_widgets\Helper\ListWidgetsHelperInterface
   */
  protected $lofHelper;

  /**
   * Media ids.
   *
   * @var array
   */
  protected $mids;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->lofHelper = $this->container->get('os_widgets.list_widgets_helper');
    $this->vsiteContextManager->activateVsite($this->group);

    // Create media entities and get ids.
    $this->entities = $this->createVsiteMedia($this->group);
    /** @var \Drupal\media\Entity\Media $media */
    foreach ($this->entities as $media) {
      $this->mids[] = $media->id();
    }
  }

  /**
   * Test Get results sorting.
   */
  public function testLofHelperGetResultsSorting() : void {

    // Tests Newest Sort.
    $results = $this->lofHelper->getLofResults($this->mids, 'sort_newest');
    $this->assertEquals('Document1', $results[0]->name);

    // Test oldest sort.
    $results = $this->lofHelper->getLofResults($this->mids, 'sort_oldest');
    $this->assertEquals('MediaImage1', $results[0]->name);

    // Test alphabetical sort.
    $results = $this->lofHelper->getLofResults($this->mids, 'sort_alpha');
    $this->assertEquals('Audio1', $results[0]->name);

    // Test random sort.
    $results = $this->lofHelper->getLofResults($this->mids, 'sort_random');
    $this->assertNotEmpty($results);

  }

  /**
   * Test get media icon.
   */
  public function testLofHelperGetMediaIcon() : void {
    $mapping = [
      'image' => 'field_media_image',
      'audio' => 'field_media_file',
      'document' => 'field_media_file',
    ];
    // Test for image.
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['image1'], $mapping);
    $this->assertEquals('image-x-generic.svg', $icon_type);

    // Test for audio.
    $file = File::load($this->entities['audio1']->get($mapping['audio'])->getValue()[0]['target_id']);
    $file->set('filemime', 'audio')->save();
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['audio1'], $mapping);
    $this->assertEquals('audio-x-generic.svg', $icon_type);

    // Test for document.
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['document1'], $mapping);
    $this->assertEquals('text-x-generic.svg', $icon_type);

    // Test for embeds.
    /** @var \Drupal\media\Entity\Media $embed */
    $embed = Media::create(['bundle' => 'oembed']);
    $icon_type = $this->lofHelper->getMediaIcon($embed, $mapping);
    $this->assertEquals('video-x-generic.svg', $icon_type);

    // Test for pdf.
    $file = File::load($this->entities['document1']->get($mapping['document'])->getValue()[0]['target_id']);
    $file->set('filemime', 'pdf')->save();
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['document1'], $mapping);
    $this->assertEquals('application-pdf.svg', $icon_type);

    // Test for spreadsheet.
    $file = File::load($this->entities['document1']->get($mapping['document'])->getValue()[0]['target_id']);
    $file->set('filemime', 'sheet')->save();
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['document1'], $mapping);
    $this->assertEquals('x-office-spreadsheet.svg', $icon_type);

    // Test for word.
    $file = File::load($this->entities['document1']->get($mapping['document'])->getValue()[0]['target_id']);
    $file->set('filemime', 'word')->save();
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['document1'], $mapping);
    $this->assertEquals('x-office-document.svg', $icon_type);

    // Test for zip.
    $file = File::load($this->entities['document1']->get($mapping['document'])->getValue()[0]['target_id']);
    $file->set('filemime', 'zip')->save();
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['document1'], $mapping);
    $this->assertEquals('package-x-generic.svg', $icon_type);

    // Test for presentation.
    $file = File::load($this->entities['document2']->get($mapping['document'])->getValue()[0]['target_id']);
    $file->set('filemime', 'presentation')->save();
    $icon_type = $this->lofHelper->getMediaIcon($this->entities['document2'], $mapping);
    $this->assertEquals('x-office-presentation.svg', $icon_type);
  }

  /**
   * Test add widget mini pager.
   */
  public function testLofHelperAddWidgetPager() : void {
    $pager = [
      'total_count' => 5,
      'numItems' => 3,
      'page' => pager_find_page(),
    ];
    $blockData = [
      'block_id' => 1,
      'block_attribute_id' => 'attribute_test_id',
      'moreLinkId' => 'more_test_id',
    ];
    $build = [];
    $this->lofHelper->addWidgetMiniPager($build, $pager, $blockData);
    $this->assertArrayHasKey('#pager', $build['render_content']);
    $this->assertArrayHasKey('#theme', $build['render_content']['#pager']);
    $this->assertArrayHasKey('#next_link', $build['render_content']['#pager']);
    $this->assertArrayHasKey('#prev_link', $build['render_content']['#pager']);
    $this->assertArrayHasKey('#pager_id', $build['render_content']['#pager']);
  }

  /**
   * Test Prepend purl helper method.
   */
  public function testPrependPurl(): void {
    $vsite_alias = $this->group->get('path')->getValue()[0]['alias'];
    // Test with no vsite in url.
    $url = 'internal:/blog';
    $url_raw = 'blog';
    $result = $this->lofHelper->prependPurl($url, $url_raw);
    $this->assertEquals("internal:$vsite_alias/blog", $result);

    // Test with vsite in url.
    $url = "internal:$vsite_alias/blog";
    $url_raw = "$vsite_alias/blog";
    $result = $this->lofHelper->prependPurl($url, $url_raw);
    $this->assertEquals("internal:$vsite_alias/blog", $result);
  }

}
