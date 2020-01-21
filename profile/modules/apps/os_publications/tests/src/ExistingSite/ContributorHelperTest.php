<?php

namespace Drupal\Tests\os_publications\ExistingSite;

/**
 * Class ContributorHelperTest.
 *
 * @group kernel
 * @group publications-1
 *
 * @package Drupal\Tests\os_publications\ExistingSite
 */
class ContributorHelperTest extends TestBase {

  /**
   * Contributor helper service.
   *
   * @var \Drupal\os_publications\ContributorHelper
   */
  protected $contributorHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->contributorHelper = $this->container->get('os_publications.contributor_helper');
  }

  /**
   * Tests testContributorLoadByProps method.
   *
   * @covers \Drupal\os_publications\ContributorHelper::getByProps
   */
  public function testContributorLoadByProps(): void {
    $first_name = $this->randomString();
    $middle_name = $this->randomString();
    $last_name = $this->randomString();

    $contributor = $this->createContributor([
      'first_name' => $first_name,
      'middle_name' => $middle_name,
      'last_name' => $last_name,
    ]);

    $contributors = $this->contributorHelper->getByProps($contributor);

    $this->assertTrue(!empty($contributors));

    $contributor = $this->createContributor([
      'first_name' => $first_name,
      'middle_name' => $middle_name,
      'last_name' => $last_name,
    ]);

    $contributors = $this->contributorHelper->getByProps($contributor);
    $this->assertFalse(is_array($contributors), 'Duplicate contributors found');
  }

}
