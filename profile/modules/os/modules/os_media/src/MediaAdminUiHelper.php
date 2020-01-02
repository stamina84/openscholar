<?php

namespace Drupal\os_media;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides helper methods for improving the experience of media admin UI.
 */
final class MediaAdminUiHelper {

  /**
   * Node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Publication storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $publicationStorage;

  /**
   * Creates a new MediaAdminUIHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->publicationStorage = $entity_type_manager->getStorage('bibcite_reference');
  }

  /**
   * Returns the nodes using a media entity.
   *
   * @param int $media_id
   *   ID of the media entity.
   * @param string $title
   *   (Optional) Node title to further filter the usages.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The nodes.
   */
  public function getMediaUsageInNodes($media_id, string $title = ''): array {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->nodeStorage->getQuery();

    $condition_group = $query->orConditionGroup()
      ->condition('field_attached_media.entity:media.mid', $media_id)
      ->condition('field_presentation_slides.entity:media.mid', $media_id)
      ->condition('field_software_package.entity:media.mid', $media_id);

    $query->condition('status', NodeInterface::PUBLISHED)
      ->condition($condition_group)
      ->condition('title', "%$title%", 'LIKE');

    return $this->nodeStorage->loadMultiple($query->execute());
  }

  /**
   * Returns the publications using a media entity.
   *
   * @param int $media_id
   *   ID of the media entity.
   * @param string $title
   *   (Optional) Publication title to further filter the usages.
   *
   * @return \Drupal\bibcite_entity\Entity\ReferenceInterface[]
   *   The publications.
   */
  public function getMediaUsageInPublications($media_id, string $title = ''): array {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->publicationStorage->getQuery();

    $query->condition('status', 1)
      ->condition('field_attach_files.entity:media.mid', $media_id)
      ->condition('title', "%$title%", 'LIKE');

    return $this->publicationStorage->loadMultiple($query->execute());
  }

  /**
   * Filters nodes which are using a media by title.
   *
   * @param string $title
   *   The title to filter with.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The filtered nodes.
   */
  public function filterNodesUsingMediaByTitle(string $title): array {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->nodeStorage->getQuery();

    $condition_group = $query->orConditionGroup()
      ->exists('field_attached_media')
      ->exists('field_presentation_slides')
      ->exists('field_software_package');

    $query->condition('status', NodeInterface::PUBLISHED)
      ->condition($condition_group)
      ->condition('title', "%{$title}%", 'LIKE');

    return $this->nodeStorage->loadMultiple($query->execute());
  }

  /**
   * Filters publications which are using a media by title.
   *
   * @param string $title
   *   The title to filter with.
   *
   * @return \Drupal\bibcite_entity\Entity\ReferenceInterface[]
   *   The filtered publications.
   */
  public function filterPublicationsUsingMediaByTitle(string $title): array {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->publicationStorage->getQuery();

    $query->condition('status', 1)
      ->exists('field_attach_files')
      ->condition('title', "%{$title}%", 'LIKE');

    return $this->publicationStorage->loadMultiple($query->execute());
  }

}
