<?php

namespace Drupal\web_push_notification;

/**
 * Notification queue item.
 */
class NotificationItem {

  /**
   * @var int[]
   *   The list of Subscription entity ID.
   */
  public $ids = [];

  /**
   * @var string
   *   The notification title.
   */
  public $title = '';

  /**
   * @var string
   *   The notification message (body).
   */
  public $body = '';

  /**
   * @var string
   *   The notification url.
   */
  public $url = '';

  /**
   * @var string
   *  The notification image/icon.
   */
  public $icon = '';

  /**
   * NotificationItem constructor.
   *
   * @param string $title
   *   The notification title.
   *
   * @param string $body
   *   The notification message (body).
   */
  public function __construct(string $title = '', string $body = '') {
    $this->title = $title;
    $this->body = $body;
  }

  /**
   * Converts the item to a web push payload.
   *
   * @return string
   *   A JSON encoded payload.
   */
  public function payload(): string {
    return json_encode([
      'title' => $this->title,
      'body' => $this->body,
      'url' => $this->url,
      'icon' => $this->icon,
    ]);
  }
}
