<?php

namespace Drupal\os_news\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * News app.
 *
 * @App(
 *   title = @Translation("News"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *    "news"
 *   },
 *   viewsTabs = {
 *     "news" = {
 *       "page_1",
 *       "page_2",
 *     },
 *   },
 *   id = "news"
 * )
 */
class NewsApp extends AppPluginBase {

}
