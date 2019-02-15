<?php

namespace Drupal\web_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Creates a queue for notification send.
 */
class NotificationQueue {

  /**
   * The web_push_queue queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The web_push_notification config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * SendMessage constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity manager service.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(QueueFactory $queueFactory, EntityTypeManagerInterface $entityManager, ConfigFactoryInterface $config_factory) {
    $this->queue = $queueFactory->get('web_push_queue');
    $this->entityManager = $entityManager;
    $this->config = $config_factory->get('web_push_notification.settings');
  }

  /**
   * Starts a send queue with an content entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity.
   */
  public function start(NodeInterface $entity) {
    $bundle = $entity->bundle();
    $fields = $this->config->get("fields.$bundle");

    $item = new NotificationItem();
    $item->title = $entity->getTitle();

    $body = $entity->get($fields['body'])->value;
    if (isset($fields['body']) && $body) {
      $item->body = $this->prepareBody($body);
    }

    if (isset($fields['icon'])) {
      $item->icon = $this->getIconUrl($entity->get($fields['icon']));
    }

    $item->url = $entity->toUrl('canonical', [
      'absolute' => TRUE,
    ])->toString();

    $this->startWithItem($item);
  }

  /**
   * Starts a send queue with a notification item.
   *
   * @param \Drupal\web_push_notification\NotificationItem $baseItem
   *   The notification item.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function startWithItem(NotificationItem $baseItem) {
    $query = $this->entityManager
      ->getStorage('wpn_subscription')
      ->getQuery();

    $start = 0;
    $limit = $this->config->get('queue_batch_size');

    while ($ids = $query->range($start, $limit)->execute()) {
      $item = new NotificationItem();
      $item->ids = $ids;
      $item->title = $baseItem->title;
      $item->body = $baseItem->body;
      $item->icon = $baseItem->icon;
      $item->url = $baseItem->url;
      $this->queue->createItem($item);
      $start += $limit;
    }
  }

  /**
   * Returns an icon url from the entity field.
   *
   * @param \Drupal\Core\Field\FieldItemList $field
   *   The entity field.
   *
   * @return string
   *   An icon url.
   */
  protected function getIconUrl($field): string {
    if ($field instanceof FileFieldItemList) {
      if (!($entities = $field->referencedEntities())) {
        return '';
      }
      $file = reset($entities);
      return $file->url();
    }
    return '';
  }

  /**
   * Prepares a notification body: trim and strip html tags.
   *
   * @param string $raw
   *   The raw text.
   *
   * @return string
   *   A trimmed and filtered text.
   */
  protected function prepareBody(string $raw): string {
    $body = FieldPluginBase::trimText([
      'max_length' => $this->config->get('body_length') ?: 100,
      'html' => FALSE,
    ], $raw);
    $body = strip_tags($raw);
    return $body;
  }

}
