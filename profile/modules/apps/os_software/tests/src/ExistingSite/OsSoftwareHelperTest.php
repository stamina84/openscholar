<?php

namespace Drupal\Tests\os_software\ExistingSite;

use Drupal\node\Entity\Node;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class OsSoftwareHelperTest.
 *
 * @group os
 * @group kernel
 *
 * @package Drupal\Tests\os_software\ExistingSite
 */
class OsSoftwareHelperTest extends OsExistingSiteTestBase {

  /**
   * Os Software Helper.
   *
   * @var \Drupal\os_software\OsSoftwareHelperInterface
   */
  private $helper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->helper = $this->container->get('os.software.helper');
  }

  /**
   * Test release title update logic.
   */
  public function testPrepareReleaseTitle() {
    $project = $this->createNode([
      'type' => 'software_project',
    ]);
    $release = $this->createNode([
      'type' => 'software_release',
      'field_software_project' => [
        $project->id(),
      ],
      'field_software_version' => 'v1.1.2',
    ]);
    $title = $this->helper->prepareReleaseTitle($release);
    $this->assertEquals($project->label() . ' v1.1.2', $title);
  }

  /**
   * Test release title default project label.
   */
  public function testPrepareReleaseDefaultProjectTitle() {
    $release = $this->createNode([
      'type' => 'software_release',
      'field_software_version' => 'v1.1.2',
    ]);
    $title = $this->helper->prepareReleaseTitle($release);
    $this->assertEquals('Project Release v1.1.2', $title);
  }

  /**
   * Test previous release gets marked not recommended automatically.
   */
  public function testUnsetRecommendedReleases() {
    $project = $this->createNode([
      'type' => 'software_project',
    ]);
    $release1 = $this->createNode([
      'type' => 'software_release',
      'field_software_project' => [
        'target_id' => $project->id(),
      ],
      'field_is_recommended_version' => 1,
      'field_software_version' => 'v1',
    ]);

    // Test that this is a recommended release.
    $this->assertEquals(1, $release1->get('field_is_recommended_version')->getValue()[0]['value']);

    $release2 = $this->createNode([
      'type' => 'software_release',
      'field_software_project' => [
        'target_id' => $project->id(),
      ],
      'field_is_recommended_version' => 1,
      'field_software_version' => 'v2',
    ]);

    // Load the updated node.
    $release1 = Node::load($release1->id());

    // Test that previous release is unrecommended now and this one is marked
    // recommended.
    $this->assertEquals(0, $release1->get('field_is_recommended_version')->getValue()[0]['value']);
    $this->assertEquals(1, $release2->get('field_is_recommended_version')->getValue()[0]['value']);

    $this->createNode([
      'type' => 'software_release',
      'field_software_project' => [
        'target_id' => $project->id(),
      ],
      'field_is_recommended_version' => 0,
      'field_software_version' => 'v3',
    ]);

    // Load the node again.
    $release2 = Node::load($release2->id());

    // Test that old release is still recommended if new one is not marked as
    // recommended.
    $this->assertEquals(1, $release2->get('field_is_recommended_version')->getValue()[0]['value']);

  }

}
