import 'dart:async';
import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:timezone/data/latest.dart' as tz;
import 'package:timezone/timezone.dart' as tz;
import 'package:uuid/uuid.dart';

import '../core/logger.dart';
import '../core/timetable_reminder_info.dart';
import 'api_service.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(
  RemoteMessage message,
) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // Firebase may already have been initialised,
    // or configuration may not be available yet.
  }
}

class NotificationService {
  NotificationService._();

  static final NotificationService instance = NotificationService._();

  static const String _updatesChannelId = 'elevateher360_updates';

  static const String _remindersChannelId = 'elevateher360_reminders';

  final FlutterLocalNotificationsPlugin _local =
      FlutterLocalNotificationsPlugin();

  final Uuid _uuid = const Uuid();

  final StreamController<String> _destinationController =
      StreamController<String>.broadcast();

  bool _firebaseReady = false;
  bool _initialised = false;

  String? _pendingDestination;

  Stream<String> get destinationStream => _destinationController.stream;

  bool get isFirebaseReady => _firebaseReady;

  Future<void> initialise() async {
    if (_initialised) {
      return;
    }

    _initialised = true;

    tz.initializeTimeZones();

    try {
      tz.setLocalLocation(
        tz.getLocation('Africa/Kampala'),
      );
    } catch (_) {
      // Falls back to the package's default location.
    }

    const androidSettings = AndroidInitializationSettings(
      '@mipmap/ic_launcher',
    );

    // Don't prompt for notification permission at launch; it is requested
    // after sign-in (see [requestPermission]), when the user knows why.
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );

