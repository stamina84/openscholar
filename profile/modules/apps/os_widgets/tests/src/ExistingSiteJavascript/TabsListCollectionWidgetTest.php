<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests ListCollection field widget.
 *
 * Testing ListCollection field_widget as an authenticated user, but with
 * "Administer blocks" permission. We are creating a custom_text_html block to
 * make this block available as an option in the field widget.
 *
 * @group functional-javascript
 * @group widgets
 * @covers \Drupal\os_media\Plugin\Field\FieldWidget\ListCollectionWidget
 */
class TabsListCollectionWidgetTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Group administrator.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->createUser(['administer blocks'], 'testUser');
  }

  /**
   * Tests os_widgets tabs field add widget to collection selection.
   */
  public function testListCollectionWidget() {
    $this->drupalLogin($this->user);
    // Activating test Vsite.
    $vsiteContextManager = $this->container->get('vsite.context_manager');
    $vsiteContextManager->activateVsite($this->group);
    $web_assert = $this->assertSession();

    // Creating a test custom_text_html block.
    $block_content = $this->createBlockContent([
      'type' => 'custom_text_html',
      'info' => [
        'value' => 'Test tab 1',
      ],
      'body' => [
        'Lorem Ipsum tab content 1',
      ],
      'field_widget_title' => ['Test tab 1'],
    ]);

    $this->group->addContent($block_content, 'group_entity:block_content');
    $tag = $block_content->getVsiteCacheTag();
    $this->assertSame('block_content_entity_vsite:' . $this->group->id(), $tag, 'This is valid');
    $this->visitViaVsite('block/add/tabs', $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $this->assertSession()->pageTextContains('Add Widget to Collection');
    $select_field = $page->findField('field_widget_collection[add_new_element][select_input]');
    $select_field->selectOption('Test tab 1');
    $page->findButton('Add this')->click();
    // On AJAX submit, waiting and finding table column with same block info.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertTrue('<td>Test tab 1</td>');
  }

}
