<?php

/**
 * @file
 * Contains \Drupal\featured_content\Cache\TaxonomyTermRouteCacheContext.
 */

namespace Drupal\featured_content\Cache;

use Drupal\Core\Cache\Context\RouteCacheContext;

/**
 * Defines the taxonomy term route cache sub-context.
 *
 * Cache context ID: 'route.taxonomy_term'.
 */
class TaxonomyTermRouteCacheContext extends RouteCacheContext {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Taxonomy term context');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    if ($this->routeMatch->getRouteName() !== 'entity.taxonomy_term.canonical') {
      return '0';
    }

    return (string) ((int) $this->routeMatch->getRawParameter('taxonomy_term'));
  }

}
