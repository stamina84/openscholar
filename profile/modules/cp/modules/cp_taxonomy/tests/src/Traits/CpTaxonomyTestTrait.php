<?php

namespace Drupal\Tests\cp_taxonomy\Traits;

/**
 * CpTaxonomy test helpers.
 */
trait CpTaxonomyTestTrait {

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

  /**
   * Helper function, that will select a term and remove from selected media.
   *
   * @param string $action_name
   *   Action machine name.
   */
  protected function removeTermWithAction($action_name = '') {
    $web_assert = $this->assertSession();
    $page = $this->getCurrentPage();
    $select = $page->findField('action');
    $select->setValue($action_name);
    $page->pressButton('Apply to selected items');
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $select = $page->findField('vocabulary');
    $select->setValue('vocab_group_1');
    $this->waitForAjaxToFinish();
    $page->find('css', '.chosen-search-input')->click();

    $result = $web_assert->waitForElementVisible('css', '.active-result.highlighted');
    $this->assertNotEmpty($result, 'Chosen popup is not visible.');
    $web_assert->pageTextContains($this->term->label());
    $page->find('css', '.active-result.highlighted')->click();
    $page->find('css', '.chosen-search-input')->click();
    $page->pressButton('Remove');
    $web_assert->statusCodeEquals(200);
  }

}
