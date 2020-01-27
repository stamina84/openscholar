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
 *     "events",
 *   },
 *   specialBundle = {
 *     "upcoming_events",
 *   },
 *   viewsTabs = {
 *     "calendar" = {
 *       "page_1",
 *     },
 *     "past_events_calendar" = {
 *       "page",
 *     },
 *     "upcoming_calendar" = {
 *       "page",
 *     },
 *   },
 *   id = "event",
 *   contextualRoute = "view.upcoming_calendar.page",
 *   listPageRoute = "view.view.calendar.page_1"
 * )
 */
class EventsApp extends AppPluginBase {

}
