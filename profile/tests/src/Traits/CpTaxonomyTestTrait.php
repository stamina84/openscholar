<?php

namespace Drupal\Tests\openscholar\Traits;

use Drupal\cp_taxonomy\CpTaxonomyHelper;
use Drupal\group\Entity\GroupInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides a trait for taxonomy and vocab tests.
 */
trait CpTaxonomyTestTrait {

  /**
   * Create a vocabulary to a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group entity.
   * @param string $vid
   *   Vocabulary id.
   * @param array $allowed_types
   *   Allowed types for entity bundles.
   * @param string $widget_type
   *   Widget type of field node form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createGroupVocabulary(GroupInterface $group, string $vid, array $allowed_types = [], string $widget_type = CpTaxonomyHelper::WIDGET_TYPE_AUTOCOMPLETE) {
    $this->vsiteContextManager->activateVsite($group);
    $vocab = Vocabulary::create([
      'name' => $vid,
      'vid' => $vid,
    ]);
    $vocab->enforceIsNew();
    $vocab->save();
    if (!empty($allowed_types)) {
      $config_vocab = $this->configFactory->getEditable('taxonomy.vocabulary.' . $vid);
      $config_vocab
        ->set('allowed_vocabulary_reference_types', $allowed_types)
        ->set('widget_type', $widget_type)
        ->set('is_required', FALSE)
        ->set('widget_type_autocomplete', CpTaxonomyHelper::TYPE_AUTOCOMPLETE)
        ->save(TRUE);
    }

    $this->markEntityForCleanup($vocab);
  }

  /**
   * Create a vocabulary to a group on cp taxonomy pages.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group entity.
   * @param string $vid
   *   Vocabulary id.
   * @param array $settings
   *   Taxonomy term settings.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   Created taxonomy term.
   */
  protected function createGroupTerm(GroupInterface $group, string $vid, array $settings) {
    $this->vsiteContextManager->activateVsite($group);
    $vocab = Vocabulary::load($vid);
    $term = $this->createTerm($vocab, $settings);
    $group->addContent($term, 'group_entity:taxonomy_term');
    return $term;
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
