<?php

/**
 * @file
 * Contains \Drupal\featured_content\Plugin\Validation\Constraint\FeaturedContent.
 */

namespace Drupal\featured_content\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Composite constraint for the 'featured_content' entity.
 *
 * @Constraint(
 *   id = "FeaturedContent",
 *   label = @Translation("Featured content constraint", context = "Validation"),
 *   type = "entity"
 * )
 */
class FeaturedContent extends CompositeConstraintBase {

  /**
   * Duplicate validation error message.
   *
   * @var string
   */
  public $duplicateRelationMessage = "Relation term '@term', block '@block' already exists.";

  /**
   * Not a Views block error message.
   *
   * @var string
   */
  public $notViewsBlock = "The block '@block' is not a Views block.";

  /**
   * Invalid Views block display.
   *
   * @var string
   */
  public $invalidBlockViewsDisplay = "The block '@block' should use the Views 'featured_content_block' display type.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['term', 'block_plugin'];
  }

}
