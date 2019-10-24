<?php

namespace Drupal\os_media;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles lazy builder of media entities.
 */
class OsMediaLazyBuilders implements ContainerInjectionInterface {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Renders the media entity to the given width.
   *
   * @param int $id
   *   The id of the media entity to render.
   * @param string $width
   *   The width of the final media entity render,
   *   or 'default' if no width is set.
   * @param string $height
   *   The width of the final media entity render.
   *
   * @return array
   *   Actual media entity render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function renderMedia($id, $width, $height) {
    if ($entity = $this->entityTypeManager->getStorage('media')->load($id)) {
      if ($entity->bundle() === 'oembed') {
        // To control width and height of the rendered video.
        $entity->field_media_oembed_content->width = $width;
        $entity->field_media_oembed_content->height = $height;
        $field = $entity->field_media_oembed_content->view('wysiwyg');
      }
      elseif ($entity->bundle() === 'image') {
        $field = $entity->field_media_image->get(0)->view('wysiwyg');
        $field['#item']->width = $width;
        $field['#item']->height = $height;
      }
      elseif ($entity->bundle() === 'html') {
        $entity->field_media_html->width = $width;
        $entity->field_media_html->height = $height;
        $field = $entity->field_media_html->view('wysiwyg');
      }
      else {
        $field = $entity->toLink()->toString();
        $field = [
          '#markup' => $field,
        ];
      }
      return $field;
    }

    // No media entity of $id found.
    // Fallback to empty string.
    // TODO: Display warning for privledged users?
    return [
      '#type' => 'markup',
      '#markup' => "",
    ];
  }

}
