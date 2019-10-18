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
    $slideshow_layout = $block_content->get('field_slideshow_layout')->getValue();
    if ($slideshow_layout[0]['value'] == '3_1_overlay') {
      $build['field_slideshow']['#build']['settings']['view_mode'] = 'slideshow_wide';
      if (!empty($build['field_slideshow']['#build']['items'])) {
        foreach ($build['field_slideshow']['#build']['items'] as &$item) {
          $item['#view_mode'] = 'slideshow_wide';
        }
      }
    }
    $build['add_slideshow_button'] = [
      '#type' => 'link',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#title' => $this->t('Add slideshow'),
      '#url' => Url::fromRoute('os_widgets.add_slideshow', [
        'block_content' => $block_content->id(),
      ]),
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
