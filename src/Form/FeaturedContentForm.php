<?php

/**
 * @file
 * Contains \Drupal\featured_content\Form\FeaturedContentForm.
 */

namespace Drupal\featured_content\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\featured_content\Entity\FeaturedContent;
use Drupal\taxonomy\Entity\Term;
use Psr\Log\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;

/**
 * Form controller for editing 'featured_content' entities.
 */
class FeaturedContentForm extends ContentEntityForm {

  /**
   * The query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs and featured content entity form.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The query factory service.
   */
  public function __construct(EntityManagerInterface $entity_manager, QueryFactory $query_factory) {
    parent::__construct($entity_manager);
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $block_id = $route_match->getRawParameter('block');
    if ($block_id === NULL) {
      throw new InvalidParameterException('Block not passed in the URL.');
    }

    /* @var \Drupal\block\BlockInterface $block */
    $block = Block::load($block_id);
    if (empty($block)) {
      throw new InvalidParameterException("Invalid block: '$block_id'.");
    }

    $block_plugin = $block->getPlugin();
    $definition = $block_plugin->getPluginDefinition();
    if (($definition['id'] !== 'views_block') || ($definition['provider'] !== 'views')) {
      throw new InvalidArgumentException("The block '$block_id' is not a Views block.");
    }

    $configuration = $block_plugin->getConfiguration();
    if (empty($configuration['featured_content_display_plugin_id'])) {
      throw new InvalidParameterException("The block '$block_id' should use the Views 'Featured Content' display type.");
    }

    $term_id = $route_match->getRawParameter('taxonomy_term');
    if ($term_id === NULL) {
      throw new InvalidParameterException('Taxonomy term not passed in the URL.');
    }

    $taxonomy_term = Term::load($term_id);
    if (empty($taxonomy_term)) {
      throw new InvalidParameterException("Invalid taxonomy term: '$term_id'.");
    }

    $plugin_id = $block_plugin->getPluginId();
    $featured_content = FeaturedContent::loadByContext($plugin_id, $term_id);
    if (!$featured_content) {
      $featured_content = FeaturedContent::create([
        'block_plugin' => $plugin_id,
        'term' => $term_id,
        'uid' => $this->currentUser()->id(),
      ]);
    }

    return $featured_content;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

}
