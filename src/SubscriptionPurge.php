<?php

namespace Drupal\web_push_notification;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * This service deletes subscriptions that 'rejected' during web push send.
 */
class SubscriptionPurge {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * SubscriptionPurge constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityType
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityType) {
    $this->entityStorage = $entityType->getStorage('wpn_subscription');
  }

  /**
   * Deletes subscriptions whose notification response status isn't success.
   *
   * @param array $statuses
   *   The notification statuses list.
   *
   * @see \Minishlink\WebPush\WebPush::flush()
   */
  public function delete(array $statuses) {
    foreach ($statuses as $status) {
      if ($this->isRejected($status)) {
        $this->deleteSubscription($status['endpoint']->__toString());
      }
    }
  }

  /**
   * Has a notification status 'rejected'.
   *
   * @param array $status
   *   The notification status item.
   *
   * @return bool
   *   Rejected or not.
   */
  public function isRejected(array $status): bool {
    return isset($status['success']) && $status['success'] === false;
  }

  /**
   * Deletes a subscription entity.
   *
   * @param string $endpoint
   *   The subscription endpoint.
   */
  protected function deleteSubscription(string $endpoint) {
    $ids = $this->entityStorage->getQuery()
      ->condition('endpoint', $endpoint)
      ->execute();
    if (empty($ids)) {
      return;
    }

    $entities = array_filter(array_map(function ($id) {
      return $this->entityStorage->load($id);
    }, $ids));

    try {
      $this->entityStorage->delete($entities);
    }
    catch (EntityStorageException $e) {
      // TODO: log.
    }
  }

}