<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;

/**
 * Class DataverseSearchBoxWidget.
 *
 * @OsWidget(
 *   id = "dataverse_search_box_widget",
 *   title = @Translation("Dataverse Search Box Widget")
 * )
 */
class DataverseSearchBoxWidget extends OsWidgetsBase implements OsWidgetsInterface {
  // TODO: check with team if we should make this drupal config instead
  // of the os_widgets module instead of a class constant here?
  const DATAVERSE_SEARCH_BASE_URL = "https://dataverse.harvard.edu/dataverse.xhtml";

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $params = [];

    $field_search_placeholder_text = $block_content->get('field_search_placeholder_text')->getValue();
    if (!empty($field_search_placeholder_text)) {
      $params['dataverse_search_box_placeholder'] = $field_search_placeholder_text[0]['value'];
    }

    $field_dataverse_identifier = $block_content->get('field_dataverse_identifier')->getValue();
    if (!empty($field_dataverse_identifier)) {
      $params['dataverse_identifier'] = $field_dataverse_identifier[0]['value'];
    }

    $params['dataverse_search_baseurl'] = $this::DATAVERSE_SEARCH_BASE_URL;

    $form = \Drupal::formBuilder()->getForm('Drupal\os_widgets\Form\DataverseSearchBoxForm', $params);
    $build['dataverse_search_box_form'] = $form;
  }

}
