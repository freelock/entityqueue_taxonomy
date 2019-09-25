<?php

namespace Drupal\entityqueue_taxonomy\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "entityqueue_taxonomy:node",
 *   label = @Translation("Node selection for Entityqueue Taxonomy"),
 *   entity_types = {"node"},
 *   group = "entityqueue_taxonomy",
 *   weight = 1
 * )
 */
class EntityQueueTaxonomyNodeSelection extends NodeSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0, $vocabulary = NULL, $taxonomy_term = NULL) {
    $target_type = $this->getConfiguration()['target_type'];
    $configuration = $this->getConfiguration();
    $bundles = $configuration['target_bundles'];
    $taxonomy_fields = [];

    foreach ($bundles as $bundle) {
      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
      $field_manager = \Drupal::service('entity_field.manager');
      $fields = $field_manager->getFieldDefinitions($target_type, $bundle);
      // Get all taxonomy fields of entity type.
      $taxonomy_fields += $this->getTaxonomyFields($fields, $vocabulary);
    }

    $query = $this->buildEntityQuery($match, $match_operator);

    if ($taxonomy_fields) {
      // Add found fields to 'or' condition.
      $or = $query->orConditionGroup();
      foreach ($taxonomy_fields as $field_name => $field) {
        $or->condition($field_name, $taxonomy_term);
      }
      $query->condition($or);
    }

    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
    }

    return $options;
  }

  /**
   * Get taxonomy entity reference fields for the vocabulary.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $fields
   *   List of field definitions.
   * @param string $vocabulary
   *   The vocabulary ID.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Array of taxonomy term entity reference fields for vocabulary.
   */
  protected function getTaxonomyFields(array $fields, $vocabulary) {
    $result = [];
    foreach ($fields as $field_name => $field) {
      if ($field->getType() !== 'entity_reference' || $field->getSetting('handler') !== 'default:taxonomy_term') {
        continue;
      }

      $handler_settings = $field->getSetting('handler_settings');
      if (!in_array($vocabulary, $handler_settings['target_bundles'])) {
        continue;
      }

      $result[$field_name] = $field;
    }

    return $result;
  }

}
