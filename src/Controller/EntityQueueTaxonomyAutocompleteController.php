<?php

namespace Drupal\entityqueue_taxonomy\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\Controller\EntityAutocompleteController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class EntityQueueTaxonomyAutocompleteController extends EntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   *
   * @var \Drupal\entityqueue_taxonomy\EntityQueueTaxonomyAutocompleteMatcher
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entityqueue_taxonomy.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete(Request $request, $target_type, $selection_handler, $selection_settings_key, $vocabulary = NULL, $taxonomy_term = NULL) {
    $matches = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));

      // Selection settings are passed in as a hashed key of a serialized array
      // stored in the key/value store.
      $selection_settings = $this->keyValue->get($selection_settings_key, FALSE);
      if ($selection_settings !== FALSE) {
        $selection_settings_hash = Crypt::hmacBase64(serialize($selection_settings) . $target_type . $selection_handler . $vocabulary . $taxonomy_term, Settings::getHashSalt());
        if ($selection_settings_hash !== $selection_settings_key) {
          // Disallow access when the selection settings hash does not match the
          // passed-in key.
          throw new AccessDeniedHttpException('Invalid selection settings key.');
        }
      }
      else {
        // Disallow access when the selection settings key is not found in the
        // key/value store.
        throw new AccessDeniedHttpException();
      }

      // Provide tvocabulary and taxonomy term ID to matcher.
      $matches = $this->matcher->getMatches($target_type, $selection_handler, $selection_settings, $typed_string, $vocabulary, $taxonomy_term);
    }

    return new JsonResponse($matches);
  }

}
