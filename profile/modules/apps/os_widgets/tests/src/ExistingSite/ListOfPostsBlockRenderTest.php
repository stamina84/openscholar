<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class ListOfPostsBlockRenderTest.
 *
 * @group kernel
 * @group widgets-4
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\ListOfPostsWidget
 */
class ListOfPostsBlockRenderTest extends OsWidgetsExistingSiteTestBase {
  /**
   * The object we're testing.
   *
   * @var \Drupal\os_widgets\Plugin\OsWidgets\ListOfPostsWidget
   */
  protected $lopWidget;

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
    $this->lopWidget = $this->osWidgets->createInstance('list_of_posts_widget');
    $this->vsiteContextManager->activateVsite($this->group);
    $this->viewBuilder = $this->entityTypeManager->getViewBuilder('block_content');
  }

  /**
   * Test listing for All content without pager.
   */
  public function testBuildListingTypesWithoutPager() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'title',
      'field_content_type' => 'all',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<ul  id="list-of-posts"', $markup->__toString());
    $this->assertContains('Blog</a></li>', $markup->__toString());
    $this->assertContains('News</a></li>', $markup->__toString());
    $this->assertContains('Publication1</a></li>', $markup->__toString());
    $this->assertContains('Publication2</a></li>', $markup->__toString());
  }

  /**
   * Test listing for All content with pager and alphabetical filter.
   */
  public function testBuildListingTypesWithPager() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'title',
      'field_content_type' => 'all',
      'field_number_of_items_to_display' => 2,
      'field_sorted_by' => 'sort_alpha',
    ]);
    $block_id = $block_content->id();

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('1 of 2', $markup->__toString());
    $this->assertContains("/refresh-widget-content/$block_id?page=1", $markup->__toString());
    $this->assertContains('Blog</a></li>', $markup->__toString());
    $this->assertContains('News</a></li>', $markup->__toString());
  }

  /**
   * Test listing for Blog content type.
   */
  public function testBuildListingWithContentTypeFilter() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'title',
      'field_content_type' => 'blog',
      'field_number_of_items_to_display' => 6,
      'field_sorted_by' => 'sort_newest',
    ]);

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('Blog</a></li>', $markup->__toString());
    $this->assertNotContains('News</a></li>', $markup->__toString());
  }

  /**
   * Test full content listing for Blog content type.
   */
  public function testBuildListingWithFullContentMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'default',
      'field_content_type' => 'blog',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<article role="article"', $markup->__toString());
    $this->assertContains('blog/blog', $markup->__toString());
  }

  /**
   * Test Slide Teaser mode listing for Blog content type.
   */
  public function testBuildListingWithSlideTeaserMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'slide_teaser',
      'field_content_type' => 'blog',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<article role="article"', $markup->__toString());
    $this->assertContains('slide_teaser blog', $markup->__toString());
  }

  /**
   * Test Sidebar Teaser mode listing for News content type.
   */
  public function testBuildListingWithSidebarTeaserMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'sidebar_teaser',
      'field_content_type' => 'news',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<article role="article"', $markup->__toString());
    $this->assertContains('news sidebar-teaser', $markup->__toString());
  }

  /**
   * Test No Image Teaser mode listing for Person content type.
   */
  public function testBuildListingWithNoImageMode() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'no_image_teaser',
      'field_content_type' => 'person',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $person = $this->createNode([
      'type' => 'person',
      'title' => 'Mr Person Test',
    ]);

    $this->group->addContent($person, 'group_node:person');

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<article role="article"', $markup->__toString());
    $this->assertContains('person no-image-teaser', $markup->__toString());
  }

  /**
   * Test listing for Artwork Publication content type.
   */
  public function testBuildListingWithContentTypeFilterPublication() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'title',
      'field_content_type' => 'publications',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('Publication1</a></li>', $markup->__toString());
    $this->assertContains('Publication2</a></li>', $markup->__toString());
  }

  /**
   * Test full content listing for Publication content type.
   */
  public function testBuildListingWithFullContentPublication() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'default',
      'field_content_type' => 'publications',
      'field_sorted_by' => 'sort_newest',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent($this->group);

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<article class="bibcite-reference">', $markup->__toString());
    $this->assertContains('publications/publication1', strtolower($markup->__toString()));
  }

}