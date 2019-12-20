<?php

namespace Drupal\Tests\cp_users\ExistingSite;

/**
 * SuuportVsite test.
 *
 * @group functional
 * @group cp
 */
class CpSupportVsiteSubscribeTest extends CpUsersExistingSiteTestBase {

  /**
   * Support User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $supportAdmin;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();
    // Create support user.
    $this->supportAdmin = $this->createUser([
      'support vsite',
    ]);
    $this->drupalLogin($this->supportAdmin);
  }

  /**
   * Tests subscribe/unsubsribe workflow.
   *
   * @covers \Drupal\cp_users\Form\CpUserSupportConfirmForm
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function test(): void {

    $web = $this->assertSession();
    $page = $this->getCurrentPage();
    $this->visitViaVsite('', $this->group);

    // Subscribe the vsite.
    $page->clickLink('Subscribe to this site');
    $web->pageTextContains('Are you sure you want to subscribe this website ' . $this->group->label()) . " ?";
    $page->pressButton('Confirm');
    $web->pageTextContains('Successfully subscribed to ' . $this->group->label());

    // Check Support User membership exists.
    $this->visitViaVsite('cp/users', $this->group);
    $support_role = $page->find('xpath', '//tr/td[contains(.,"' . $this->supportAdmin->getAccountName() . '")]/following-sibling::td[contains(.,"Support User")]');
    $this->assertNotNull($support_role, 'Support user not present in the Table.');

    // Unsubscribe the vsite.
    $page->clickLink('Unsubscribe to this site');
    $web->pageTextContains('Are you sure you want to unsubscribe this website ' . $this->group->label()) . " ?";
    $page->pressButton('Confirm');
    $web->pageTextContains('Successfully unsubscribed to ' . $this->group->label());

    // Check Support User membership does not exists.
    $this->visitViaVsite('cp/users', $this->group);
    $support_role = $page->find('xpath', '//tr/td[contains(.,"' . $this->supportAdmin->getAccountName() . '")]/following-sibling::td[contains(.,"Support User")]');
    $this->assertNull($support_role, 'Support user present in the Table.');
  }

}
