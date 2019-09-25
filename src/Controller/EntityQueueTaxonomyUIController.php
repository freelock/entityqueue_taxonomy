<?php

namespace Drupal\entityqueue_taxonomy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entityqueue\EntityQueueInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Returns responses for Entityqueue UI routes.
 */
class EntityQueueTaxonomyUIController extends ControllerBase {

  /**
   * Returns a form to add a new subqeue.
   *
   * @param \Drupal\entityqueue\EntityQueueInterface $entity_queue
   *   The queue this subqueue will be added to.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The taxonomy term.
   *
   * @return array
   *   The entity subqueue add form.
   */
  public function addForm(EntityQueueInterface $entity_queue, TermInterface $taxonomy_term) {
    $subqueue = $this->entityTypeManager()->getStorage('entity_subqueue')->create(
      [
        'queue' => $entity_queue->id(),
        'name' => $entity_queue->id() . '_' . $taxonomy_term->id(),
        'title' => $taxonomy_term->label(),
        'langcode' => $entity_queue->language()->getId(),
      ]);
    return $this->entityFormBuilder()->getForm($subqueue);
  }

  /**
   * Returns a list of subqueues for the term.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The taxonomy term.
   *
   * @return array
   *   The term subqueues list.
   */
  public function taxonomyTermSubqueues(TermInterface $taxonomy_term) {
    $rows = [];
    $queues = $this->getAvailableTaxonomyQueuesForEntity($this->currentUser());
    $storage = $this->entityTypeManager->getStorage('entity_subqueue');

    $destination = Url::fromRoute("entity.taxonomy_term.entityqueue_taxonomy", ['taxonomy_term' => $taxonomy_term->id()])->toString();

    foreach ($queues as $queue) {
      /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
      if ($subqueue = $storage->load($queue->id() . '_' . $taxonomy_term->id())) {
        $operations = [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit subqueue items'),
                'url' => $subqueue->toUrl('edit-form', ['query' => ['destination' => $destination]]),
              ],
            ],
          ],
        ];

        if ($subqueue->isTranslatable()) {
          $operations['data']['#links']['translate'] = [
            'title' => $this->t('Translate'),
            'url' => $subqueue->toUrl('drupal:content-translation-overview', ['query' => ['destination' => $destination]]),
          ];
        }
      }
      else {
        continue;
      }

      $rows[] = [
        $queue->label(),
        $operations,
      ];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#header' => [
        $this->t('Subqueue'),
        $this->t('Operations'),
      ],
    ];

  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *  The account to check access.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $entity_type_id = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $route_match->getParameter($entity_type_id);

    $permisssion = 'update ' . $entity->bundle() . ' entityqueue';

    if (!$account->hasPermission($permisssion)) {
      return AccessResult::forbidden();
    }

    if ($entity && $this->getAvailableTaxonomyQueuesForEntity($account)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Gets a list of queues which can hold this entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *  The account to check access.
   *
   * @return \Drupal\entityqueue\EntityQueueInterface[]
   *   An array of entity queues which can hold this entity.
   */
  protected function getAvailableTaxonomyQueuesForEntity(AccountInterface $account) {
    $storage = $this->entityTypeManager()->getStorage('entity_queue');

    $queues = [];
    /** @var \Drupal\entityqueue\Entity\EntityQueue[] $entityqueues */
    $entityqueues = $storage->loadMultiple();

    foreach ($entityqueues as $id => $entityqueue) {
      if ($entityqueue->getHandler() === 'taxonomy_term' && $entityqueue->access('update', $account)) {
        $queues[$entityqueue->id()] = $entityqueue;
      }
    }

    return $queues;
  }

}
