<?php

namespace Drupal\Tests\os_rest\ExistingSiteJavascript;

use Drupal\cp_taxonomy\CpTaxonomyHelper;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;

/**
 * Tests vocabulary resource response.
 *
 * @group functional
 * @group os
 */
class OsRestVocabularyResourceTest extends OsExistingSiteTestBase {

  use CpTaxonomyTestTrait;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user we're logging in as.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->createUser();
    $this->addGroupAdmin($this->user, $this->group);
    $this->drupalLogin($this->user);
    // createGroupVocabulary() function needs these.
    $this->configFactory = $this->container->get('config.factory');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
  }

  /**
   * Tests vocabulary resource with multiple vocabs and tree terms.
   */
  public function testVocabularyMultipleVocabsTreeTerms() {
    $vid1 = strtolower('a' . $this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid1, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_SELECT);
    $vid2 = strtolower('b' . $this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid2, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_SELECT);
    $term1_1 = $this->createGroupTerm($this->group, $vid1, ['name' => 'a' . $this->randomMachineName()]);
    $term1_2 = $this->createGroupTerm($this->group, $vid1, ['name' => 'b' . $this->randomMachineName()]);
    $term2_1 = $this->createGroupTerm($this->group, $vid2);
    $term2_2 = $this->createGroupTerm($this->group, $vid2, ['parent' => $term2_1->id()]);

    $this->visitViaVsite('api/vocabulary?_format=json&bundle=document&entity_type=media&vsite=' . $this->group->id(), $this->group);
    $json_array = json_decode($this->getCurrentPageContent());
    $this->assertEquals(2, $json_array->count);
    $rows = $json_array->rows;
    $this->assertEquals($vid1, $rows[0]->id);
    $this->assertEquals($vid1, $rows[0]->machine_name);
    $this->assertEquals($vid1, $rows[0]->label, $vid1);
    $this->assertEquals(CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_SELECT, $rows[0]->form);
    $this->assertEquals($term1_1->label(), $rows[0]->tree[0]->label);
    $this->assertEquals($term1_1->id(), $rows[0]->tree[0]->value);
    $this->assertEquals($term1_2->label(), $rows[0]->tree[1]->label);
    $this->assertEquals($term1_2->id(), $rows[0]->tree[1]->value);
    $this->assertEquals($vid2, $rows[1]->id);
    $this->assertEquals($term2_1->label(), $rows[1]->tree[0]->label);
    $this->assertEquals($term2_2->label(), $rows[1]->tree[0]->children[0]->label);
    $this->assertEquals($term2_2->id(), $rows[1]->tree[0]->children[0]->value);
  }

}
