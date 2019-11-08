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

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $form = \Drupal::formBuilder()->getForm('Drupal\os_widgets\Form\DataverseSearchBoxForm');
    $build['dataverse_search_box_form'] = $form;
    // TODO: Use $block_content to.
  }

}
