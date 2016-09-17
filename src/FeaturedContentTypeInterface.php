<?php

namespace Drupal\featured_content;

/**
 * Provides an interface for featured content type entities.
 */
interface FeaturedContentTypeInterface {

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this featured content type.
   */
  public function getDescription();

}
