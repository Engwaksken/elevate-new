import 'dart:async';
import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:timezone/data/latest.dart' as tz;
import 'package:timezone/timezone.dart' as tz;
import 'package:uuid/uuid.dart';

import 'api_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {
    return;
  }
}

class NotificationService {
  NotificationService._();

  static final NotificationService instance = NotificationService._();

  final FlutterLocalNotificationsPlugin _local =
      FlutterLocalNotificationsPlugin();

  final Uuid _uuid = const Uuid();
  final StreamController<String> _destinationController =
      StreamController<String>.broadcast();

  bool _firebaseReady = false;
  String? _pendingDestination;

  Stream<String> get destinationStream => _destinationController.stream;

  Future<void> initialise() async {
    tz.initializeTimeZones();

    const android = AndroidInitializationSettings('@mipmap/ic_launcher');
    const ios = DarwinInitializationSettings();

    await _local.initialize(
      const InitializationSettings(android: android, iOS: ios),
      onDidReceiveNotificationResponse: (response) {
        _dispatchDestination(response.payload);
      },
    );

    final launchDetails = await _local.getNotificationAppLaunchDetails();
    if (launchDetails?.didNotificationLaunchApp == true) {
      _pendingDestination = launchDetails?.notificationResponse?.payload;
    }

    try {
      await Firebase.initializeApp();
      _firebaseReady = true;

      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission();

      FirebaseMessaging.onMessage.listen(_showForegroundMessage);

      FirebaseMessaging.onMessageOpenedApp.listen((message) {
        _dispatchDestination(_destinationFromMessage(message));
      });

      final initialMessage = await messaging.getInitialMessage();
      if (initialMessage != null) {
        _pendingDestination = _destinationFromMessage(initialMessage);
      }

      messaging.onTokenRefresh.listen((token) async {
        await _registerToken(token);
      });
    } catch (_) {
      _firebaseReady = false;
    }
  }

  Future<void> registerCurrentDevice() async {
    if (!_firebaseReady) return;

    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null || token.isEmpty) return;
      await _registerToken(token);
    } catch (_) {}
  }

  String? consumePendingDestination() {
    final value = _pendingDestination;
    _pendingDestination = null;
    return value;
  }

  Future<String> persistentDeviceId() async {
    final prefs = await SharedPreferences.getInstance();
    final existing = prefs.getString('persistent_device_id');

    if (existing != null && existing.isNotEmpty) {
      return existing;
    }

    final created = _uuid.v4();
    await prefs.setString('persistent_device_id', created);
    return created;
  }

  Future<void> _registerToken(String token) async {
    final deviceId = await persistentDeviceId();

    try {
      await ApiService.instance.registerDeviceToken(
        deviceId: deviceId,
        token: token,
        platform: Platform.isIOS ? 'ios' : 'android',
      );
    } catch (_) {
      // Registration can fail before participant login. It is retried after
      // successful login and on the next token refresh.
    }
  }

  Future<void> _showForegroundMessage(RemoteMessage message) async {
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
          channelDescription: 'Learning, mentorship, jobs and event updates',
          importance: Importance.high,
          priority: Priority.high,
        ),
      ),
      payload: _destinationFromMessage(message),
    );
  }

  String _destinationFromMessage(RemoteMessage message) {
    return message.data['type']?.toString() ??
        message.data['destination']?.toString() ??
        'notifications';
  }

  void _dispatchDestination(String? destination) {
    if (destination == null || destination.isEmpty) return;
    _destinationController.add(destination);
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
        tz.TZDateTime.from(
          notifyAt,
          tz.getLocation('Africa/Kampala'),
        ),
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
        payload: reminder['type']?.toString(),
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
