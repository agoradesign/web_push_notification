<?php

namespace Drupal\web_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * @var \Drupal\web_push_notification\SubscriptionPurge
   */
  protected $purge;

  /**
   * The web_push_notification config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * WebPushSender constructor.
   *
   * @param \Drupal\web_push_notification\KeysHelper $keysHelper
   *   The keys helper service.
   * @param \Drupal\web_push_notification\SubscriptionPurge $purge
   *   The subscription purge service.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(KeysHelper $keysHelper, SubscriptionPurge $purge, ConfigFactoryInterface $config_factory) {
    $this->keyHelper = $keysHelper;
    $this->purge = $purge;
    $this->config = $config_factory->get('web_push_notification.settings');
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
      $options = $this->getPushOptions();
      $this->webPush = new WebPush($auth, $options);
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
    $results = $webPush->flush(count($subscriptions));
    if (is_array($results)) {
      $this->purge->delete($results);
    }
  }

  /**
   * Gets the default push options.
   *
   * @return array
   *   The list of options.
   *
   * @see https://github.com/web-push-libs/web-push-php#notifications-and-default-options
   */
  public function getPushOptions(): array {
    return [
      'TTL' => $this->config->get('push_ttl') ?: 100,
      'urgency' => 'normal',
    ];
  }

  /**
   * Expands a notification item to a subscription list.
   *
   * @param \Drupal\web_push_notification\NotificationItem $item
   *   The notification item.
   *
   * @return \Minishlink\WebPush\Subscription[]
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