    await _local.initialize(
      const InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      ),
      onDidReceiveNotificationResponse: (response) {
        _dispatchDestination(
          response.payload,
        );
      },
    );

    await _createAndroidChannels();

    final launchDetails = await _local.getNotificationAppLaunchDetails();

    if (launchDetails?.didNotificationLaunchApp == true) {
      _pendingDestination = launchDetails?.notificationResponse?.payload;
    }

    try {
      await Firebase.initializeApp();

      _firebaseReady = true;

      FirebaseMessaging.onBackgroundMessage(
        firebaseMessagingBackgroundHandler,
      );

      final messaging = FirebaseMessaging.instance;

      FirebaseMessaging.onMessage.listen(
        _showForegroundMessage,
      );

      FirebaseMessaging.onMessageOpenedApp.listen(
        (message) {
          _dispatchDestination(
            _destinationFromMessage(message),
          );
        },
      );

      final initialMessage = await messaging.getInitialMessage();

      if (initialMessage != null) {
        _pendingDestination = _destinationFromMessage(
          initialMessage,
        );
      }

      messaging.onTokenRefresh.listen(
        (token) async {
          await _registerToken(token);
        },
      );
    } catch (error, stackTrace) {
      // Typically Firebase isn't configured for this build (e.g. an iOS
      // build without GoogleService-Info.plist). The app keeps working;
      // only push notifications are unavailable. Local reminders still work.
      _firebaseReady = false;
      appLog(
        'Firebase unavailable; continuing without push notifications',
        error,
        stackTrace,
      );
    }
  }

  bool _permissionRequested = false;

  /// Asks for notification permission once per app session. Called after
  /// sign-in rather than at launch. The OS only shows its prompt the first
  /// time; later calls just return the stored choice.
  Future<void> requestPermission() async {
    if (_permissionRequested) {
      return;
    }

    _permissionRequested = true;

    try {
      if (_firebaseReady) {
        // Covers local notifications too: on iOS both use the same
        // UNUserNotificationCenter authorisation, and on Android 13+ this
        // requests POST_NOTIFICATIONS.
        await FirebaseMessaging.instance.requestPermission(
          alert: true,
          badge: true,
          sound: true,
        );
        return;
      }

      await _local
          .resolvePlatformSpecificImplementation<
              IOSFlutterLocalNotificationsPlugin>()
          ?.requestPermissions(alert: true, badge: true, sound: true);

      await _local
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.requestNotificationsPermission();
    } catch (error) {
      appLog('Notification permission request failed', error);
    }
  }

  Future<void> _createAndroidChannels() async {
    final androidImplementation = _local.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();

    if (androidImplementation == null) {
      return;
    }

    const updatesChannel = AndroidNotificationChannel(
      _updatesChannelId,
      'ElevateHer360 Updates',
      description: 'Learning, mentorship, jobs and event updates',
      importance: Importance.high,
    );

    const remindersChannel = AndroidNotificationChannel(
      _remindersChannelId,
      'ElevateHer360 Reminders',
      description:
          'Assignment deadlines, mentorship sessions, events and programme reminders',
      importance: Importance.high,
    );

    await androidImplementation.createNotificationChannel(
      updatesChannel,
    );

    await androidImplementation.createNotificationChannel(
      remindersChannel,
    );
  }

  Future<void> registerCurrentDevice() async {
    await requestPermission();

    if (!_firebaseReady) {
      return;
    }

    try {
      final token = await FirebaseMessaging.instance.getToken();

      if (token == null || token.trim().isEmpty) {
        return;
      }

      await _registerToken(token);
    } catch (_) {
      // Registration can retry later.
    }
  }

  Future<void> unregisterDeviceToken() async {
    if (!_firebaseReady) {
      return;
    }

    try {
      final deviceId = await persistentDeviceId();

      await ApiService.instance.unregisterDeviceToken(
        deviceId: deviceId,
      );
    } catch (_) {
      // Local logout should still continue even if
      // the device cannot reach the API.
    }
  }

  Future<String> persistentDeviceId() async {
    final prefs = await SharedPreferences.getInstance();

    final existing = prefs.getString(
      'persistent_device_id',
    );

    if (existing != null && existing.trim().isNotEmpty) {
      return existing;
    }

    final created = _uuid.v4();

    await prefs.setString(
      'persistent_device_id',
      created,
    );

    return created;
  }

  Future<void> _registerToken(
    String token,
  ) async {
    final deviceId = await persistentDeviceId();

    try {
      await ApiService.instance.registerDeviceToken(
        deviceId: deviceId,
        token: token,
        platform: Platform.isIOS ? 'ios' : 'android',
      );
    } catch (_) {
      // Registration may run before authentication.
      // Retry after login or token refresh.
    }
  }

  Future<void> _showForegroundMessage(
    RemoteMessage message,
  ) async {
    final notification = message.notification;

    if (notification == null) {
      return;
    }

    await _local.show(
      _notificationIdForRemoteMessage(
        message,
      ),
      notification.title ?? 'ElevateHer360',
      notification.body,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          _updatesChannelId,
          'ElevateHer360 Updates',
          channelDescription: 'Learning, mentorship, jobs and event updates',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
      payload: _destinationFromMessage(
        message,
      ),
    );
  }

  int _notificationIdForRemoteMessage(
    RemoteMessage message,
  ) {
    final messageId = message.messageId;

    if (messageId != null && messageId.isNotEmpty) {
      return messageId.hashCode & 0x7fffffff;
    }

    return message.hashCode & 0x7fffffff;
  }

  String _destinationFromMessage(
    RemoteMessage message,
  ) {
    return message.data['destination']?.toString() ??
        message.data['type']?.toString() ??
        'notifications';
  }

  void _dispatchDestination(
    String? destination,
  ) {
    if (destination == null || destination.trim().isEmpty) {
      return;
    }

    _destinationController.add(
      destination.trim(),
    );
  }

  String? consumePendingDestination() {
    final value = _pendingDestination;

    _pendingDestination = null;

    return value;
  }

  Future<void> scheduleFromSync(
    List<dynamic> reminders,
  ) async {
    for (final raw in reminders) {
      if (raw is! Map) {
        continue;
      }

      final reminder = Map<String, dynamic>.from(raw);

      await _scheduleReminder(
        reminder,
      );
    }
  }

  Future<void> _scheduleReminder(
    Map<String, dynamic> reminder,
  ) async {
    final scheduledRaw = reminder['scheduled_at']?.toString();

    if (scheduledRaw == null || scheduledRaw.isEmpty) {
      return;
    }

    final scheduled = DateTime.tryParse(
      scheduledRaw,
    )?.toLocal();

    if (scheduled == null) {
      return;
    }

    final type = reminder['type']?.toString() ?? 'activity';

    final sourceId = reminder['source_id']?.toString() ?? scheduledRaw;

    final id = type == 'timetable_lesson'
        ? TimetableReminderInfo(reminder).notificationId
        : '$type-$sourceId'.hashCode & 0x7fffffff;

    if (scheduled.isBefore(
      DateTime.now(),
    )) {
      await _local.cancel(id);
      return;
    }

    final notifyAt = _notificationTimeFor(
      type,
      scheduled,
    );

    if (notifyAt.isBefore(
      DateTime.now(),
    )) {
      if (type == 'timetable_lesson') await _local.cancel(id);
      return;
    }

    await _local.zonedSchedule(
      id,
      reminder['title']?.toString() ?? 'ElevateHer360 reminder',
      reminder['message']?.toString() ?? _messageForType(type),
      tz.TZDateTime.from(
        notifyAt,
        tz.local,
      ),
      const NotificationDetails(
        android: AndroidNotificationDetails(
          _remindersChannelId,
          'ElevateHer360 Reminders',
          channelDescription:
              'Assignment deadlines, mentorship sessions, events and programme activities',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
      androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
      payload: reminder['destination']?.toString() ?? type,
    );
  }

  DateTime _notificationTimeFor(
    String type,
    DateTime scheduledAt,
  ) {
    switch (type) {
      case 'timetable_lesson':
        return scheduledAt.subtract(const Duration(minutes: 10));
      case 'assignment_deadline':
        return scheduledAt.subtract(
          const Duration(hours: 1),
        );

      case 'mentorship_session':
        return scheduledAt.subtract(
          const Duration(hours: 1),
        );

      case 'event':
        return scheduledAt.subtract(
          const Duration(hours: 1),
        );

      case 'course_deadline':
        return scheduledAt.subtract(
          const Duration(hours: 1),
        );

      case 'programme_activity':
        return scheduledAt.subtract(
          const Duration(hours: 1),
        );

      default:
        return scheduledAt.subtract(
          const Duration(hours: 1),
        );
    }
  }

  String _messageForType(
    String type,
  ) {
    switch (type) {
      case 'assignment_deadline':
        return 'This assignment is due in about one hour.';

      case 'mentorship_session':
        return 'Your mentorship session starts in about one hour.';

      case 'event':
        return 'This event starts in about one hour.';

      case 'course_deadline':
        return 'This course activity is due in about one hour.';

      case 'programme_activity':
        return 'You have an upcoming programme activity in about one hour.';

      default:
        return 'You have an upcoming ElevateHer360 activity.';
    }
  }

  Future<void> scheduleTimetableReminders(List<dynamic> reminders) async {
    final prefs = await SharedPreferences.getInstance();
    final previous = prefs.getStringList('timetable_reminder_ids') ?? [];
    final current = <String>[];
    for (final raw in reminders) {
      if (raw is! Map) continue;
      final data = Map<String, dynamic>.from(raw);
      final info = TimetableReminderInfo(data);
      if (info.sourceId.isEmpty || info.startsAt == null) continue;
      current.add(info.notificationId.toString());
      await _scheduleReminder(data);
    }
    for (final id in previous.where((id) => !current.contains(id))) {
      await _local.cancel(int.parse(id));
    }
    await prefs.setStringList('timetable_reminder_ids', current);
  }

  Future<void> cancelTimetableReminders() async {
    final prefs = await SharedPreferences.getInstance();
    for (final id in prefs.getStringList('timetable_reminder_ids') ?? []) {
      await _local.cancel(int.parse(id));
    }
    await prefs.remove('timetable_reminder_ids');
  }

  /// Schedules a local reminder about an hour before a mentorship session.
  /// Returns false when that moment has already passed.
  Future<bool> scheduleSessionReminder({
    required Object sessionId,
    required String title,
    required DateTime scheduledAt,
  }) async {
    final notifyAt =
        _notificationTimeFor('mentorship_session', scheduledAt.toLocal());
    if (!notifyAt.isAfter(DateTime.now())) return false;

    await requestPermission();
    await _scheduleReminder({
      'type': 'mentorship_session',
      'source_id': sessionId,
      'title': title,
      'message': 'Your mentorship session starts in about one hour.',
      'scheduled_at': scheduledAt.toUtc().toIso8601String(),
      'destination': 'mentorship',
    });
    return true;
  }

  Future<void> cancelReminder({
    required String type,
    required Object sourceId,
  }) async {
    final id = type == 'timetable_lesson'
        ? TimetableReminderInfo({'source_id': sourceId}).notificationId
        : '$type-$sourceId'.hashCode & 0x7fffffff;

    await _local.cancel(id);
  }

  void dispose() {
    _destinationController.close();
  }
}
