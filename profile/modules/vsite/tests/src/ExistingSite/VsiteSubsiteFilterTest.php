<?php

namespace Drupal\Tests\vsite\ExistingSite;

/**
 * Test the Subsite Vsite Views Filter.
 *
 * @group vsite
 * @group functional
 * @coversDefaultClass \Drupal\vsite\Plugin\views\filter\VsiteSubsiteFilter
 */
class VsiteSubsiteFilterTest extends VsiteExistingSiteTestBase {

  /**
   * Group dummy content is being assigned (or not) to.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $groupOther;

  /**
   * Group hidden.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $groupHidden;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupOther = $this->createGroup([
      'type' => 'personal',
      'label' => 'OtherSite',
      'field_privacy_level' => 'public',
    ]);
    $this->groupHidden = $this->createGroup([
      'type' => 'subsite_test',
      'label' => 'HiddenSite',
      'field_parent_site' => $this->group->id(),
      'field_privacy_level' => 'private',
    ]);

    $this->createGroup([
      'type' => 'subsite_test',
      'label' => 'SubSite01',
      'field_parent_site' => $this->group->id(),
      'field_privacy_level' => 'public',
    ]);
    $this->createGroup([
      'type' => 'subsite_test',
      'label' => 'SubSite02',
      'field_parent_site' => $this->group->id(),
      'field_privacy_level' => 'public',
    ]);
  }

  /**
   * Check that only the subsite group shows up in a subsites list.
   */
  public function testInsideOfVsite() {
    $this->visitViaVsite('subsites', $this->group);

    $this->assertSession()->pageTextContains('SubSite01');
    $this->assertSession()->pageTextContains('SubSite02');
    $this->assertSession()->pageTextNotContains('HiddenSite');
  }

  /**
   * Check that only the subsite group not shows up in other subsites list.
   */
  public function testOtherVsite() {
    $this->visitViaVsite('subsites', $this->groupOther);

    $this->assertSession()->pageTextNotContains('SubSite01');
    $this->assertSession()->pageTextNotContains('SubSite02');
    $this->assertSession()->pageTextNotContains('HiddenSite');
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();
    unset($this->groupHidden);
    unset($this->groupOther);
  }

}
