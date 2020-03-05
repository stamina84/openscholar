<?php

namespace Drupal\Tests\vsite\ExistingSite;

/**
 * VsiteTokenTest.
 *
 * @group vsite
 * @group kernel
 */
class VsiteTokenTest extends VsiteExistingSiteTestBase {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager->activateVsite($this->group);
    $this->token = $this->container->get('token');
  }

  /**
   * Check that token returns group url alias.
   */
  public function testBaseUrlToken() {
    $token_value = $this->token->replace('[vsite:base_url]');
    $this->assertEqual($this->groupAlias, rtrim($token_value, '/'));
  }

}
