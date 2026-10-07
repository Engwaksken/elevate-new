import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../core/network/app_exception.dart';
import '../core/profile_fields.dart';
import '../core/theme/app_theme.dart';
import '../services/participant_data_service.dart';
import '../services/biometric_auth_service.dart';
import '../services/sync_service.dart';
import '../widgets/decorations.dart';
import '../widgets/feedback.dart';
import '../widgets/progress_widgets.dart';
import '../widgets/state_views.dart';
import '../widgets/user_avatar.dart';
import 'change_password_screen.dart';
import 'edit_profile_screen.dart';
import 'goals_screen.dart';

/// The participant's own profile: photo, key details, completion and the
/// private (optional) section. Viewable offline from the cache; editing
/// needs a connection.
class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  bool _online = true;
  bool _photoBusy = false;
  bool _biometricEnabled = false;
  bool _biometricBusy = false;
  Object? _error;

  static const _maxPhotoBytes = 5 * 1024 * 1024;
  static const _photoExtensions = ['jpg', 'jpeg', 'png', 'webp'];

  @override
  void initState() {
    super.initState();
    ParticipantDataService.instance.profileVersion.addListener(_readCache);
    _loadBiometricPreference();
    _load();
  }

  @override
  void dispose() {
    ParticipantDataService.instance.profileVersion.removeListener(_readCache);
    super.dispose();
  }

  Map<String, dynamic> get _profile {
    final p = _data?['profile'];
    return p is Map ? Map<String, dynamic>.from(p) : const {};
  }

  List<ProfileField> get _fields =>
      parseEditableFields(_data?['editable_fields']);

  Future<void> _readCache() async {
    final cached = await ParticipantDataService.instance.cachedProfile();
    if (!mounted) return;
    setState(() {
      if (cached != null) _data = cached;
      _loading = false;
    });
  }

  Future<void> _loadBiometricPreference() async {
    final enabled = await BiometricAuthService.instance.isEnabled;
    if (mounted) setState(() => _biometricEnabled = enabled);
  }

  Future<void> _setBiometric(bool enabled) async {
    if (_biometricBusy) return;
    setState(() => _biometricBusy = true);
    try {
      if (enabled) {
        if (!await BiometricAuthService.instance.canEnable) {
          if (mounted) {
            showAppSnackBar(
              context,
              'Set up fingerprint or face unlock in your device settings first.',
              error: true,
            );
          }
          return;
        }
        final enabledNow = await BiometricAuthService.instance.enable();
        if (!enabledNow) {
          if (mounted) {
            showAppSnackBar(context, 'Biometric verification was cancelled.');
          }
          return;
        }
      } else {
        await BiometricAuthService.instance.disable();
      }
      if (mounted) {
        setState(() => _biometricEnabled = enabled);
        showAppSnackBar(
          context,
          enabled
              ? 'Biometric unlock is enabled on this device.'
              : 'Biometric unlock is turned off.',
        );
      }
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _biometricBusy = false);
    }
  }

  Future<void> _load() async {
    await _readCache();
    await _refresh(quiet: true);
  }

  Future<void> _refresh({bool quiet = false}) async {
    final online = await SyncService.instance.isOnline();
    if (mounted) setState(() => _online = online);
    if (!online) {
      if (_data == null && mounted) {
        setState(() {
          _error = AppException.offline;
          _loading = false;
        });
      } else if (!quiet && mounted) {
        showAppSnackBar(context,
            "You're offline. Showing the profile saved on this device.");
      }
      return;
    }
    try {
      await ParticipantDataService.instance.refreshProfile();
      if (mounted) setState(() => _error = null);
      await _readCache();
    } catch (error) {
      if (!mounted) return;
      if (_data == null) {
        setState(() {
          _error = error;
          _loading = false;
        });
      } else if (!quiet) {
        showErrorSnackBar(context, error, onRetry: _refresh);
      }
    }
  }

  /// Editing needs a connection: explain kindly when offline.
  Future<bool> _requireOnline() async {
    final online = await SyncService.instance.isOnline();
    if (mounted) setState(() => _online = online);
    if (!online && mounted) {
      showAppSnackBar(
        context,
        "You're offline. Connect to the internet to update your profile.",
      );
    }
    return online;
  }

  Future<void> _edit() async {
    if (!await _requireOnline() || !mounted) return;
    final saved = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => EditProfileScreen(profile: _profile, fields: _fields),
      ),
    );
    if (saved == true && mounted) {
      showAppSnackBar(context, 'Your profile has been updated. Looking good!');
      await _readCache();
    }
  }

  Future<void> _changePassword() async {
    if (!await _requireOnline() || !mounted) return;
    final changed = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => const ChangePasswordScreen()),
    );
    if (changed == true && mounted) {
      showAppSnackBar(
        context,
        'Password changed. For your safety, other devices have been signed out.',
      );
    }
  }

  Future<void> _changePhoto() async {
    if (!await _requireOnline()) return;
    final result = await FilePicker.platform.pickFiles(
      type: FileType.image,
      allowMultiple: false,
    );
    final file = result?.files.single;
    final path = file?.path;
    if (file == null || path == null || !mounted) return;

    final ext = (file.extension ?? path.split('.').last).toLowerCase();
    if (!_photoExtensions.contains(ext)) {
      showAppSnackBar(context, 'Please choose a JPG, PNG or WebP photo.',
          error: true);
      return;
    }
    final size = file.size > 0 ? file.size : await File(path).length();
    if (size > _maxPhotoBytes) {
      if (!mounted) return;
      showAppSnackBar(context,
          'That photo is larger than 5 MB. Please choose a smaller one.',
          error: true);
      return;
    }

    setState(() => _photoBusy = true);
    try {
      await ParticipantDataService.instance.uploadPhoto(path);
      if (mounted) showAppSnackBar(context, 'Lovely! Your new photo is saved.');
    } catch (error) {
      if (!mounted) return;
      final mapped = AppException.from(error);
      showAppSnackBar(
        context,
        mapped.fieldError('photo') ?? mapped.message,
        error: true,
      );
    } finally {
      if (mounted) setState(() => _photoBusy = false);
    }
  }

  Future<void> _removePhoto() async {
    if (!await _requireOnline() || !mounted) return;
    final ok = await confirmDialog(
      context,
      title: 'Remove your photo?',
      message:
          'Your initials will be shown instead. You can add a new photo any time.',
      confirmLabel: 'Remove',
      destructive: true,
    );
    if (!ok) return;
    setState(() => _photoBusy = true);
    try {
      await ParticipantDataService.instance.removePhoto();
      if (mounted) showAppSnackBar(context, 'Your photo has been removed.');
    } catch (error) {
      if (mounted) showErrorSnackBar(context, error);
    } finally {
      if (mounted) setState(() => _photoBusy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My profile'),
        actions: [
          if (_data != null)
            IconButton(
              tooltip: 'Edit profile',
              onPressed: _edit,
              icon: const Icon(Icons.edit_outlined),
            ),
        ],
      ),
      body: SafeArea(top: false, child: _body(context)),
    );
  }

  Widget _body(BuildContext context) {
    if (_loading && _data == null) return const LoadingSkeleton(itemHeight: 90);
    if (_data == null) {
      return ErrorState(
        error: _error ?? AppException.offline,
        title: "Your profile isn't on this device yet",
        onRetry: () {
          setState(() => _loading = true);
          _refresh();
        },
      );
    }

    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final brand = BrandColors.of(context);
    final profile = _profile;
    final fields = _fields;
    final percent = profileCompletionPercent(profile, fields);
    final missing = missingFields(profile, fields);
    final hasPhoto = profile['has_photo'] == true ||
        (profile['photo_url']?.toString().isNotEmpty ?? false);
    final name = profile['name']?.toString();
    final branch =
        profile['branch'] is Map ? profile['branch']['name']?.toString() : null;

    final general =
        fields.where((f) => !f.sensitive && f.name != 'name').toList();
    final private = fields.where((f) => f.sensitive).toList();

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView(
        padding: AppSpacing.listPadding,
        children: [
          const SizedBox(height: AppSpacing.sm),
          if (!_online) ...[
            const NoticeCard(
              icon: Icons.cloud_off_outlined,
              tone: PillTone.warning,
              message: "You're offline. You can view your profile, and edit it "
                  "once you're back online.",
            ),
            const SizedBox(height: AppSpacing.md),
          ],
          GradientHeader(
            seed: 3,
            child: Column(
              children: [
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    UserAvatar(name: name, radius: 48, ring: true),
                    if (_photoBusy)
                      const Positioned.fill(
                        child: Center(child: CircularProgressIndicator()),
                      ),
                  ],
                ),
                const SizedBox(height: AppSpacing.md),
                Text(
                  (name ?? '').isEmpty ? 'Your profile' : name!,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.titleLarge
                      ?.copyWith(color: brand.onHeader),
                ),
                if ((profile['email']?.toString() ?? '').isNotEmpty)
                  Text(
                    profile['email'].toString(),
                    textAlign: TextAlign.center,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: brand.onHeader.withValues(alpha: 0.92),
                    ),
                  ),
                if (branch != null && branch.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: AppSpacing.xs),
                    child: Text(
                      '$branch branch',
                      style: theme.textTheme.bodySmall
                          ?.copyWith(color: brand.onHeader),
                    ),
                  ),
                const SizedBox(height: AppSpacing.md),
                Wrap(
                  alignment: WrapAlignment.center,
                  spacing: AppSpacing.sm,
                  runSpacing: AppSpacing.sm,
                  children: [
                    FilledButton.tonalIcon(
                      onPressed: _photoBusy ? null : _changePhoto,
                      icon: const Icon(Icons.photo_camera_outlined),
                      label: Text(hasPhoto ? 'Change photo' : 'Add a photo'),
                    ),
                    if (hasPhoto)
                      TextButton.icon(
                        style: TextButton.styleFrom(
                            foregroundColor: brand.onHeader),
                        onPressed: _photoBusy ? null : _removePhoto,
                        icon: const Icon(Icons.delete_outline),
                        label: const Text('Remove photo'),
                      ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Semantics(
                container: true,
                label: 'Profile $percent percent complete',
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        ExcludeSemantics(
                          child: ProgressRing(
                            value: percent / 100,
                            size: 56,
                            strokeWidth: 7,
                            center: Text('$percent%',
                                style: theme.textTheme.labelLarge),
                          ),
                        ),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                percent >= 100
                                    ? 'Your profile is complete!'
                                    : 'Profile $percent% complete',
                                style: theme.textTheme.titleMedium,
                              ),
                              Text(
                                percent >= 100
                                    ? 'Thank you. It helps us match you with the right opportunities.'
                                    : 'A full profile helps us match you with mentors and opportunities.',
                                style: theme.textTheme.bodyMedium
                                    ?.copyWith(color: scheme.onSurfaceVariant),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    if (missing.isNotEmpty) ...[
                      const SizedBox(height: AppSpacing.md),
                      Wrap(
                        spacing: AppSpacing.sm,
                        runSpacing: AppSpacing.sm,
                        children: [
                          for (final f in missing.take(6))
                            ActionChip(
                              avatar: const Icon(Icons.add, size: 18),
                              label: Text(f.label),
                              onPressed: _edit,
                            ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
          const SectionHeader(title: 'About you'),
          Card(
            child: Column(
              children: [
                for (var i = 0; i < general.length; i++) ...[
                  if (i > 0)
                    const Divider(
                        indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                  _DetailTile(field: general[i], profile: profile),
                ],
                if (general.isEmpty)
                  const ListTile(title: Text('No details yet')),
              ],
            ),
          ),
          if (private.isNotEmpty) ...[
            const SectionHeader(title: 'Private and optional'),
            Card(
              child: Column(
                children: [
                  ListTile(
                    leading: Icon(Icons.lock_outline, color: scheme.secondary),
                    title: const Text(
                        'Only you and the programme team can see this'),
                    subtitle: const Text(
                      'Sharing it is entirely your choice. It helps us offer the right support.',
                    ),
                  ),
                  for (final f in private)
                    if (f.name != 'disability_types' &&
                            f.name != 'disability_other' ||
                        profile['is_pwd'] == true) ...[
                      const Divider(
                          indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                      _DetailTile(
                        field: f,
                        profile: profile,
                        emptyText: 'Prefer not to say',
                      ),
                    ],
                ],
              ),
            ),
          ],
          const SectionHeader(title: 'Account'),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.edit_outlined),
                  title: const Text('Edit profile'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: _edit,
                ),
                const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                ListTile(
                  leading: const Icon(Icons.flag_outlined),
                  title: const Text('My goals'),
                  subtitle: const Text('Set goals and track your progress'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const GoalsScreen()),
                  ),
                ),
                const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                ListTile(
                  leading: const Icon(Icons.password_rounded),
                  title: const Text('Change password'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: _changePassword,
                ),
                const Divider(indent: AppSpacing.lg, endIndent: AppSpacing.lg),
                SwitchListTile(
                  secondary: const Icon(Icons.fingerprint),
                  title: const Text('Biometric unlock'),
                  subtitle: const Text(
                    'Use fingerprint or face recognition to unlock this device.',
                  ),
                  value: _biometricEnabled,
                  onChanged: _biometricBusy ? null : _setBiometric,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailTile extends StatelessWidget {
  const _DetailTile({
    required this.field,
    required this.profile,
    this.emptyText = 'Not added yet',
  });

  final ProfileField field;
  final Map<String, dynamic> profile;
  final String emptyText;

  static IconData _icon(ProfileField f) {
    final n = f.name;
    if (n.contains('phone')) return Icons.phone_outlined;
    if (n.contains('gender')) return Icons.face_3_outlined;
    if (n.contains('birth')) return Icons.cake_outlined;
    if (n == 'country' || n == 'district' || n == 'location')
      return Icons.place_outlined;
    if (n.contains('education')) return Icons.school_outlined;
    if (n.contains('employment')) return Icons.work_outline_rounded;
    if (n.contains('interest')) return Icons.favorite_outline;
    if (n.contains('language')) return Icons.translate_rounded;
    if (f.sensitive) return Icons.accessibility_new_rounded;
    return Icons.badge_outlined;
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final value = displayProfileValue(field, profile);
    final shown =
        field.type == ProfileFieldType.boolean && profile[field.name] == null
            ? ''
            : value;
    return ListTile(
      leading: Icon(_icon(field)),
      title: Text(field.label),
      subtitle: Text(
        shown.isEmpty ? emptyText : shown,
        style: shown.isEmpty
            ? TextStyle(
                color: scheme.onSurfaceVariant, fontStyle: FontStyle.italic)
            : null,
      ),
    );
  }
}
