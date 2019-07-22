<?php

namespace Drupal\entityqueue_taxonomy;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher;

/**
 * Matcher class to get autocompletion results for entity reference.
 */
class EntityQueueTaxonomyAutocompleteMatcher extends EntityAutocompleteMatcher {

  /**
   * @inheritdoc
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '', $vocabulary = NULL, $taxonomy_term = NULL) {
    $matches = [];

    $options = $selection_settings + [
        'target_type' => $target_type,
        'handler' => $selection_handler,
      ];

    /** @var \Drupal\entityqueue_taxonomy\Plugin\EntityReferenceSelection\EntityQueueTaxonomyNodeSelection $handler */
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10, $vocabulary, $taxonomy_term);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $key = "$label ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
