<?php

namespace Drupal\os_media;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides helper methods for improving the experience of media admin UI.
 */
final class MediaAdminUIHelper {

  /**
   * Node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

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
  }

  /**
   * Returns the nodes using a media entity.
   *
   * @param int $media_id
   *   ID of the media entity.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The nodes.
   */
  public function getMediaUsageInNodes($media_id): array {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $field_attached_media_query */
    $field_attached_media_query = $this->nodeStorage->getQuery();

    $field_attached_media_query->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', [
        'software_project',
        'news',
        'faq',
        'class',
        'blog',
        'page',
        'events',
      ], 'IN')
      ->condition('field_attached_media.entity:media.mid', $media_id);

    // TODO: Do the same for other node+media fields.
    $usages = $this->nodeStorage->loadMultiple($field_attached_media_query->execute());

    return $usages;
  }

  /**
   * Filters nodes which are using a media by title.
   *
   * This always performs the filtering with `LIKE` operator.
   *
   * @param string $title
   *   The title to filter with.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The filtered nodes.
   */
  public function filterNodesUsingMediaByTitle(string $title): array {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $field_attached_media_query */
    $field_attached_media_query = $this->nodeStorage->getQuery();

    $field_attached_media_query->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', [
        'software_project',
        'news',
        'faq',
        'class',
        'blog',
        'page',
        'events',
      ], 'IN')
      ->exists('field_attached_media')
      ->condition('title', "%{$title}%", 'LIKE');

    // TODO: Do the same for other node+media fields.
    $usages = $this->nodeStorage->loadMultiple($field_attached_media_query->execute());

    return $usages;
  }

}
