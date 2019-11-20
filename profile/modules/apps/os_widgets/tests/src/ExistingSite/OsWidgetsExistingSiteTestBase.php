<?php

namespace Drupal\Tests\os_widgets\ExistingSite;

use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\openscholar\Traits\WidgetsTestTrait;

/**
 * Base class for os_widgets tests.
 */
class OsWidgetsExistingSiteTestBase extends OsExistingSiteTestBase {

  use WidgetsTestTrait;
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

}
