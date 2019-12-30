<?php

namespace Drupal\os_media\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
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
    if (empty($this->value)) {
      return;
    }

    $value = $this->value;
    $value = reset($value);

    if (!empty($value)) {
      $this->query->addWhere('AND', 'node_field_data.title', "%{$value}%", 'LIKE');
    }
  }

}
