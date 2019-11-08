<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

/**
 * Class FollowMeBlockRenderTest.
 *
 * @group kernel
 * @group widgets
 * @covers \Drupal\os_widgets\Plugin\OsWidgets\FollowMeWidget
 */
class FollowMeBlockRenderTest extends OsWidgetsExistingSiteTestBase {

  /**
   * Test build function display block.
   */
  public function testBuildDisplay() {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'follow_me',
      'field_widget_title' => 'test',
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);

    $this->assertSame('os_widgets/followMeWidget', $render['follow_me']['#attached']['library'][0]);
    $this->assertEquals('os_widgets_follow_me', $render['follow_me']['#theme']);
    $this->assertContains('rrss', $markup->__toString());
  }

  /**
   * Test build function display social media service name.
   */
  public function testBuildDisplayMediaServiceName() {
    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'follow_me',
      'field_widget_title' => 'test',
      'field_display_social' => [
        TRUE,
      ],
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertNotContains('no-label', $markup->__toString());
  }

  /**
   * Test build function display rss feed page link.
   */
  public function testBuildDisplayRssFeedPageLink() {
    // Create paragraph.
    $values = [
      'type' => 'follow_me_links',
      'field_domain' => 'facebook.com',
      'field_link_title' => 'facebook',
      'field_weight' => 1,
    ];
    $paragraph = $this->createParagraph($values);
    $paragraph_items[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'follow_me',
      'field_widget_title' => 'test',
      'field_add_link_to_rss_feed_page' => [
        TRUE,
      ],
      'field_links' => $paragraph_items,
    ]);
    $view_builder = $this->entityTypeManager
      ->getViewBuilder('block_content');
    $render = $view_builder->view($block_content);
    $renderer = $this->container->get('renderer');

    /** @var \Drupal\Core\Render\Markup $markup_array */
    $markup = $renderer->renderRoot($render);
    $this->assertContains('rrssb-buttons', $markup->__toString());
    $this->assertContains('rss-follow-icon', $markup->__toString());
    $this->assertContains('rrssb-facebook', $markup->__toString());
    $this->assertContains('rrssb-icon', $markup->__toString());
    $this->assertContains('rrssb-text', $markup->__toString());
    $this->assertContains('facebook', $markup->__toString());
  }

}
