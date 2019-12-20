<?php

namespace Drupal\Tests\cp_users\ExistingSite;

/**
 * Check compact mode access for authenticated users.
 *
 * @group functional
 * @group cp
 */
class CpUsersAccessCompactModeTest extends CpUsersExistingSiteTestBase {

  /**
   * Site member.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $member;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    $this->member = $this->createUser();
  }

  /**
   * Tests compact mode url access.
   */
  public function testCompactModeAccessPositive() {
    $this->drupalLogin($this->member);
    $this->visit('/admin/compact/on');
    $this->assertSession()->statusCodeEquals(200);

    $this->visit('/admin/compact');
    $this->assertSession()->statusCodeEquals(200);
  }

}
