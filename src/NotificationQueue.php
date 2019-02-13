<?php

namespace Drupal\web_push_notification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;

/**
 * Creates a queue for notification send.
 */
class NotificationQueue {

  /**
   * @var Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * SendMessage constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory
   *  The queue factory.
   */
  public function __construct(QueueFactory $queueFactory, EntityTypeManagerInterface $entityManager) {
    $this->queueFactory = $queueFactory;
    $this->entityManager = $entityManager;
  }

  public function getQueue(): QueueInterface {
    return $this->queueFactory->get('web_push_queue');
  }

  public function start(EntityInterface $entity) {
    $this->startWithItem(new NotificationItem('test', 'test'));
  }

  public function startWithItem(NotificationItem $baseItem) {
    $queue = $this->getQueue();
    $query = $this->entityManager
      ->getStorage('wpn_subscription')
      ->getQuery();

    $start = 0;
    $limit = 10; // TODO: configurable

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

}
