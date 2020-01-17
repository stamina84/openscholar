<?php

namespace Drupal\os_media\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler for file name used in a media entity.
 *
 * @ViewsField("os_media_file_name")
 */
class MediaFileName extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
    $relationships = $this->query->relationships;

    // Ensuring whether the tables exists before adding the custom field.
    // In case, there aren't enough relationships, safely handle the situation
    // by showing ambiguous output, and without breaking the system.
    if (isset($relationships['file_managed_media__field_media_image'], $relationships['file_managed_media__field_media_file'])) {
      $this->query->addField(NULL, "CASE WHEN file_managed_media__field_media_image.{$this->realField} IS NOT NULL THEN file_managed_media__field_media_image.{$this->realField} WHEN file_managed_media__field_media_file.{$this->realField} IS NOT NULL THEN file_managed_media__field_media_file.{$this->realField} END", $this->getPluginId());
    }
    else {
      $this->query->addField(NULL, 'NULL', $this->getPluginId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $this->query->addOrderBy(NULL, $this->query->fields[$this->getPluginId()]['field'], $order, $this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return $values->{$this->getPluginId()};
  }

}
