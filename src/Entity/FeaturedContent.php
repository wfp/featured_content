<?php

/**
 * @file
 * Contains \Drupal\featured_content\Entity\FeaturedContent.
 */

namespace Drupal\featured_content\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'featured_content' content entity.
 *
 * @ContentEntityType(
 *   id = "featured_content",
 *   label = @Translation("Featured content relation"),
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid"
 *   },
 *   base_table = "featured_content",
 *   translatable = FALSE,
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "\Drupal\featured_content\Form\FeaturedContentForm",
 *     },
 *   },
 *   field_ui_base_route = "featured_content.ui",
 *   constraints = {
 *     "FeaturedContent" = {}
 *   },
 * )
 */
class FeaturedContent extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The relation ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The relation UUID.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Featured by'))
      ->setDescription(t('The username that featured the content.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDefaultValueCallback(static::class . '::getCurrentUserId');

    $fields['block_plugin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Block plugin'))
      ->setDescription(t('The plugin of the block storing featured content.'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $arguments = [
      '@vocabulary' => $this->term->entity->getVocabularyId(),
      '@term' => $this->term->entity->label(),
      '@type' => $this->content->entity->getEntityType()->getLabel(),
      '@block' => $this->block_plugin->value,
    ];
    return new TranslatableMarkup("@type featured content: @vocabulary '@term', block: '@block'", $arguments);
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Loads a featured_content entity by its block plugin and taxonomy term ids.
   *
   * @param string $block_plugin_id
   *   The block plugin id.
   * @param int $taxonomy_term_id
   *   The taxonomy term id.
   *
   * @return \Drupal\featured_content\Entity\FeaturedContent|null
   *   The entity or NULL.
   */
  public static function loadByContext($block_plugin_id, $taxonomy_term_id) {
    $ids = \Drupal::entityQuery('featured_content')
      ->condition('block_plugin', $block_plugin_id)
      ->condition('term.target_id', $taxonomy_term_id)
      ->execute();
    if (!$id = reset($ids)) {
      return NULL;
    }
    return FeaturedContent::load($id);
  }

}
