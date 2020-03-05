<?php

namespace Drupal\vsite\Helper;

use Drupal\Core\Utility\Token;

/**
 * Class VsiteTokenHelper.
 *
 * @package Drupal\vsite\Helper
 */
class VsiteTokenHelper {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * Returns an array of available token replacements.
   *
   * @param bool $prepared
   *   Whether to return the raw token info for each token or an array of
   *   prepared tokens for each type. E.g. "[view:name]".
   * @param array $types
   *   An array of additional token types to return, defaults to 'site' and
   *   'view'.
   *
   * @return array
   *   An array of available token replacement info or tokens, grouped by type.
   */
  public function getAvailableGlobalTokens($prepared = FALSE, array $types = []) {
    $info = $this->token->getInfo();
    // Site and view tokens should always be available.
    $types += ['site', 'view'];
    $available = array_intersect_key($info['tokens'], array_flip($types));

    // Construct the token string for each token.
    if ($prepared) {
      $prepared = [];
      foreach ($available as $type => $tokens) {
        foreach (array_keys($tokens) as $token) {
          $prepared[$type][] = "[$type:$token]";
        }
      }

      return $prepared;
    }

    return $available;
  }

}
