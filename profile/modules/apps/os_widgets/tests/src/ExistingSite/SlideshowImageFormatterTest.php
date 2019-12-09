<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\Component\Serialization\Json;

/**
 * Class SlideshowImageFormatterTest.
 *
 * @group kernel
 * @group widgets-3
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\SlideshowWidget
 */
class SlideshowImageFormatterTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Formatter settings.
   *
   * @var array
   */
  protected $formatterSettings;

  /**
   * Testing paragraph.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph
   */
  protected $slideshowParagraph;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $image = $this->createMediaImage();
    $this->slideshowParagraph = $this->createParagraph([
      'type' => 'slideshow',
      'field_slide_image' => $image,
    ]);
    $this->formatterSettings = [
      'layout_type' => 'wide',
    ];
  }

  /**
   * Shortcut to get slideshow formatter.
   */
  protected function getSlideshowFormatter() {
    return $this->getFormatterInstance('paragraph', 'slideshow', 'field_slide_image', 'os_widgets_slideshow_image', $this->formatterSettings);
  }

  /**
   * Test render slideshow slick data (wide).
   */
  public function testRenderSlideshowSlickDataWide() {
    $formatter_instance = $this->getSlideshowFormatter();

    $elements = $formatter_instance->view($this->slideshowParagraph->get('field_slide_image'));

    $data_breakpoint_uri = Json::decode($elements[0]['image']['#image']['#attributes']['data-breakpoint_uri']);
    $this->assertSame([576, 768, 'large'], array_keys($data_breakpoint_uri));
    $this->assertContains('os_slideshow_wide_small', $data_breakpoint_uri[576]['uri']);
    $this->assertContains('os_slideshow_wide_medium', $data_breakpoint_uri[768]['uri']);
    $this->assertContains('os_slideshow_wide_large', $data_breakpoint_uri['large']['uri']);
  }

  /**
   * Test render slideshow slick data (standard).
   */
  public function testRenderSlideshowSlickDataStandard() {
    $this->formatterSettings = [
      'layout_type' => 'standard',
    ];
    $formatter_instance = $this->getSlideshowFormatter();

    $elements = $formatter_instance->view($this->slideshowParagraph->get('field_slide_image'));

    $data_breakpoint_uri = Json::decode($elements[0]['image']['#image']['#attributes']['data-breakpoint_uri']);
    $this->assertSame([576, 768, 'large'], array_keys($data_breakpoint_uri));
    $this->assertContains('os_slideshow_standard_small', $data_breakpoint_uri[576]['uri']);
    $this->assertContains('os_slideshow_standard_medium', $data_breakpoint_uri[768]['uri']);
    $this->assertContains('os_slideshow_standard_large', $data_breakpoint_uri['large']['uri']);
  }

  /**
   * Test render slideshow with media alt and title (fallback).
   */
  public function testRenderSlideshowMediaAltTitle() {
    $formatter_instance = $this->getSlideshowFormatter();
    $image = $this->createMediaImage();
    $image->field_media_image->alt = $this->randomMachineName();
    $image->field_media_image->title = $this->randomMachineName();
    $image->save();
    $slideshow_paragraph = $this->createParagraph([
      'type' => 'slideshow',
      'field_slide_image' => $image,
    ]);
    $elements = $formatter_instance->view($slideshow_paragraph->get('field_slide_image'));

    $this->assertSame($image->field_media_image->alt, $elements[0]['image']['#image']['#attributes']['alt']);
    $this->assertSame($image->field_media_image->title, $elements[0]['image']['#image']['#attributes']['title']);
  }

  /**
   * Test render slideshow with media alt and title (override).
   */
  public function testRenderSlideshowParagraphAltTitle() {
    $formatter_instance = $this->getSlideshowFormatter();
    $image = $this->createMediaImage();
    // Set to make sure it will be overridden.
    $image->field_media_image->alt = $this->randomMachineName();
    $image->field_media_image->title = $this->randomMachineName();
    $image->save();
    $slideshow_paragraph = $this->createParagraph([
      'type' => 'slideshow',
      'field_slide_image' => $image,
      'field_slide_alt_text' => $this->randomMachineName(),
      'field_slide_title_text' => $this->randomMachineName(),
    ]);
    $elements = $formatter_instance->view($slideshow_paragraph->get('field_slide_image'));

    $this->assertSame($slideshow_paragraph->get('field_slide_alt_text')->getString(), $elements[0]['image']['#image']['#attributes']['alt']);
    $this->assertSame($slideshow_paragraph->get('field_slide_title_text')->getString(), $elements[0]['image']['#image']['#attributes']['title']);
  }

  /**
   * Test render slideshow with media link.
   */
  public function testRenderSlideshowWithLink() {
    $formatter_instance = $this->getSlideshowFormatter();
    $image = $this->createMediaImage();
    $slideshow_paragraph = $this->createParagraph([
      'type' => 'slideshow',
      'field_slide_image' => $image,
      'field_slide_link' => 'http://' . $this->randomMachineName() . '.com',
    ]);
    $elements = $formatter_instance->view($slideshow_paragraph->get('field_slide_image'));

    $this->assertSame($slideshow_paragraph->get('field_slide_link')->getString(), $elements[0]['image']['#url']);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    $this->formatterSettings = NULL;
    $this->slideshowParagraph = NULL;
  }

}
