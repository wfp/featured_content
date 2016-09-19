<?php

namespace Drupal\featured_content\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\featured_content\Entity\FeaturedContentType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a form for editing featured content type entities.
 */
class FeaturedContentTypeEdit extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->getEntity();

    $form['label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this featured content type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$type->isNew(),
      '#machine_name' => array(
        'exists' => [FeaturedContentType::class, 'load'],
        'source' => ['label'],
      ),
      '#description' => $this->t('A unique machine-readable name for this featured content type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => t('A brief description.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->addMandatoryFields();
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
  }

  /**
   * Creates mandatory fields: 'term', 'content'.
   */
  protected function addMandatoryFields() {
    $type = $this->getEntity();
    foreach (static::getMandatoryFieldDefinition() as $id => $definition) {
      $field_storage = FieldStorageConfig::loadByName('featured_content', $id);
      $field = FieldConfig::loadByName('featured_content', $type->id(), $id);
      if (empty($field)) {
        $field = FieldConfig::create([
          'field_storage' => $field_storage,
          'bundle' => $type->id(),
          'label' => $definition['label'],
          'required' => $definition['required'],
          'settings' => $definition['settings'],
        ]);
        $field->save();
      }
    }

    $display = EntityFormDisplay::load("featured_content.{$type->id()}.default");
    if (!$display) {
      $display = EntityFormDisplay::create([
        'targetEntityType' => 'featured_content',
        'bundle' => $type->id(),
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $display
      ->setComponent('content', ['type' => 'entity_reference_autocomplete'])
      ->save();
  }

  /**
   * Gets the mandatory fields definition.
   *
   * @return array[]
   */
  protected static function getMandatoryFieldDefinition() {
    return [
      'term' => [
        'label' => t('Taxonomy term'),
        'required' => TRUE,
        'settings' => [
          'handler' => 'default:taxonomy_term',
          'handler_settings' => [],
        ],
      ],
      'content' => [
        'label' => t('Content'),
        'required' => TRUE,
        'settings' => [
          'handler' => 'default',
          'handler_settings' => [],
        ],
      ],
    ];
  }

}
