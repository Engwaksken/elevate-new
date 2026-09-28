import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:timezone/data/latest.dart' as tz;
import 'package:timezone/timezone.dart' as tz;
import 'package:uuid/uuid.dart';

import 'api_service.dart';

class NotificationService {
  NotificationService._();

  static final NotificationService instance = NotificationService._();

  final FlutterLocalNotificationsPlugin _local =
      FlutterLocalNotificationsPlugin();
  final Uuid _uuid = const Uuid();

  Future<void> initialise() async {
    tz.initializeTimeZones();

    const android = AndroidInitializationSettings('@mipmap/ic_launcher');
    const ios = DarwinInitializationSettings();

    await _local.initialize(
      const InitializationSettings(android: android, iOS: ios),
    );

    try {
      await Firebase.initializeApp();
      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission();

      final token = await messaging.getToken();
      if (token != null && token.isNotEmpty) {
        await ApiService.instance.registerDeviceToken(
          deviceId: _uuid.v4(),
          token: token,
          platform: Platform.isIOS ? 'ios' : 'android',
        );
      }

      FirebaseMessaging.onMessage.listen((message) async {
        final notification = message.notification;
        if (notification == null) return;
        await _local.show(
          message.hashCode,
          notification.title ?? 'ElevateHer360',
          notification.body,
          const NotificationDetails(
            android: AndroidNotificationDetails(
              'elevateher360_updates',
              'ElevateHer360 Updates',
              channelDescription:
                  'Learning, mentorship, jobs and event updates',
              importance: Importance.high,
              priority: Priority.high,
            ),
          ),
        );
      });
    } catch (_) {
      // Firebase setup is optional until google-services configuration exists.
    }
  }

  Future<void> scheduleFromSync(List<dynamic> reminders) async {
    await _local.cancelAll();

    for (final raw in reminders) {
      if (raw is! Map) continue;
      final reminder = Map<String, dynamic>.from(raw);
      final scheduledRaw = reminder['scheduled_at']?.toString();
      if (scheduledRaw == null) continue;

      final date = DateTime.tryParse(scheduledRaw)?.toLocal();
      if (date == null || date.isBefore(DateTime.now())) continue;

      final id = '${reminder['type']}-${reminder['source_id']}'.hashCode;
      final notifyAt = date.subtract(const Duration(hours: 1));
      if (notifyAt.isBefore(DateTime.now())) continue;

      await _local.zonedSchedule(
        id,
        reminder['title']?.toString() ?? 'ElevateHer360 reminder',
        _messageForType(reminder['type']?.toString()),
        tz.TZDateTime.from(notifyAt, tz.getLocation('Africa/Kampala')),
        const NotificationDetails(
          android: AndroidNotificationDetails(
            'elevateher360_reminders',
            'Reminders',
            channelDescription:
                'Assignment deadlines, mentorship sessions and events',
            importance: Importance.high,
            priority: Priority.high,
          ),
        ),
        androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
        matchDateTimeComponents: null,
      );
    }
  }

  String _messageForType(String? type) {
    switch (type) {
      case 'assignment_deadline':
        return 'This assignment is due in about one hour.';
      case 'mentorship_session':
        return 'Your mentorship session starts in about one hour.';
      case 'event':
        return 'This event starts in about one hour.';
      default:
        return 'You have an upcoming ElevateHer360 activity.';
    }
  }
}
