<?php

namespace Drupal\Tests\os_rest\ExistingSiteJavascript;

use Drupal\cp_taxonomy\CpTaxonomyHelper;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;

/**
 * Tests vocabulary updates resource response.
 *
 * @group functional
 * @group os
 */
class OsRestVocabularyUpdatesResourceTest extends OsExistingSiteTestBase {

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
    $this->container->get('database')->truncate('entities_deleted')->execute();
  }

  /**
   * Tests vocabulary updates with deleted.
   */
  public function testVocabularyUpdatesWithDeletedVocabs() {
    $vid1 = strtolower('a' . $this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid1, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_SELECT);
    $vid2 = strtolower('b' . $this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid2, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_SELECT);

    Vocabulary::load($vid2)->delete();

    $timestamp = strtotime('-2 Day');
    $this->visitViaVsite('api/vocabulary-updates/' . $timestamp . '?_format=json&vsite=' . $this->group->id(), $this->group);
    $json_array = json_decode($this->getCurrentPageContent());
    $this->assertEquals(2, $json_array->count);
    $rows = $json_array->rows;
    $this->assertEquals($vid1, $rows[0]->id);
    $this->assertEquals($vid2, $rows[1]->id);
    $this->assertEquals('deleted', $rows[1]->status);
  }

}
