self.addEventListener("push", function (event) {
  console.log("Push message!", event.data.text());
  const payload = JSON.parse(event.data.text());

  event.waitUntil(
    self.registration.showNotification(payload.title, {
      body: payload.body,
      requireInteraction: payload.requireInteraction,
      icon: payload.icon,
      image: payload.image,
      data: {
        link: payload.link,
      },
    })
  );
});

self.addEventListener("notificationclick", function (event) {
  console.log("Notification click: tag", event.notification.tag);
  event.notification.close();
  if (event.notification.data.link)
    event.waitUntil(clients.openWindow(event.notification.data.link));
});
