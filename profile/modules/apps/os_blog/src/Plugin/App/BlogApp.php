<?php

namespace Drupal\os_blog\Plugin\App;

use Drupal\vsite\Plugin\AppPluginBase;

/**
 * Bog app.
 *
 * @App(
 *   title = @Translation("Blog"),
 *   canDisable = true,
 *   entityType = "node",
 *   bundle = {
 *    "blog"
 *   },
 *   viewsTabs = {
 *     "blog" = {
 *       "page_1",
 *       "page_2",
 *     },
 *   },
 *   id = "blog",
 *   contextualRoute = "view.blog.page_1"
 * )
 */
class BlogApp extends AppPluginBase {}
