README.txt for Web Push Notification module
-------------------------------------------

INTRODUCTION
------------

Web Push Notification module allows you to send the browser push notifications.
See (https://developer.mozilla.org/en-US/docs/Web/API/Push_API) for details.

The module registers a service worker to handle push notification and you may
to define pages where the service worker won't be registered.

The notifications may be sent by manual or when a new content is added.

REQUIREMENTS
------------

  - SSL certificate is mandatory : Push notification will only work on
    domain with SSL enabled. For testing purposes you need to use
    localhost (127.0.0.1)

  - curl extension is required (multi_curl).

  - Web Push library for PHP
    (https://github.com/web-push-libs/web-push-php)

  - Browser Push API compatibility:
    (https://developer.mozilla.org/en-US/docs/Web/API/Push_API#Browser_compatibility)


INSTALLATION
------------

 - Install the Web Push Notification module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.
