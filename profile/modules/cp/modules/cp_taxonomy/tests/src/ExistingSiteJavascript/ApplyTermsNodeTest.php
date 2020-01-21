<?php

namespace Drupal\Tests\cp_taxonomy\ExistingSiteJavascript;

use Drupal\node\Entity\Node;

/**
 * Tests taxonomy terms apply to nodes.
 *
 * @group functional
 * @group cp
 * @covers \Drupal\cp_taxonomy\Form\AddTermsToNodeForm
 */
class ApplyTermsNodeTest extends CpTaxonomyExistingSiteJavascriptTestBase {

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
      'node:faq',
    ];
    $this->createGroupVocabulary($this->group, 'vocab_group_1', $allowed_types);
    $this->term = $this->createGroupTerm($this->group, 'vocab_group_1', ['name' => $this->randomMachineName()]);
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Test functionality of Apply term even if vocabulary not related to blog.
   */
  public function testAppliedAndSkippedNodes() {
    $web_assert = $this->assertSession();
    $blog = $this->createNode([
      'type' => 'blog',
      'uid' => $this->groupAdmin->id(),
    ]);
    $this->group->addContent($blog, 'group_node:blog');
    $faq = $this->createNode([
      'type' => 'faq',
      'uid' => $this->groupAdmin->id(),
    ]);
    $this->group->addContent($faq, 'group_node:faq');
    $this->visitViaVsite('cp/content/browse/node', $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $page->findField('node_bulk_form[0]')->check();
    $page->findField('node_bulk_form[1]')->check();
    $this->applyAction('cp_taxonomy_add_terms_node_action');
    $this->applyVocabularyFirstTerm('vocab_group_1');
    $web_assert->pageTextContains('Taxonomy term ' . $this->term->label() . ' could not be applied on the content');
    $web_assert->pageTextContains('Taxonomy term ' . $this->term->label() . ' was applied on the content');
    $warning_wrapper = $page->find('css', '.messages--warning');
    $this->assertContains($blog->label(), $warning_wrapper->getHtml());
    $this->assertNotContains($faq->label(), $warning_wrapper->getHtml());
    $status_wrapper = $page->find('css', '.messages--status');
    $this->assertContains($faq->label(), $status_wrapper->getHtml());
    $this->assertNotContains($blog->label(), $status_wrapper->getHtml());

    $saved_faq = Node::load($faq->id());
    $term_value = $saved_faq->get('field_taxonomy_terms')->getString();
    $this->assertEqual($this->term->id(), $term_value);
    $saved_blog = Node::load($blog->id());
    $term_value = $saved_blog->get('field_taxonomy_terms')->getString();
    $this->assertNotContains($this->term->id(), $term_value);
  }

  /**
   * Test add invalid vocabulary.
   */
  public function testApplyInvalidVocabulary() {
    $web_assert = $this->assertSession();
    $blog = $this->createNode([
      'type' => 'blog',
      'uid' => $this->groupAdmin->id(),
    ]);
    $this->group->addContent($blog, 'group_node:blog');
    $allowed_types = [
      'media:*',
    ];
    $media_vocab = $this->randomMachineName();
    $this->createGroupVocabulary($this->group, $media_vocab, $allowed_types);
    $this->createGroupTerm($this->group, $media_vocab, ['name' => $this->randomMachineName()]);
    $this->visitViaVsite('cp/content/browse/node', $this->group);
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $page->findField('node_bulk_form[0]')->check();
    $this->applyAction('cp_taxonomy_add_terms_node_action');
    $this->applyVocabularyFirstTerm($media_vocab);
    $web_assert->pageTextContains('Selected vocabulary is not handle node entity type.');
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
