<?php

namespace Drupal\os_media\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters by entity title using a media entity.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("os_media_media_usage_filter")
 */
class MediaUsageFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    // TODO: An empty textfield appears in "used in" filter setting.
    $form['value'] = [
      '#type' => 'textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // TODO: Restrict the operator to `LIKE`.
    if (empty($this->value)) {
      return;
    }

    $value = $this->value;
    $value = reset($value);

    if (!empty($value)) {
      // TODO: Move this inside a service.
      /** @var \Drupal\node\NodeStorageInterface $node_storage */
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      /** @var \Drupal\Core\Entity\Query\QueryInterface $field_attached_media_query */
      $field_attached_media_query = $node_storage->getQuery();

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
        ->condition('title', "%{$value}%", 'LIKE');

      $field_attached_media_usages = $node_storage->loadMultiple($field_attached_media_query->execute());

      $media_entity_ids = array_map(static function (NodeInterface $node) {
        return $node->get('field_attached_media')->first()->getValue()['target_id'];
      }, $field_attached_media_usages);

      // TODO: Do the same for other node+media fields.
      // TODO: Do the same for reference+media field.
      $this->query->addWhere('AND', 'media_field_data.mid', $media_entity_ids, 'IN');
    }
  }

}
