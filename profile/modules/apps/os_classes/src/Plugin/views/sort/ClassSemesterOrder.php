<?php

namespace Drupal\os_classes\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Handler to sort classes by semester value.
 *
 * @ViewsSort("os_classes_class_semester_order")
 */
class ClassSemesterOrder extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $this->query->addOrderBy(NULL, "CASE {$this->tableAlias}.{$this->realField} WHEN 'winter' THEN 1 WHEN 'fall' THEN 2 WHEN 'summer' THEN 3 WHEN 'spring' THEN 4 ELSE 0 END", $this->options['order'], "semester_order_value");
  }

}
