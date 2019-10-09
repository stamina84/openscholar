<?php

namespace Drupal\os_wysiwyg;

use Drupal\filter\FilterProcessResult;

/**
 * PurifyHtmlHelper class.
 */
class PurifyHtmlHelper implements PurifyHtmlHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function getPurifyHtml($text, $config) {
    $purifier = new \HTMLPurifier($config);
    $purified_text = $purifier->purify($text);
    return new FilterProcessResult($purified_text);
  }

}
