<?php

namespace Drupal\featured_content;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a list builder for featured_content_type entities.
 */
class FeaturedContentTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Label'),
      'description' => [
        'data' => $this->t('Description'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ]
    ]+ parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    return [
      'label' => [
        'data' => $entity->label(),
        'class' => ['menu-label'],
      ],
      'description' => [
        'data' => [
          '#markup' => $entity->getDescription()
        ]
      ]
    ] + parent::buildRow($entity);
  }

}
