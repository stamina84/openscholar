<?php

namespace Drupal\Tests\os_rest\ExistingSiteJavascript;

use Drupal\cp_taxonomy\CpTaxonomyHelper;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;

/**
 * Tests taxonomy resource response.
 *
 * @group functional
 * @group os
 */
class OsRestTaxonomyResourceTest extends OsExistingSiteTestBase {

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
   * Tests taxonomy resource data.
   */
  public function testTaxonomyMultipleData() {
    $vid = strtolower('a' . $this->randomMachineName());
    $this->createGroupVocabulary($this->group, $vid, ['media:*'], CpTaxonomyHelper::WIDGET_TYPE_OPTIONS_SELECT);
    $term1 = $this->createGroupTerm($this->group, $vid, ['name' => 'a' . $this->randomMachineName()]);
    $term2 = $this->createGroupTerm($this->group, $vid, ['name' => 'b' . $this->randomMachineName()]);

    $this->visitViaVsite('api/taxonomy?_format=json&vid=' . $vid . '&vsite=' . $this->group->id(), $this->group);
    $json_array = json_decode($this->getCurrentPageContent());
    $this->assertEquals(2, $json_array->count);
    $rows = $json_array->rows;
    $this->assertEquals($term1->id(), $rows[0]->id);
    $this->assertEquals($term1->label(), $rows[0]->label);
    $this->assertEquals($term2->id(), $rows[1]->id);
    $this->assertEquals($term2->label(), $rows[1]->label);
    $this->assertEquals($vid, $rows[0]->vocab);
    $this->assertEquals($vid, $rows[0]->vid);
  }

}
