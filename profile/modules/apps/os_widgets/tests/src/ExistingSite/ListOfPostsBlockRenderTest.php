<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class ListOfPostsBlockRenderTest.
 *
 * @group kernel
 * @group widgets-2
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\ListOfPostsBlockRenderTest
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
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent();

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<ul  id="list-of-posts">', $markup->__toString());
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

    $this->createVsiteContent();

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
    ]);

    $this->createVsiteContent();

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
  public function testBuildListingWithFullContent() {
    $block_content = $this->createBlockContent([
      'type' => 'list_of_posts',
      'field_display_style' => 'default',
      'field_content_type' => 'blog',
      'field_number_of_items_to_display' => 6,
    ]);

    $this->createVsiteContent();

    $render = $this->viewBuilder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('<article role="article"', $markup->__toString());
    $this->assertContains('blog/blog" class="default blog">', $markup->__toString());
  }

}
