<?php

namespace Drupal\Tests\cp_taxonomy\ExistingSiteJavascript;

/**
 * Tests CpCommaSeparatorFormatter functionality.
 *
 * @group functional-javascript
 * @group cp
 */
class CommaSeparatorFieldFormatterTest extends CpTaxonomyExistingSiteJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_member = $this->createUser();
    $this->group->addMember($group_member);
    $this->drupalLogin($group_member);
    $this->createGroupVocabulary($this->group, 'vocab_group', ['node:blog']);
  }

  /**
   * Test if less than 94 characters.
   */
  public function testTaxonomyTermsCharsLessThanConfigValue() {
    $term1 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term2 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term3 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term4 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term5 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(4)]);

    $node = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term1->id(),
        $term2->id(),
        $term3->id(),
        $term4->id(),
        $term5->id(),
      ],
      'status' => 1,
    ]);
    $this->group->addContent($node, 'group_node:blog');

    $this->visitViaVsite("node/" . $node->id(), $this->group);
    $web_assert = $this->assertSession();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('See also:');
    $web_assert->pageTextContains($term1->label());
    $web_assert->pageTextContains($term2->label());
    $web_assert->pageTextContains($term3->label());
    $web_assert->pageTextContains($term4->label());
    $web_assert->pageTextContains($term5->label());
    $web_assert->pageTextNotContains('More');
  }

  /**
   * Test if more than 94 characters.
   */
  public function testTaxonomyTermsCharsMoreThanConfigValue() {
    $term1 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term2 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term3 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term4 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(20)]);
    $term5 = $this->createGroupTerm($this->group, 'vocab_group', ['name' => $this->randomMachineName(5)]);

    $node = $this->createNode([
      'type' => 'blog',
      'field_taxonomy_terms' => [
        $term1->id(),
        $term2->id(),
        $term3->id(),
        $term4->id(),
        $term5->id(),
      ],
      'status' => 1,
    ]);
    $this->group->addContent($node, 'group_node:blog');

    $this->visitViaVsite("node/" . $node->id(), $this->group);
    $web_assert = $this->assertSession();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('See also:');
    $web_assert->pageTextContains($term1->label());
    $web_assert->pageTextContains($term2->label());
    $web_assert->pageTextContains($term3->label());
    $web_assert->pageTextContains($term4->label());
    $web_assert->pageTextNotContains($term5->label());
    $web_assert->pageTextContains('More');
    $page = $this->getCurrentPage();
    $page->find('css', '.togglemore')->press();
    $web_assert->pageTextContains($term5->label());
    $web_assert->pageTextContains('Less');
    $web_assert->pageTextNotContains('More');
  }

}
