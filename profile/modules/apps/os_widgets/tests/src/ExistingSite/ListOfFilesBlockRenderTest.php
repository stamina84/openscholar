<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class ListOfFilesBlockRenderTest.
 *
 * @group kernel
 * @group widgets-4
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\ListOfPostsWidget
 */
class ListOfFilesBlockRenderTest extends OsWidgetsExistingSiteTestBase {
  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\ListOfFilesWidget
   */
  protected $lofWidget;

  /**
   * View builder service.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->lofWidget = $this->osWidgets->createInstance('list_of_files_widget');
    $this->vsiteContextManager->activateVsite($this->group);
    $this->viewBuilder = $this->entityTypeManager->getViewBuilder('block_content');
  }

  /**
   * Test listing for All media without pager.
   */
  public function testBuildListingTypesWithoutPager() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_files',
      'field_display_style_lof' => 'title',
      'field_file_type' => 'all',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteMedia($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<ul  id="list-of-files"', $markup->__toString());
    $this->assertContains('MediaImage1</a></li>', $markup->__toString());
    $this->assertContains('Document1</a></li>', $markup->__toString());
    $this->assertContains('class="title-lof', $markup->__toString());
    $this->assertContains('Audio1</a></li>', $markup->__toString());
  }

  /**
   * Test listing for All media with pager and alphabetical filter.
   */
  public function testBuildListingTypesWithPager() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_files',
      'field_display_style_lof' => 'title',
      'field_file_type' => 'all',
      'field_sorted_by_lof' => 'sort_alpha',
      'field_number_of_items_to_display' => 2,
    ]);
    $block_id = $block_content->id();

    $this->createVsiteMedia($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('1 of 3', $markup->__toString());
    $this->assertContains("/refresh-widget-content/$block_id?page=1", $markup->__toString());
    $this->assertContains('Audio1</a></li>', $markup->__toString());
    $this->assertContains('Document1</a></li>', $markup->__toString());
  }

  /**
   * Test full content listing for All Media.
   */
  public function testListingWithFullMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_files',
      'field_display_style_lof' => 'full',
      'field_file_type' => 'all',
      'field_sorted_by_lof' => 'sort_newest',
      'field_number_of_items_to_display' => 2,
    ]);

    $this->createVsiteMedia($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('field--mode-full', $markup->__toString());
    $this->assertContains('<img src="/sites/default/files/styles/os_image_large', $markup->__toString());
  }

  /**
   * Test Link without icon mode listing.
   */
  public function testListingLinkWithoutIconMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_files',
      'field_display_style_lof' => 'link',
      'field_file_type' => 'all',
      'field_sorted_by_lof' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteMedia($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('class="link-lof"', $markup->__toString());
    $this->assertContains('MediaImage1</a>', $markup->__toString());
  }

  /**
   * Test Link with Icon mode listing.
   */
  public function testListingWithSidebarTeaserMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_files',
      'field_display_style_lof' => 'link_icon',
      'field_file_type' => 'all',
      'field_sorted_by_lof' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteMedia($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('lof/icons/image-x-generic.svg', $markup->__toString());
    $this->assertContains('lof/icons/text-x-generic.svg', $markup->__toString());
    $this->assertContains('class="link_icon-lof"', $markup->__toString());
  }

  /**
   * Test Teaser mode listing.
   */
  public function testListingWithTeaserMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_files',
      'field_display_style_lof' => 'teaser',
      'field_file_type' => 'all',
      'field_sorted_by_lof' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteMedia($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('class="teaser-lof"', $markup->__toString());
    $this->assertContains('field--mode-teaser', $markup->__toString());
    $this->assertContains('class="download-link">Download</a>', $markup->__toString());
  }

  /**
   * Test Grid mode for Images.
   */
  public function testListingWithGridMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_files',
      'field_display_style_lof' => 'teaser',
      'field_file_type' => 'image',
      'field_layout' => 'grid',
      'field_columns' => 3,
      'field_sorted_by_lof' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteMedia($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('class="lof-grid lof-grid-3', $markup->__toString());
    $this->assertContains('field--mode-grid_teaser', $markup->__toString());
    $this->assertContains('styles/os_image_landscape', $markup->__toString());
  }

}
