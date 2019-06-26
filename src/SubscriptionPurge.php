<?php

namespace Drupal\web_push_notification;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Minishlink\WebPush\MessageSentReport;

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
   * @param \Minishlink\WebPush\MessageSentReport $report
   *   The notification statuses list.
   *
   * @see \Minishlink\WebPush\WebPush::flush()
   */
  public function delete(MessageSentReport $report) {
    if (!$report->isSuccess()) {
      $this->deleteSubscription($report->getEndpoint());
    }
  }

  /**
   * Deletes a subscription entity.
   *
   * @param string $endpoint
   *   The subscription endpoint.
   */
  protected function deleteSubscription($endpoint) {
    $ids = $this->entityStorage->getQuery()
      ->condition('endpoint', $endpoint)
      ->execute();
    if (empty($ids)) {
      return;
    }

    // TODO: maybe it's better to use loadByProperties() ?
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
