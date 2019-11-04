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
   * Test add slideshow link modal form on page.
   */
  public function testAddSlideshowLinkVisibleAndShowModalForm() {
    $web_assert = $this->assertSession();
    $image = $this->createMediaImage();
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
