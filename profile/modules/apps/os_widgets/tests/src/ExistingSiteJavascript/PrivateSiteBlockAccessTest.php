<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Class PrivateSiteBlockAccessTest.
 *
 * @group functional-javascript
 * @group widgets
 */
class PrivateSiteBlockAccessTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests widget/block access of private vsite.
   */
  public function testPrivateBlockAccess(): void {
    $web_assert = $this->assertSession();
    $groupMember = $this->createUser();

    // Create private vsite.
    $group2 = $this->createGroup([
      'field_privacy_level' => [
        'value' => 'private',
      ],
    ]);

    // Add group member.
    $group2->addMember($groupMember);

    // Create widget.
    $widget1 = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => 'Oumuamua Widget',
      'body' => 'This is custom test Oumuamua content.',
      'field_widget_title' => 'Oumuamua Widget',
    ]);

    // Add new block content to vsite.
    $group2->addContent($widget1, 'group_entity:block_content');

    // Place block to a region to assert content.
    $this->placeBlockContentToRegion($widget1, 'content', 'all_pages', 1);

    $this->visitViaVsite('', $group2);
    $web_assert->pageTextContains('Access Denied');
    $web_assert->pageTextNotContains('This is custom test Oumuamua content.');

    $this->drupalLogin($groupMember);
    $this->visitViaVsite('', $group2);
    $web_assert->pageTextContains('This is custom test Oumuamua content.');
    $web_assert->pageTextNotContains('Access Denied');
  }

}
