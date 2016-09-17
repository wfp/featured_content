<?php

namespace Drupal\featured_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\featured_content\FeaturedContentTypeInterface;

/**
 * Defines the featured content type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "featured_content_type",
 *   label = @Translation("Featured content type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\featured_content\Form\FeaturedContentTypeEdit",
 *       "edit" = "Drupal\featured_content\Form\FeaturedContentTypeEdit",
 *       "delete" = "Drupal\featured_content\Form\FeaturedContentTypeDeleteConfirm",
 *     },
 *     "list_builder" = "Drupal\featured_content\FeaturedContentTypeListBuilder",
 *   },
 *   admin_permission = "administer featured content",
 *   config_prefix = "type",
 *   bundle_of = "featured_content",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/featured-content/manage/{featured_content_type}",
 *     "delete-form" = "/admin/structure/featured-content/manage/{featured_content_type}",
 *     "collection" = "/admin/structure/featured-content",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class FeaturedContentType extends ConfigEntityBundleBase implements FeaturedContentTypeInterface {

  /**
   * The machine name of this feature content type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the feature content type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this feature content type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

}
