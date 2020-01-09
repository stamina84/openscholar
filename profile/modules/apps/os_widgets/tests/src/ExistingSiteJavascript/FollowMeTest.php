<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests Follow me widget.
 *
 * @group functional-javascript
 * @group widgets
 */
class FollowMeTest extends OsExistingSiteJavascriptTestBase {

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
   * Testing follow me assertions.
   */
  public function testSliderAssertArrows() {
    $web_assert = $this->assertSession();
    $values = [
      [
        'type' => 'follow_me_links',
        'field_domain' => 'https://www.facebook.com',
        'field_link_title' => 'facebook',
        'field_weight' => 1,
      ],
      [
        'type' => 'follow_me_links',
        'field_domain' => 'https://www.twitter.com',
        'field_link_title' => 'twitter',
        'field_weight' => 2,
      ],
    ];

    foreach ($values as $value) {
      $paragraph = $this->createParagraph($value);
      $paragraph_items[] = [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    /** @var \Drupal\block_content\Entity\BlockContent $block_content */
    $block_content = $this->createBlockContent([
      'type' => 'follow_me',
      'field_widget_title' => 'Follow me test',
      'field_add_link_to_rss_feed_page' => [
        TRUE,
      ],
      'field_links' => $paragraph_items,
    ]);
    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'content');
    $this->visitViaVsite("", $this->group);

    $web_assert->statusCodeEquals(200);
    $web_assert->linkByHrefExists('https://www.facebook.com');

    $web_assert->waitForElementVisible('css', '.block--type-follow-me .trigger')->click();
    $web_assert->waitForLink('Edit')->click();
    $web_assert->waitForText('Add new "Follow Me" Widget');
    $web_assert->fieldValueEquals('field_widget_title[0][value]', 'Follow me test');
    // Removing facebook link from widget.
    $web_assert->waitForElementVisible('css', '.even .remove')->click();
    $web_assert->waitForElementVisible('css', '.modal-buttons .js-form-submit')->click();
    $web_assert->assertWaitOnAjaxRequest();
    // Asserting No facebook link on widget after removal.
    $web_assert->linkByHrefNotExists('https://www.facebook.com');
  }

}
