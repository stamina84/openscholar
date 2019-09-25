<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Core\Url;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;

/**
 * Class EmbedMediaWidget.
 *
 * @OsWidget(
 *   id = "slideshow_widget",
 *   title = @Translation("Slideshow")
 * )
 */
class SlideshowWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $options = [
      'query' => [
        'destination' => \Drupal::service('path.current')->get(),
      ],
    ];
    $build['add_slideshow_button'] = [
      '#type' => 'link',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#title' => $this->t('Add slideshow'),
      '#url' => Url::fromRoute('os_widgets.add_slideshow', [
        'block_id' => $block_content->id(),
      ], $options),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'button--small',
        ],
        'data-dialog-type' => 'modal',
      ],
    ];
    $build['#attached'] = [
      'library' => [
        'core/drupal.dialog.ajax',
      ],
    ];
  }

}
