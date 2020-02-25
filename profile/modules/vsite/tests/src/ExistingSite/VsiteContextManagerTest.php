<?php

namespace Drupal\Tests\vsite\ExistingSite;

use Drupal\group\Entity\Group;
use Drupal\vsite\Plugin\VsiteContextManager;

/**
 * VsiteContextManagerTest.
 *
 * @group vsite
 * @group kernel
 */
class VsiteContextManagerTest extends VsiteExistingSiteTestBase {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
  }

  /**
   * Tests activateVsite.
   *
   * @covers \Drupal\vsite\Plugin\VsiteContextManager::activateVsite
   */
  public function testActivateVsite() {
    // Negative test.
    $unsaved_group = Group::create([
      'type' => 'personal',
    ]);
    $this->vsiteContextManager->activateVsite($unsaved_group);

    $this->assertNull($this->vsiteContextManager->getActiveVsite());

    // Positive test.
    $saved_group = $this->createGroup([
      'type' => 'personal',
    ]);
    $this->vsiteContextManager->activateVsite($saved_group);

    $this->assertEquals($saved_group->id(), $this->vsiteContextManager->getActiveVsite()->id());
  }

  /**
   * Tests getActivePurl.
   *
   * @covers \Drupal\vsite\Plugin\VsiteContextManager::getActivePurl
   */
  public function testGetActivePurl() {
    // Negative tests.
    $this->createGroup([
      'path' => [
        'alias' => '/no-active-test-alias',
      ],
    ]);

    $this->assertEquals('', $this->vsiteContextManager->getActivePurl());

    $group = $this->createGroup([
      'path' => NULL,
    ]);
    $this->vsiteContextManager->activateVsite($group);

    $this->assertEquals('', $this->vsiteContextManager->getActivePurl());

    // Positive test.
    $group = $this->createGroup([
      'path' => [
        'alias' => '/test-alias',
      ],
    ]);
    $this->vsiteContextManager->activateVsite($group);

    $this->assertEquals('test-alias', $this->vsiteContextManager->getActivePurl());
  }

  /**
   * @covers \Drupal\vsite\Plugin\VsiteContextManager::vsiteFlushCssJs
   */
  public function testVsiteFlushCssJs(): void {
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->container->get('state');

    VsiteContextManager::vsiteFlushCssJs($this->group);

    $vsite_css_js_query_string = $state->get("vsite.css_js_query_string.{$this->group->id()}");

    $this->assertNotNull($vsite_css_js_query_string);
    $this->assertInternalType('string', $vsite_css_js_query_string);
  }

}
