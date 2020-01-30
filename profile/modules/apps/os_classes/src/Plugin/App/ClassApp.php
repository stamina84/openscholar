<?php

namespace Drupal\os_classes\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * Class app.
 *
 * @App(
 *   title = @Translation("Class"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *    "class"
 *   },
 *   viewsTabs = {
 *     "os_classes" = {
 *       "page_1",
 *     },
 *   },
 *   id = "class",
 *   contextualRoute = "view.os_classes.page_1"
 * )
 */
class ClassApp extends AppPluginBase {

}
