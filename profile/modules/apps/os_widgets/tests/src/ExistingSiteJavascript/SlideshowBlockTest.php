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
    $web_assert->pageTextContains('Widescreen Overlay');
    $web_assert->pageTextContains('Standard Overlay');
    $web_assert->pageTextContains('Standard Below');
    $web_assert->pageTextContains('Standard Side');
  }

  /**
   * Test anonymous should not see Add slide button.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAddSlideButtonAnonymous() {
    $web_assert = $this->assertSession();
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'content');
    $this->drupalLogout();
    $this->visitViaVsite("", $this->group);
    $web_assert->pageTextNotContains('Add slide');
  }

  /**
   * Test admin should see Add slide button and modal form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testAddSlideButtonAdmin() {
    $web_assert = $this->assertSession();
    $block_content = $this->createBlockContent([
      'type' => 'slideshow',
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'content');
    $this->visitViaVsite("", $this->group);
    $web_assert->pageTextContains('Add slide');
    $page = $this->getCurrentPage();
    $page->findLink('Add slide')->press();
    $this->waitForAjaxToFinish();
    $web_assert->waitForText('Add new slideshow');
    // Check image field is appeared.
    $web_assert->fieldExists('files[field_slide_image_0_inline_entity_form_field_media_image_0]');
  }

}
