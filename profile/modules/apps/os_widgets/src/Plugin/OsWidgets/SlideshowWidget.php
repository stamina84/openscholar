<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
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
    $image_style_prefix = 'os_slideshow_standard_';
    if ($slideshow_layout[0]['value'] == '3_1_overlay') {
      $image_style_prefix = 'os_slideshow_wide_';
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
    $slick_breakpoints_image_style_map = [
      300 => $image_style_prefix . 'small',
      600 => $image_style_prefix . 'medium',
      900 => $image_style_prefix . 'large',
    ];
    if (empty($build['field_slideshow']['#build']['items'])) {
      return;
    }
    foreach ($build['field_slideshow']['#build']['items'] as &$item) {
      $image_media_values = $item['#paragraph']->get('field_slide_image')->referencedEntities();
      $image_media = reset($image_media_values);
      if (!$image_media) {
        // Referenced media might be deleted.
        continue;
      }
      /** @var \Drupal\media\MediaSourceInterface $source */
      $source = $image_media->getSource();
      $fid = $source->getSourceFieldValue($image_media);
      $image = File::load($fid);
      $data_breakpoint_uri = [];
      foreach ($slick_breakpoints_image_style_map as $breakpoint => $image_style) {
        $data_breakpoint_uri[$breakpoint]['uri'] = ImageStyle::load($image_style)->buildUrl($image->getFileUri());
      }
      $item['#attributes']['data-breakpoint_uri'] = Json::encode($data_breakpoint_uri);
    }
    $build['#attached'] = [
      'library' => [
        'core/drupal.dialog.ajax',
        'os_widgets/slideshowWidget',
      ],
    ];
  }

}
