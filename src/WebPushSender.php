<?php

namespace Drupal\web_push_notification;

use Drupal\web_push_notification\Entity\Subscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription as PushSubscription;

/**
 * Class WebPushSender
 *
 * @package Drupal\web_push_notification
 */
class WebPushSender implements WebPushSenderInterface {

  protected $keyHelper;

  protected $webPush;

  public function __construct(KeysHelper $keysHelper) {
    $this->keyHelper = $keysHelper;
  }

  public function getWebPush(): WebPush {
    if (!$this->webPush) {
      $auth = $this->keyHelper->getVapidAuth();
      $this->webPush = new WebPush($auth);
    }
    return $this->webPush;
  }

  public function send(NotificationItem $item) {
    if (empty($item->ids)) {
      return;
    }
    $webPush = $this->getWebPush();
    $notifications = $this->createNotifications($item);
    foreach ($notifications as $notification) {
      $webPush->sendNotification($notification['subscription'], $notification['payload']);
    }
    $webPush->flush(count($notifications));
  }

  protected function createNotifications(NotificationItem $item): array {
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