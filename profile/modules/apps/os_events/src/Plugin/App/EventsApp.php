<?php

namespace Drupal\os_events\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * Events app.
 *
 * @App(
 *   title = @Translation("Event"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *     "events"
 *   },
 *   viewsTabs = {
 *     "calendar" = {
 *       "page_1",
 *     },
 *   },
 *   id = "event",
 *   contextualRoute = "view.calendar.page_1"
 * )
 */
class EventsApp extends AppPluginBase {

}
