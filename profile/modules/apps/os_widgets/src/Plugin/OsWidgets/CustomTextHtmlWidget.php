<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Component\Utility\Html;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;

/**
 * Class CustomTextHtmlWidget.
 *
 * @OsWidget(
 *   id = "custom_text_html_widget",
 *   title = @Translation("Custom Text/HTML")
 * )
 */
class CustomTextHtmlWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    if (empty($block_content)) {
      return;
    }
    $field_css_classes_values = $block_content->get('field_css_classes')->getValue();
    if (!empty($field_css_classes_values[0]['value'])) {
      $build['#extra_classes'] = $this->parseCssClasses($field_css_classes_values[0]['value']);
    }
  }

  /**
   * Convert css classes user input string to array.
   *
   * @param string $css_string
   *   Classes in string.
   *
   * @return array
   *   Classes in trimmed array.
   */
  public function parseCssClasses($css_string) {
    $classes = [];
    $user_classes = explode(' ', $css_string);
    foreach ($user_classes as $user_class) {
      $user_class = Html::cleanCssIdentifier($user_class);
      if (!empty($user_class)) {
        $classes[] = $user_class;
      }
    }
    return $classes;
  }

}
