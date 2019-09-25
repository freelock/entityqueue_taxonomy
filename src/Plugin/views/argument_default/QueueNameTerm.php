<?php

namespace Drupal\entityqueue_taxonomy\Plugin\views\argument_default;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\taxonomy\Plugin\views\argument_default\Tid;

/**
 * @ViewsArgumentDefault(
 *  id = "entityqueue_name_term",
 *  title = @Translation("EntitySubqueue name from Term ID"),
 * )
 */
class QueueNameTerm extends Tid {

  /**
   * @inheritdoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // We cannot handle multiple terms...
    unset($form['anyall']);

    // Add list of queues.
    $queues = EntityQueue::loadMultiple();
    $options = [];
    foreach ($queues as $queue) {
      if ($queue->getHandler() == 'taxonomy_term') {
        $options[$queue->id()] = $queue->label();
      }
    }
    $form['entityqueue'] = [
      '#type' => 'select',
      '#title' => $this->t('Taxonomy EntityQueue ID'),
      '#default_value' => $this->options['entityqueue'],
      '#options' => $options,
    ];
  }

  /**
   * @inheritdoc
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    // Don't need anyall.
    unset($options['anyall']);
    // Do need queue name.
    $options['entityqueue'] = ['default' => NULL];

    return $options;
  }

  /**
   * @inheritdoc
   */
  public function getArgument() {
    $tid = parent::getArgument();
    $queue = $this->options['entityqueue'];
    return $queue . '_' . $tid;
  }

}
