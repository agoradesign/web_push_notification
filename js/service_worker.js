/**
 * @file
 * Serviceworker file for browser push notification.
 */

self.addEventListener('push', function (event) {
  'use strict';
  if (!(self.Notification && self.Notification.permission === 'granted')) {
    return;
  }
// console.log('push received');
  const sendNotification = body => {
    var str = body;
    var message_array = str.split('<br>');
    var notificationOptions = {
      body: message_array[1],
      icon: message_array[2],
      badge: message_array[2],
      tag: message_array[2],
      data: {
        url: message_array[3]
      }
    };
    return self.registration.showNotification(message_array[0],
            notificationOptions);
  };

  if (event.data) {
    const message = event.data.text();
    event.waitUntil(sendNotification(message));
  }
});
self.addEventListener('notificationclick', function (event) {
  'use strict';
  event.notification.close();
  // console.log('notification click');
  Promise.resolve();
  if (event.notification.data && event.notification.data.url) {
    clients.openWindow(event.notification.data.url);
  }
});

self.addEventListener('notificationclose', function (event) {
  'use strict';
  event.waitUntil(
    Promise.all([
    ])
  );
});
