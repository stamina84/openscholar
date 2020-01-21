<?php

namespace Drupal\Tests\cp_taxonomy\ExistingSiteJavascript;

use Drupal\media\Entity\Media;

/**
 * Tests taxonomy terms apply to media.
 *
 * @group functional
 * @group cp
 * @covers \Drupal\cp_taxonomy\Form\AddTermsToMediaForm
 */
class ApplyTermsMediaTest extends CpTaxonomyExistingSiteJavascriptTestBase {

  protected $term;
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->drupalLogin($this->groupAdmin);
    $allowed_types = [
      'media:*',
    ];
    $this->createGroupVocabulary($this->group, 'vocab_group_1', $allowed_types);
    $this->term = $this->createGroupTerm($this->group, 'vocab_group_1', ['name' => $this->randomMachineName()]);
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Test functionality of Apply term to media.
   */
  public function testAppliedTermMedia() {
    $web_assert = $this->assertSession();
    $media = $this->createMedia([
      'uid' => $this->groupAdmin->id(),
    ]);
    $this->group->addContent($media, 'group_entity:media');
    $this->visitViaVsite('cp/content/browse/media', $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $page->findField('media_bulk_form[0]')->check();
    $this->applyAction('cp_taxonomy_add_terms_media_action');
    $this->applyVocabularyFirstTerm('vocab_group_1');
    $web_assert->pageTextContains('Taxonomy term ' . $this->term->label() . ' was applied on the content');
    $status_wrapper = $page->find('css', '.messages--status');
    $this->assertContains($media->label(), $status_wrapper->getHtml());

    $saved_media = Media::load($media->id());
    $term_value = $saved_media->get('field_taxonomy_terms')->getString();
    $this->assertEqual($this->term->id(), $term_value);
  }

  /**
   * Helper function, that will apply the action.
   */
  protected function applyAction($action_id) {
    $web_assert = $this->assertSession();
    $page = $this->getCurrentPage();
    $select = $page->findField('action');
    $select->setValue($action_id);
    $page->pressButton('Apply to selected items');
    $web_assert->statusCodeEquals(200);
  }

  /**
   * Helper function, that will select a vocab and first term in chosen.
   */
  protected function applyVocabularyFirstTerm($vocabulary) {
    $web_assert = $this->assertSession();
    $page = $this->getCurrentPage();
    $select = $page->findField('vocabulary');
    $select->setValue($vocabulary);
    $this->waitForAjaxToFinish();
    $page->find('css', '.chosen-search-input')->click();
    $result = $web_assert->waitForElementVisible('css', '.active-result.highlighted');
    $this->assertNotEmpty($result, 'Chosen popup is not visible.');
    $page->find('css', '.active-result.highlighted')->click();
    $page->find('css', '.chosen-search-input')->click();
    $page->pressButton('Apply');
    $web_assert->statusCodeEquals(200);
  }

}
