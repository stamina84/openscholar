<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests os_widgets module.
 *
 * @group functional-javascript
 * @group widgets
 */
class SlideshowBlockTest extends OsExistingSiteJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Tests os_widgets slideshow overlay new form.
   */
  public function testSlideshowOverlayNewForm() {
    $web_assert = $this->assertSession();

    $this->visitViaVsite("?block-place=1", $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $page->pressButton('Create New Widget');
    $page->find('xpath', '//li[contains(@class, "slideshow")]/a')->press();
    $web_assert->waitForText('Add new "Slideshow" Widget');

    // Check default form selection.
    $default_value = $page->findField('field_slideshow_layout')->getValue();
    $this->assertSame('3_1_overlay', $default_value);

    // We should see all 4 options on widget creation.
    $web_assert->pageTextContains('Wide Overlay');
    $web_assert->pageTextContains('Standard Overlay');
    $web_assert->pageTextContains('Standard Below');
    $web_assert->pageTextContains('Standard Side');
  }

  /**
   * Tests os_widgets slideshow overlay on wide edit form.
   */
  public function testSlideshowOverlayWideEditForm() {
    $web_assert = $this->assertSession();

    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
      'field_slideshow_layout' => '3_1_overlay',
    ]);

    $this->visit("/block/" . $block_content->id());
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    // Check form selection.
    $default_value = $page->findField('field_slideshow_layout')->getValue();
    $this->assertSame('3_1_overlay', $default_value);

    // We should see only one option on widget edit.
    $web_assert->pageTextContains('Wide Overlay');
    $web_assert->pageTextNotContains('Standard Overlay');
    $web_assert->pageTextNotContains('Standard Below');
    $web_assert->pageTextNotContains('Standard Side');
  }

  /**
   * Tests os_widgets slideshow overlay on standard edit form.
   */
  public function testSlideshowOverlayStandardEditForm() {
    $web_assert = $this->assertSession();

    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
      'field_slideshow_layout' => '16_9_overlay',
    ]);

    $this->visit("/block/" . $block_content->id());
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    // Check form selection.
    $default_value = $page->findField('field_slideshow_layout')->getValue();
    $this->assertSame('16_9_overlay', $default_value);

    // We should see three options on widget edit.
    $web_assert->pageTextNotContains('Wide Overlay');
    $web_assert->pageTextContains('Standard Overlay');
    $web_assert->pageTextContains('Standard Below');
    $web_assert->pageTextContains('Standard Side');
  }

  /**
   * Test add slideshow link modal form on page.
   */
  public function testAddSlideshowLinkVisibleAndShowModalForm() {
    $web_assert = $this->assertSession();
    $image = $this->createMedia([
      'bundle' => 'image',
    ], 'image');
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);

    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->group->addContent($image, 'group_entity:media');
    $this->placeBlockContentToRegion($block_content, 'sidebar_second');

    $this->visitViaVsite("", $this->group);
    $page = $this->getCurrentPage();
    $page->findLink('Add slideshow')->press();
    $web_assert->waitForElement('css', '.modal-dialog');
    $web_assert->pageTextContains('Add new slideshow');
    $page->fillField('field_slide_image[0][target_id]', $image->label());
    $result = $web_assert->waitForElementVisible('css', '.ui-autocomplete li');
    $this->assertNotNull($result);
    // Click the autocomplete option.
    $result->click();
    // Verify that correct the input is selected.
    $web_assert->pageTextContains($image->label());
    $submit_button = $page->findButton('Save');
    $submit_button->press();
    $new_slide = $web_assert->waitForElement('css', '.slide--0');
    $this->assertNotNull($new_slide, 'New slide is not visible.');
  }

}
