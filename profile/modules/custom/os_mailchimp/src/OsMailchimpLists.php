<?php

namespace Drupal\os_mailchimp;

use Mailchimp\MailchimpLists;

/**
 * Class OsMailchimpLists.
 *
 * @package Drupal\os_mailchimp
 */
class OsMailchimpLists implements OsMailchimpListsInterface {

  /**
   * Convert mailchimp lists to form options.
   *
   * @param array $lists
   *   Generated array by mailchimp_get_lists().
   *
   * @return array
   *   Converted form options.
   */
  public function mailChimpListsToOptions(array $lists) : array {
    $options = [];
    foreach ($lists as $list_id => $list) {
      $options[$list_id] = $list->name;
    }
    return $options;
  }

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
  public function osMailchimpGetLists($api_key, $timeout = 60, $count = 500) : array {
    $lists = [];
    $mcapi = new MailchimpLists($api_key, 'apikey', $timeout);
    if ($mcapi != NULL) {
      $result = $mcapi->getLists(['count' => $count]);
      foreach ($result->lists as $list) {
        $int_category_data = $mcapi->getInterestCategories($list->id, ['count' => $count]);
        $list->intgroups = [];
        foreach ($int_category_data->categories as $interest_category) {
          $list->intgroups[] = $interest_category;
        }

        $lists[$list->id] = $list;

        // Append mergefields:
        $mergefields = $mcapi->getMergeFields($list->id, ['count' => $count]);
        if ($mergefields->total_items > 0) {
          $lists[$list->id]->mergevars = $mergefields->merge_fields;
        }
      }

      uasort($lists, '_mailchimp_list_cmp');
    }

    return $lists;
  }

}
