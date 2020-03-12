<?php

namespace Drupal\Tests\vsite\ExistingSite;

/**
 * Tests VsitePathAliasHelper.
 *
 * @group vsite
 * @group kernel
 * @coversDefaultClass \Drupal\vsite\Helper\VsitePathAliasHelper
 */
class VsitePathAliasHelperTest extends VsiteExistingSiteTestBase {

  /**
   * Vsite path alias helper.
   *
   * @var \Drupal\vsite\Helper\VsitePathAliasHelper
   */
  protected $vsitePathAliasHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsitePathAliasHelper = $this->container->get('vsite.path_alias_helper');
  }

  /**
   * Tests path alias is group source.
   */
  public function testPathAliasIsGroupSource() {
    $path_alias = $this->createPathAlias([
      'path' => '/group/9999',
    ]);
    $original_alias = $path_alias->getAlias();
    $this->vsitePathAliasHelper->save($path_alias);
    $this->assertEqual($original_alias, $path_alias->getAlias());
  }

  /**
   * Tests path alias is node source.
   */
  public function testPathAliasIsNodeSource() {
    $this->vsiteContextManager->activateVsite($this->group);
    $original_alias = '/' . $this->randomMachineName();
    $path_alias = $this->createPathAlias([
      'path' => '/node/9999',
      'alias' => $original_alias,
    ]);
    $this->assertEqual('/[vsite:' . $this->group->id() . ']' . $original_alias, $path_alias->getAlias());
  }

  /**
   * Tests path alias with group path alias.
   */
  public function testPathAliasWithGroupPathAlias() {
    $this->vsiteContextManager->activateVsite($this->group);
    $original_alias = '/' . $this->randomMachineName();
    $path_alias = $this->createPathAlias([
      'path' => '/node/9999',
      'alias' => $this->groupAlias . $original_alias,
    ]);
    $this->assertEqual('/[vsite:' . $this->group->id() . ']' . $original_alias, $path_alias->getAlias());
  }

}
