"use strict";

self.addEventListener('push', function(event)
{
	if (!(self.Notification && self.Notification.permission === 'granted'))
	{
		return;
	}

	try
	{
		var data = event.data.json();
	}
	catch (e)
	{
		console.warn('Received push notification but payload not in the expected format.');
		return;
	}

	if (!data || !data.title || !data.body)
	{
		console.warn('Received push notification but no payload data or required fields missing.');
		return;
	}

	data['last_count'] = 0;

	var options = {
		body: data.body,
		dir: data.dir || 'ltr',
		data: data
	};
	if (data.badge)
	{
		options['badge'] = data.badge;
	}
	if (data.icon)
	{
		options['icon'] = data.icon;
	}

	var notificationPromise, count;

	if (data.tag && data.tag_phrase)
	{
		options['tag'] = data.tag;
		options['renotify'] = true;

		self.registration.getNotifications({ tag: data.tag })
			.then(function(notifications)
			{
				var lastKey = (notifications.length - 1);

				var notification = notifications[lastKey];
				if (notification)
				{
					count = notification.data.last_count + 1;
					options.data.last_count = count;
				}

			})
			.then(function()
			{
				if (count > 0)
				{
					options.body = options.body +  ' ' + data.tag_phrase.replace('{count}', count.toString());
				}

				notificationPromise = self.registration.showNotification(data.title, options);
			});
	}
	else
	{
		notificationPromise = self.registration.showNotification(data.title, options);
	}

	event.waitUntil(notificationPromise);
});

self.addEventListener('notificationclick', function(event)
{
	var notification = event.notification;
	
	notification.close();

	if (notification.data.url)
	{
		event.waitUntil(clients.openWindow(notification.data.url));
	}
});
