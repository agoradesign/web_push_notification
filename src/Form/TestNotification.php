<?php

namespace Drupal\web_push_notification\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\web_push_notification\KeysHelper;
use Drupal\web_push_notification\NotificationItem;
use Drupal\web_push_notification\NotificationQueue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows to send a test notification to subscribed users.
 */
class TestNotification extends FormBase {

  /**
   * @var \Drupal\web_push_notification\KeysHelper
   */
  protected $keysHelper;

  /**
   * @var \Drupal\web_push_notification\NotificationQueue
   */
  protected $queue;

  /**
   * The web_push_notification config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The subscription entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The queue worker manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueWorkerManger;

  /**
   * Constructs a new TestNotification object.
   *
   * @param \Drupal\web_push_notification\KeysHelper $keys_helper
   *   The keys helper service.
   * @param \Drupal\web_push_notification\NotificationQueue $queue
   *   The notification queue service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_worker_manager
   *   The queue worker manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(
      KeysHelper $keys_helper,
      NotificationQueue $queue,
      ConfigFactoryInterface $config_factory,
      EntityTypeManagerInterface $entity_type,
      QueueWorkerManagerInterface $queue_worker_manager
  ) {
    $this->keysHelper = $keys_helper;
    $this->queue = $queue;
    $this->config = $config_factory->get('web_push_notification.settings');
    $this->storage = $entity_type->getStorage('wpn_subscription');
    $this->queueWorkerManger = $queue_worker_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('web_push_notification.keys_helper'),
      $container->get('web_push_notification.queue'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_push_notification_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $count = $this->storage->getQuery()->count()->execute();
    if ($count == 0) {
      $this->messenger()->addWarning($this->t('No subscriptions found.'));
      return $form;
    }

    $form['test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test notification'),
    ];
    $form['test']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 128,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['test']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Keep in mind, the message will be trimmed to %chars characters.', [
        '%chars' => $this->config->get('body_length') ?: 100,
      ]),
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['test']['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#description' => $this->t('Enter the icon URL which will show in the notification.'),
      '#maxlength' => 512,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['test']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#description' => $this->t('Enter the URL on which user will redirect after clicking on the notification.'),
      '#maxlength' => 512,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $item = new NotificationItem();
    $item->title = $form_state->getValue('title');
    $item->body = $form_state->getValue('body');
    $item->icon = $form_state->getValue('icon');
    $item->url = $form_state->getValue('url');

    // TODO: make a batch process.

    $this->queue->startWithItem($item);
    $queue = $this->queue->getQueue();
    $worker = $this->queueWorkerManger->createInstance('web_push_queue');

    while ($unprocessed = $queue->claimItem()) {
      try {
        $worker->processItem($unprocessed->data);
        $queue->deleteItem($unprocessed);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
      }
      catch (\Exception $e) {

      }
    }
  }

}
