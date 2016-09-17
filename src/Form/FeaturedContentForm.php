<?php

namespace Drupal\featured_content\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\featured_content\Entity\FeaturedContent;

/**
 * Form controller for editing 'featured_content' entities.
 */
class FeaturedContentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /** @var \Drupal\featured_content\Entity\FeaturedContentType $type */
    $type = $route_match->getParameter('featured_content_type');

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
    $block_plugin = $route_match->getParameter('views_block_plugin');
    $block_plugin_id = $block_plugin->getPluginId();

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $route_match->getParameter('taxonomy_term');

    $featured_content = FeaturedContent::loadByContext($type->id(), $block_plugin_id, $term->id());
    if (!$featured_content) {
      $featured_content = FeaturedContent::create([
        'type' => $type->id(),
        'block_plugin' => $block_plugin_id,
        'term' => $term->id(),
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
