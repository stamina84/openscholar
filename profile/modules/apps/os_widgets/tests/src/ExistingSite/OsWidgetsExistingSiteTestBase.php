<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Base class for os_widgets tests.
 */
class OsWidgetsExistingSiteTestBase extends OsExistingSiteTestBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Handle all widgets with plugin manager.
   *
   * @var \Drupal\os_widgets\OsWidgetsManager
   *   Os Widgets Manager.
   */
  protected $osWidgets;

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->osWidgets = $this->container->get('plugin.manager.os_widgets');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
  }

  /**
   * Creates a media.
   *
   * @param array $values
   *   (Optional) Default values for the media.
   *
   * @return \Drupal\media\MediaInterface
   *   The new media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function osWidgetCreateMedia(array $values = []) : MediaInterface {
    $media = Media::create($values + [
      'bundle' => 'default',
      'name' => $this->randomMachineName(8),
      'status' => 1,
    ]);

    $media->save();

    $this->markEntityForCleanup($media);

    return $media;
  }

  /**
   * Create content for vsites.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createVsiteContent() : array {
    $ref1 = $this->createReference([
      'type' => 'artwork',
      'html_title' => 'Publication1',
    ]);
    $ref2 = $this->createReference([
      'type' => 'book',
      'html_title' => 'Publication2',
    ]);
    $node1 = $this->createNode([
      'type' => 'blog',
      'title' => 'Blog',
    ]);
    $node2 = $this->createNode([
      'type' => 'news',
      'title' => 'News',
    ]);
    $this->group->addContent($ref1, 'group_entity:bibcite_reference');
    $this->group->addContent($ref2, 'group_entity:bibcite_reference');
    $this->group->addContent($node1, 'group_node:blog');
    $this->group->addContent($node2, 'group_node:news');

    return [$ref1, $ref2, $node1, $node2];
  }

}
