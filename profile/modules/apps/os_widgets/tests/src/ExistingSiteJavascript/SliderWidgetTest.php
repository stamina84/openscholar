<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests Slider widget.
 *
 * @group functional-javascript
 * @group widgets
 */
class SliderWidgetTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Block ids.
   *
   * @var array
   */

  protected $blockIds;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);

    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test 1',
      ],
      'body' => [
        'Lorem Ipsum content 1',
      ],
      'field_widget_title' => ['Test 1'],
    ]);
    $block2 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test 2',
      ],
      'body' => [
        'Lorem Ipsum content 2',
      ],
      'field_widget_title' => ['Test 2'],
    ]);

    $this->group->addContent($block1, 'group_entity:block_content');
    $this->group->addContent($block2, 'group_entity:block_content');

    $this->blockIds[]['target_id'] = $block1->id();
    $this->blockIds[]['target_id'] = $block2->id();
  }

  /**
   * Tests os_widgets slider overlay new form.
   */
  public function testSliderOverlayNewForm() {
    $web_assert = $this->assertSession();
    $this->visitViaVsite("?block-place=1", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $page->pressButton('Create New Widget');
    $page->find('xpath', '//li[contains(@class, "slider")]/a')->press();
    $web_assert->waitForText('Add new "Slider" Widget');
    // Asserting Slider Height default value is 400.
    $web_assert->fieldValueEquals('field_slider_height[0][value]', 400);
  }

  /**
   * Testing slider assertions.
   */
  public function assertSliderWidget() {
    $web_assert = $this->assertSession();

    $slider_block = $this->createBlockContent([
      'type' => 'slider',
      'info' => [
        'value' => 'Slider test',
      ],
      'field_widget_title' => 'testing slider',
      'field_widget_collection' => $this->blockIds,
      'field_display_arrows' => 1,
    ]);
    $this->group->addContent($slider_block, 'group_entity:block_content');
    $this->placeBlockContentToRegion($slider_block, 'content');

    $slider_block2 = $this->createBlockContent([
      'type' => 'slider',
      'info' => [
        'value' => 'Slider test 2',
      ],
      'field_widget_title' => 'Slider test 2',
      'field_widget_collection' => $this->blockIds,
    ]);
    $this->group->addContent($slider_block2, 'group_entity:block_content');
    $this->placeBlockContentToRegion($slider_block2, 'content');

    $this->visitViaVsite("", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('Lorem Ipsum content 1');
    $web_assert->pageTextContains('Lorem Ipsum content 2');
    // Asserting markup contain arrows as field_display_arrows is selected.
    $web_assert->pageTextContains('testing slider');
    $web_assert->elementExists('css', '#dots-' . $slider_block->id() . ' .slick-prev');
    $web_assert->elementExists('css', '#dots-' . $slider_block->id() . ' .slick-next');
    // Asserting dots.
    $web_assert->buttonExists('slick-slide-control00');

    // Slider assertions without arrows.
    $web_assert->pageTextContains('Slider test 2');
    $web_assert->elementNotExists('css', '#dots-' . $slider_block2->id() . ' .slick-prev');
    $web_assert->elementNotExists('css', '#dots-' . $slider_block2->id() . ' .slick-next');
  }

}
