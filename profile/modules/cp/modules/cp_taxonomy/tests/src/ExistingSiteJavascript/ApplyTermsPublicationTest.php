<?php

namespace Drupal\Tests\cp_taxonomy\ExistingSiteJavascript;

use Drupal\bibcite_entity\Entity\Reference;

/**
 * Tests taxonomy terms apply to publication.
 *
 * @group functional-javascript
 * @group cp
 * @covers \Drupal\cp_taxonomy\Form\AddTermsToPublicationForm
 */
class ApplyTermsPublicationTest extends CpTaxonomyExistingSiteJavascriptTestBase {

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
      'bibcite_reference:*',
    ];
    $this->createGroupVocabulary($this->group, 'vocab_group_1', $allowed_types);
    $this->term = $this->createGroupTerm($this->group, 'vocab_group_1', ['name' => $this->randomMachineName()]);
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Test functionality of Apply term to publication.
   */
  public function testAppliedTermMedia() {
    $web_assert = $this->assertSession();
    $publication = $this->createReference([
      'uid' => $this->groupAdmin->id(),
    ]);
    $this->group->addContent($publication, 'group_entity:bibcite_reference');
    $this->visitViaVsite('cp/content/browse/publications', $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $page->findField('bibcite_reference_bulk_form[0]')->check();
    $this->applyAction('cp_taxonomy_add_terms_bibcite_reference_action');
    $this->applyVocabularyFirstTerm('vocab_group_1');
    $web_assert->pageTextContains('Taxonomy term ' . $this->term->label() . ' was applied on the content');
    $status_wrapper = $page->find('css', '.messages--status');
    $this->assertContains($publication->label(), $status_wrapper->getHtml());

    $saved_publication = Reference::load($publication->id());
    $term_value = $saved_publication->get('field_taxonomy_terms')->getString();
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
