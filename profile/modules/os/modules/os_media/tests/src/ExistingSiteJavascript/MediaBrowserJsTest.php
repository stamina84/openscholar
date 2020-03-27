<?php

namespace Drupal\Tests\os_media\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\Tests\WebAssert;

/**
 * Tests media browser field.
 *
 * @group functional-javascript
 * @group os
 */
class MediaBrowserJsTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Media Entity to test with.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->media = $this->createMedia();
    $this->group->addContent($this->media, "group_entity:media");
    $account = $this->createUser();
    $this->addGroupAdmin($account, $this->group);
    $this->drupalLogin($account);
  }

  /**
   * Tests media browser field loads inside new paragraph.
   */
  public function testMediaBrowserFieldInsideClassParagraph(): void {
    $webAssert = $this->assertSession();
    $this->visitViaVsite('node/add/class', $this->group);
    // Wait for every js to be loaded.
    $webAssert->waitForElementVisible('css', '.media-browser-drop-box');
    $webAssert->waitForElementVisible('css', '.cke_button__bold_icon');

    // Test Negative that field does not exist already.
    $webAssert->elementNotExists('css', '.field--name-field-attached-files .media-browser-drop-box');

    // Test positive that field appears inside new ajax content.
    $add_more = $this->getSession()->getPage()->find('css', '.field-add-more-submit');
    $add_more->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $webAssert->elementExists('css', '.field--name-field-attached-files .media-browser-drop-box');
  }

  /**
   * Test Media file is actually attached to the node and paragraph entity.
   */
  public function testMediaBrowserAttachFileInsideParagraph() {
    $webAssert = $this->assertSession();
    $nodeTitle = $this->randomString();
    $this->prepareClassAddForm($webAssert, $nodeTitle, 'Test Material');

    // Click material link.
    $this->getCurrentPage()->clickLink('Test Material');
    $webAssert->statusCodeEquals(200);

    // Assert media is actually attached and saved to the material.
    $webAssert->linkExists($this->media->label());

    // Assert via Drupal Api that reference is saved in DB as well.
    $nodeArr = $this->entityTypeManager->getStorage('node')->loadByProperties(['title' => $nodeTitle]);
    /** @var \Drupal\node\Entity\Node $classNode */
    $classNode = array_values($nodeArr)[0];
    $paraId = $classNode->get('field_class_materials')->getValue()[0]['target_id'];
    /** @var \Drupal\paragraphs\Entity\Paragraph $paraEntity */
    $paraEntity = $this->entityTypeManager->getStorage('paragraph')->load($paraId);
    $mediaId = $paraEntity->get('field_attached_files')->getValue()[0]['target_id'];
    $this->assertEquals($this->media->id(), $mediaId);
  }

  /**
   * Tests multiple media browser on Class node and ops like add, remove.
   */
  public function testMultipleMediaBrowserAttachFileClass(): void {
    $webAssert = $this->assertSession();
    $this->visitViaVsite('node/add/class', $this->group);
    // Wait for every js to be loaded.
    $webAssert->waitForElementVisible('css', '.media-browser-drop-box');
    $webAssert->waitForElementVisible('css', '.cke_button__bold_icon');

    // Load the paragraph.
    $add_more = $this->getSession()->getPage()->find('css', '.field-add-more-submit');
    $add_more->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Open node's main media browser dialog and attach file.
    $node_upload = $this->getCurrentPage()->find('css', '.field--name-field-attached-media #upmedia');
    $node_upload->click();
    $this->attachMediaViaMediaBrowser();

    // Assert it does not get attached to both media browser fields.
    $webAssert->elementExists('css', '.field--name-field-attached-media .media-actions');
    $webAssert->elementNotExists('css', '.field--name-field-attached-files .media-actions');

    // Now attach file to paragraph's media browser field too.
    $paragraph_upload = $this->getCurrentPage()->find('css', '.field--name-field-attached-files #upmedia');
    $paragraph_upload->click();
    $this->attachMediaViaMediaBrowser();
    // Assert file gets attached now and does not get attached to other browser.
    $webAssert->elementExists('css', '.field--name-field-attached-files .media-actions');
    $webAssert->elementNotExists('css', 'input[name="field_attached_media[1][target_id]"]');

    // Remove file from main node field and assert it does not get removed from
    // the paragraph's field.
    $remove = $this->getCurrentPage()->find('css', '.field--name-field-attached-media .remove');
    $remove->clickLink('');
    $webAssert->elementNotExists('css', '.field--name-field-attached-media .media-actions');
    $webAssert->elementExists('css', '.field--name-field-attached-files .media-actions');

    // Save the form and assert that media entity is actually attached.
    $this->getCurrentPage()->fillField('title[0][value]', 'Node Title');
    $this->getCurrentPage()->fillField('field_class_materials[0][subform][field_title][0][value]', 'Para Title');
    $this->submitForm([], 'Save');
    $webAssert->waitForElementVisible('css', '.page-header');
    $webAssert->statusCodeEquals(200);
    $nodeArr = $this->entityTypeManager->getStorage('node')->loadByProperties(['title' => 'Node Title']);
    /** @var \Drupal\node\Entity\Node $classNode */
    $classNode = array_values($nodeArr)[0];
    $paraId = $classNode->get('field_class_materials')->getValue()[0]['target_id'];
    /** @var \Drupal\paragraphs\Entity\Paragraph $paraEntity */
    $paraEntity = $this->entityTypeManager->getStorage('paragraph')->load($paraId);
    $mediaId = $paraEntity->get('field_attached_files')->getValue()[0]['target_id'];
    $this->assertEquals($this->media->id(), $mediaId);
  }

  /**
   * Tests multiple media browser on presentation node.
   */
  public function testMultipleMediaBrowserAttachFilePresentation(): void {
    $webAssert = $this->assertSession();

    $this->visitViaVsite('node/add/presentation', $this->group);
    // Wait for every js to be loaded.
    $webAssert->waitForElementVisible('css', '.media-browser-drop-box');
    $webAssert->waitForElementVisible('css', '.cke_button__bold_icon');

    // Attach field to one of the field and assert it is not attached to other
    // one.
    $slides_field = $this->getCurrentPage()->find('css', '.field--name-field-attached-media #upmedia');
    $slides_field->click();
    $this->attachMediaViaMediaBrowser();
    $webAssert->elementNotExists('css', '.field--name-field-presentation-slides .media-actions');
    $webAssert->elementExists('css', '.field--name-field-attached-media .media-actions');
  }

  /**
   * Fill, attach media, save the class node and paragraph with some assertions.
   *
   * @param \Drupal\Tests\WebAssert $webAssert
   *   Assert Session.
   * @param string $nodeTitle
   *   Node Title.
   * @param string $paraTitle
   *   Paragraph title.
   */
  protected function prepareClassAddForm(WebAssert $webAssert, $nodeTitle, $paraTitle) {
    $this->visitViaVsite('node/add/class', $this->group);
    $webAssert->statusCodeEquals(200);
    // Wait for every js to be loaded.
    $webAssert->waitForElementVisible('css', '.media-browser-drop-box');
    $webAssert->waitForElementVisible('css', '.cke_button__bold_icon');

    // Fill the Class node add form with class material add form.
    $this->getCurrentPage()->fillField('title[0][value]', $nodeTitle);
    // Inject the class material paragraph form.
    $add_more = $this->getSession()->getPage()->find('css', '.field-add-more-submit');
    $add_more->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getCurrentPage()->fillField('field_class_materials[0][subform][field_title][0][value]', $paraTitle);
    // Open media browser dialog inside paragraph.
    $paragraph_upload = $this->getCurrentPage()->find('css', '.field--name-field-attached-files #upmedia');
    $paragraph_upload->click();
    // Attach File.
    $this->attachMediaViaMediaBrowser();
    // Assert media title is attached and visible in paragraph media browser
    // field.
    $webAssert->pageTextContains($this->media->label());
    // Save the entire node.
    $this->submitForm([], 'Save');
    $webAssert->waitForElementVisible('css', '.page-header');
    $webAssert->statusCodeEquals(200);
  }

}
