<?php

namespace Drupal\os_profiles\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * Profiles app.
 *
 * @App(
 *   title = @Translation("Person"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *     "person"
 *   },
 *   viewsTabs = {
 *     "people" = {
 *       "page_1",
 *     },
 *   },
 *   id = "profiles",
 *   contextualRoute = "view.people.page_1",
 *   listPageRoute = "view.people.page_1",
 * )
 */
class ProfilesApp extends AppPluginBase {

}
