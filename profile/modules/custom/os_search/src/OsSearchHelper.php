<?php

namespace Drupal\os_search;

/**
 * Helper class for search.
 */
class OsSearchHelper {

  /**
   * Alter query for search added condition for custom_date.
   */
  public function addCustomDateFilterQuery(&$query, $query_params): void {
    $year = $query_params['year'];
    $month = $query_params['month'];
    $day = $query_params['day'];
    $hour = $query_params['hour'];
    $minutes = $query_params['minutes'];

    if (($year) !== NULL) {
      if (!isset($month)) {
        $start_date = strtotime('01-01-' . $year);
        $end_date = strtotime('31-12-' . $year);
      }
      elseif (!isset($day)) {
        $start_date = strtotime('01-' . $month . '-' . $year);
        $end_date = strtotime('31-' . $month . '-' . $year);
      }
      elseif (!isset($hour)) {
        $start_date = strtotime($day . '-' . $month . '-' . $year . ' 00:00:00');
        $end_date = strtotime($day . '-' . $month . '-' . $year . ' 23:59:59');
      }
      elseif (!isset($minutes)) {
        $start_date = strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':00:00');
        $end_date = strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':59:59');
      }
      else {
        $start_date = strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':' . $minutes . ':00');
        $end_date = strtotime($day . '-' . $month . '-' . $year . ' ' . $hour . ':' . $minutes . ':59');
      }

      $query->addCondition('custom_date', $start_date, '>=');
      $query->addCondition('custom_date', $end_date, '<=');

    }
  }

}
