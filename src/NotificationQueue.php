<?php

namespace Drupal\web_push_notification;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\node\NodeInterface;

/**
 * Creates a queue for notification send.
 */
class NotificationQueue {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * SendMessage constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *  The queue factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *  The entity manager service.
   */
  public function __construct(QueueFactory $queueFactory, EntityTypeManagerInterface $entityManager) {
    $this->queueFactory = $queueFactory;
    $this->entityManager = $entityManager;
  }

  /**
   * Returns the queue.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The queue.
   */
  public function getQueue(): QueueInterface {
    return $this->queueFactory->get('web_push_queue');
  }

  /**
   * Starts a send queue with an content entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity.
   */
  public function start(NodeInterface $entity) {
    $item = new NotificationItem();
    $item->title = $entity->getTitle();
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
    $queue = $this->getQueue();
    $query = $this->entityManager
      ->getStorage('wpn_subscription')
      ->getQuery();

    $start = 0;
    $limit = $this->getProcessingLimit();

    while ($ids = $query->range($start, $limit)->execute()) {
      $item = new NotificationItem();
      $item->ids = $ids;
      $item->title = $baseItem->title;
      $item->body = $baseItem->body;
      $item->icon = $baseItem->icon;
      $item->url = $baseItem->url;
      $queue->createItem($item);
      $start += $limit;
    }
  }

  /**
   * Returns a queue processing limit.
   *
   * @return int
   *   The queue processing limit.
   */
  protected function getProcessingLimit(): int {
    return (int) \Drupal::config('web_push_notification.settings')
      ->get('queue_batch_size');
  }

}
