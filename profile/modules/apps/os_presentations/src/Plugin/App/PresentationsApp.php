<?php

namespace Drupal\os_presentations\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * Presentations app.
 *
 * @App(
 *   title = @Translation("Presentation"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *     "presentation"
 *   },
 *   viewsTabs = {
 *     "presentations" = {
 *       "page_1",
 *     },
 *   },
 *   id = "presentations",
 *   contextualRoute = "view.presentations.page_1"
 * )
 */
class PresentationsApp extends AppPluginBase {

}
