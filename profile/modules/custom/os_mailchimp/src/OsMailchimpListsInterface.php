<?php

namespace Drupal\os_mailchimp;

/**
 * Helper to fetch mailchimp lists based on api_key.
 */
interface OsMailchimpListsInterface {

  /**
   * Handles mailchimp lists to form options.
   *
   * @param array $lists
   *   Generated array by mailchimp_get_lists().
   *
   * @return array
   *   Converted form options.
   */
  public function mailChimpListsToOptions(array $lists) : array;

  /**
   * Fetch mailchimp lists for a Vsite.
   *
   * @param string $api_key
   *   The API key.
   * @param int $timeout
   *   Connection timeout in seconds.
   * @param int $count
   *   Limit to fetch number of lists.
   *
   * @return array
   *   Mailchimp list.
   */
  public function osMailchimpGetLists($api_key, $timeout, $count) : array;

}
