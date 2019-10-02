<?php

namespace Drupal\os_wysiwyg;

/**
 * PurifyHtmlHelperInterface class.
 */
interface PurifyHtmlHelperInterface {

  /**
   * Get purify text.
   *
   * @param mixed $text
   *   Text.
   * @param array $config
   *   Config.
   *
   * @return mixed
   *   Text
   */
  public function getPurifyHtml($text, array $config);

}
