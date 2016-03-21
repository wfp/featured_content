<?php

/**
 * @file
 * Contains \Drupal\featured_content\Controller\FeaturedContentAdmin.
 */

namespace Drupal\featured_content\Controller;

use Drupal\Core\Controller\ControllerBase;

class FeaturedContentAdmin extends ControllerBase {

  /**
   * Shows the featured content admin page.
   *
   * @return array
   *   A renderable array.
   */
  public function overview() {
    return [
      [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Configure the type of entities that can be featured from Manage fields > Content > Edit'),
        ]
      ],
    ];
  }

}
