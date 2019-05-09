<?php

namespace Drupal\Tests\os_google_analytics\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Class GaFormTest.
 *
 * @group functional
 * @group analytics
 *
 * @package Drupal\Tests\os_publications\ExistingSite
 */
class GaFormTest extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->createUser([], '', TRUE);
    $this->simpleUser = $this->createUser();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
  }

  /**
   * Test Setting form route.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGaSettingsPath() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('cp/settings/analytics');
    drupal_flush_all_caches();
    $this->drupalGet('cp/settings/analytics');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Settings form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGaSettingsForm() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('cp/settings/analytics');
    drupal_flush_all_caches();
    $this->drupalGet('cp/settings/analytics');
    // Dummy web property.
    $edit = [
      'edit-web-property-id' => 'UA-111111111-1',
    ];
    $this->submitForm($edit, 'edit-submit');
    $this->assertSession()->fieldValueEquals('edit-web-property-id', 'UA-1234567-A1');
  }

  /**
   * Test Settings form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testVsiteCodesShowOnPage() {

    $vsite = $this->createGroup([
      'type' => 'personal',
      'path' => [
        'alias' => '/test-alias',
      ],
    ]);
    $this->vsiteContextManager->activateVsite($vsite);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test-alias/cp/settings/analytics');

    // Dummy web property.
    $edit = [
      'edit-web-property-id' => 'UA-111111111-1',
    ];
    $this->submitForm($edit, 'edit-submit');
    $this->drupalGet('test-alias');
    $this->assertSession()->responseContains('ga("test-alias.send", "pageview")');
    $this->assertSession()->responseContains('UA-111111111-1');
  }

  /**
   * Create a vsite.
   *
   * @param array $values
   *   The values for the new vsite.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The created group entity.
   */
  protected function createGroup(array $values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'type' => 'personal',
      'label' => $this->randomMachineName(),
    ]);
    $group->enforceIsNew();
    $group->save();

    $this->markEntityForCleanup($group);

    return $group;
  }

}
