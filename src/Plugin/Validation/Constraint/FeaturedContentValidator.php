<?php

/**
 * @file
 * Contains \Drupal\featured_content\Plugin\Validation\Constraint\FeaturedContentValidator.
 */

namespace Drupal\featured_content\Plugin\Validation\Constraint;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides a validator for 'FeaturedContent' constraint.
 */
class FeaturedContentValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * The query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The block plugin manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a featured content constraint validator.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The query factory service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager service.
   */
  public function __construct(QueryFactory $query_factory, BlockManagerInterface $block_manager) {
    $this->queryFactory = $query_factory;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /* @var \Drupal\featured_content\Entity\FeaturedContent $entity */
    if (!isset($entity)) {
      return;
    }

    /* @var \Drupal\featured_content\Plugin\Validation\Constraint\FeaturedContent $constraint */

    /* @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
    $block_plugin = $this->blockManager->createInstance($entity->block_plugin->value);

    // Validate the block plugin. Should be a Views block based on
    // 'featured_content_block' Views block display.
    $definition = $block_plugin->getPluginDefinition();
    if (($definition['id'] !== 'views_block') || ($definition['provider'] !== 'views')) {
      $this->context->addViolation($constraint->notViewsBlock, ['@block' => $entity->block_plugin->value]);
    }

    $configuration = $block_plugin->getConfiguration();
    if (empty($configuration['featured_content_display_plugin_id'])) {
      $this->context->addViolation($constraint->invalidBlockViewsDisplay, ['@block' => $entity->block_plugin->value]);
    }

    // Validate relation uniqueness.
    $ids = $this->queryFactory->get('featured_content')
      ->condition('term.target_id', $entity->term->target_id)
      ->condition('block_plugin', $entity->block_plugin->value)
      ->condition('id', $entity->id(), '<>')
      ->execute();

    if ($ids) {
      $arguments = [
        '@term' => $entity->term->entity->label(),
        '@block' => $entity->block_plugin->value,
      ];
      $this->context->buildViolation($constraint->duplicateRelationMessage, $arguments)
        ->atPath('term')
        ->atPath('block_plugin')
        ->addViolation();
    }
  }

}
