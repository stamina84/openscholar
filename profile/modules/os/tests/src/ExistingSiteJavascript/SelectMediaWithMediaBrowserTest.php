<?php

namespace Drupal\Tests\os\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests media select.
 *
 * @group functional-javascript
 * @group os
 */
class SelectMediaWithMediaBrowserTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests selection exists media with media browser.
   */
  public function testExistsMediaSelect(): void {
    $media = $this->createMedia();
    $this->group->addContent($media, "group_entity:media");
    $account = $this->createUser();
    $this->addGroupAdmin($account, $this->group);

    $web_assert = $this->assertSession();
    $this->drupalLogin($account);
    $this->visitViaVsite('node/add/blog', $this->group);
    $web_assert->statusCodeEquals(200);
    // Wait for every javascript loaded.
    $web_assert->waitForElementVisible('css', '.media-browser-drop-box');
    $web_assert->waitForElementVisible('css', '.cke_button__bold_icon');

    // Open media browser dialog.
    $this->getSession()->getPage()->clickLink('Upload');
    // Attach Media.
    $this->attachMediaViaMediaBrowser();
    // Assert media title is showed.
    $web_assert->pageTextContains($media->label());
  }

}
