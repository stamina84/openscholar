<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class SlideshowBlockRenderTest.
 *
 * @group kernel
 * @group widgets-3
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
    $this->assertSame(2, $breakpoints);
    $responsive_options = $slick_optionset->getResponsiveOptions();
    $this->assertSame(576, $responsive_options[0]['breakpoint']);
    $this->assertSame('slider', $responsive_options[0]['settings']['respondTo']);
    $this->assertSame(768, $responsive_options[1]['breakpoint']);
    $this->assertSame('slider', $responsive_options[1]['settings']['respondTo']);
    $this->assertSame('slideshow_wide', $render['field_slideshow']['#build']['settings']['view_mode']);
    $this->assertNotEmpty($render['field_slideshow']['#build']['items']);
    $this->assertSame('slideshow_wide', $render['field_slideshow']['#build']['items'][0]['#view_mode']);
    // Checking layout depend class.
    $this->assertSame('slideshow-layout-3-1-overlay', $render['#attributes']['class'][0]);
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
    // Checking layout depend class.
    $this->assertSame('slideshow-layout-16-9-overlay', $render['#attributes']['class'][0]);
  }

  /**
   * Test render slideshow with all fields.
   */
  public function testRenderSlideshowWithAllFields() {
    $image = $this->createMediaImage();
    $slideshow_paragraph = $this->createParagraph([
      'type' => 'slideshow',
      'field_slide_image' => $image,
      'field_slide_link' => 'http://' . $this->randomMachineName() . '.com',
      'field_slide_alt_text' => $this->randomMachineName(),
      'field_slide_title_text' => $this->randomMachineName(),
      'field_slide_description' => $this->randomMachineName(),
      'field_slide_headline' => $this->randomMachineName(),
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
    $this->group->addContent($image, 'group_entity:media');

    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $markup = $this->container->get('renderer')->renderRoot($render);

    $this->assertContains($slideshow_paragraph->get('field_slide_link')->getString(), $markup->__toString());
    $this->assertContains($slideshow_paragraph->get('field_slide_alt_text')->getString(), $markup->__toString());
    $this->assertContains($slideshow_paragraph->get('field_slide_title_text')->getString(), $markup->__toString());
    $this->assertContains($slideshow_paragraph->get('field_slide_description')->getString(), $markup->__toString());
    $this->assertContains($slideshow_paragraph->get('field_slide_headline')->getString(), $markup->__toString());
  }

}
