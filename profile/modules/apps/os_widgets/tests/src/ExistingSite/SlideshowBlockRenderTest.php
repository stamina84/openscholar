<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\Component\Serialization\Json;

/**
 * Class SlideshowBlockRenderTest.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\SlideshowWidget
 */
class SlideshowBlockRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test render add slideshow link.
   */
  public function testRenderAddSlideshowLink() {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);

    $this->assertNotEmpty($render['add_slideshow_button']);
    $this->assertSame('link', $render['add_slideshow_button']['#type']);
    $this->assertSame('route:os_widgets.add_slideshow;block_content=' . $block_content->id(), $render['add_slideshow_button']['#url']->toUriString());
  }

  /**
   * Test render slideshow slick data (wide).
   */
  public function testRenderSlideshowSlickDataWide() {
    $image1 = $this->createMediaImage();
    $slideshow_paragraph = $this->createParagraph([
      'type' => 'slideshow',
      'field_slide_image' => $image1,
    ]);
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
      'field_slideshow' => [
        $slideshow_paragraph,
      ],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->group->addContent($image1, 'group_entity:media');

    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);

    $this->assertNotEmpty($render['field_slideshow']['#build']);
    $this->assertSame('slideshow_wide', $render['field_slideshow']['#build']['settings']['view_mode']);
    $this->assertSame('os_slideshow', $render['field_slideshow']['#build']['settings']['optionset']);
    /** @var \Drupal\slick\Entity\Slick $slick_optionset */
    $slick_optionset = $render['field_slideshow']['#build']['optionset'];
    $breakpoints = $slick_optionset->getBreakpoints();
    $this->assertSame(3, $breakpoints);
    $responsive_options = $slick_optionset->getResponsiveOptions();
    $this->assertSame(300, $responsive_options[0]['breakpoint']);
    $this->assertSame('slider', $responsive_options[0]['settings']['respondTo']);
    $this->assertSame(600, $responsive_options[1]['breakpoint']);
    $this->assertSame('slider', $responsive_options[1]['settings']['respondTo']);
    $this->assertSame(900, $responsive_options[2]['breakpoint']);
    $this->assertSame('slider', $responsive_options[2]['settings']['respondTo']);
    $this->assertSame('slideshow_wide', $render['field_slideshow']['#build']['settings']['view_mode']);
    $this->assertNotEmpty($render['field_slideshow']['#build']['items']);
    $this->assertSame('slideshow_wide', $render['field_slideshow']['#build']['items'][0]['#view_mode']);
    $data_breakpoint_uri = Json::decode($render['field_slideshow']['#build']['items'][0]['#attributes']['data-breakpoint_uri']);
    $this->assertSame([300, 600, 900], array_keys($data_breakpoint_uri));
    $this->assertContains('os_slideshow_wide_small', $data_breakpoint_uri[300]['uri']);
    $this->assertContains('os_slideshow_wide_medium', $data_breakpoint_uri[600]['uri']);
    $this->assertContains('os_slideshow_wide_large', $data_breakpoint_uri[900]['uri']);
  }

  /**
   * Test render slideshow slick data (standard view mode).
   */
  public function testRenderSlideshowSlickDataStandardLayout() {
    $image1 = $this->createMediaImage();
    $slideshow_paragraph = $this->createParagraph([
      'type' => 'slideshow',
      'field_slide_image' => $image1,
    ]);
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
      'field_slideshow_layout' => '16_9_overlay',
      'field_slideshow' => [
        $slideshow_paragraph,
      ],
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->group->addContent($image1, 'group_entity:media');

    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);

    $this->assertNotEmpty($render['field_slideshow']['#build']);
    $this->assertSame('slideshow_standard', $render['field_slideshow']['#build']['settings']['view_mode']);
    $this->assertSame('slideshow_standard', $render['field_slideshow']['#build']['settings']['view_mode']);
    $this->assertNotEmpty($render['field_slideshow']['#build']['items']);
    $this->assertSame('slideshow_standard', $render['field_slideshow']['#build']['items'][0]['#view_mode']);
    $data_breakpoint_uri = Json::decode($render['field_slideshow']['#build']['items'][0]['#attributes']['data-breakpoint_uri']);
    $this->assertSame([300, 600, 900], array_keys($data_breakpoint_uri));
    $this->assertContains('os_slideshow_standard_small', $data_breakpoint_uri[300]['uri']);
    $this->assertContains('os_slideshow_standard_medium', $data_breakpoint_uri[600]['uri']);
    $this->assertContains('os_slideshow_standard_large', $data_breakpoint_uri[900]['uri']);
  }

}
