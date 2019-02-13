<?php

namespace Drupal\web_push_notification;

use Drupal\web_push_notification\Entity\Subscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription as PushSubscription;

/**
 * Sends push notifications.
 *
 * @package Drupal\web_push_notification
 */
class WebPushSender implements WebPushSenderInterface {

  /**
   * @var \Drupal\web_push_notification\KeysHelper
   */
  protected $keyHelper;

  /**
   * @var \Minishlink\WebPush\WebPush
   */
  protected $webPush;

  /**
   * WebPushSender constructor.
   *
   * @param \Drupal\web_push_notification\KeysHelper $keysHelper
   *   The keys helper service.
   */
  public function __construct(KeysHelper $keysHelper) {
    $this->keyHelper = $keysHelper;
  }

  /**
   * Returns the web push sender engine.
   *
   * @return \Minishlink\WebPush\WebPush
   *   The sender engine.
   *
   * @throws \Drupal\web_push_notification\AuthKeysException
   * @throws \ErrorException
   */
  public function getWebPush(): WebPush {
    if (!$this->webPush) {
      $auth = $this->keyHelper->getVapidAuth();
      $this->webPush = new WebPush($auth);
    }
    return $this->webPush;
  }

  /**
   * Sends notifications.
   *
   * @param \Drupal\web_push_notification\NotificationItem $item
   *   The notification item.
   *
   * @throws \Drupal\web_push_notification\AuthKeysException
   * @throws \ErrorException
   */
  public function send(NotificationItem $item) {
    if (empty($item->ids)) {
      return;
    }
    $webPush = $this->getWebPush();
    $subscriptions = $this->createSubscriptions($item);
    foreach ($subscriptions as $subscription) {
      $webPush->sendNotification($subscription['subscription'], $subscription['payload']);
    }
    $webPush->flush(count($subscriptions));
  }

  /**
   * Expands a notification item to a subscription list.
   *
   * @param \Drupal\web_push_notification\NotificationItem $item
   *   The notification item.
   *
   * @return Minishlink\WebPush\Subscription[]
   *   A list of push subscriptions.
   *
   * @throws \ErrorException
   */
  protected function createSubscriptions(NotificationItem $item): array {
    $notifications = [];
    foreach ($item->ids as $subscription_id) {
      if (!($subscription = Subscription::load($subscription_id))) {
        continue;
      }
      $notifications[] = [
        'subscription' => PushSubscription::create([
          'endpoint' => $subscription->getEndpoint(),
          'publicKey' => $subscription->getPublicKey(),
          'authToken' => $subscription->getToken(),
        ]),
        'payload' => $item->payload(),
      ];
    }
    return $notifications;
  }

}