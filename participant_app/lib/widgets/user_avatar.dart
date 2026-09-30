import 'dart:io';

import 'package:flutter/material.dart';

import '../core/formatters.dart';
import '../core/theme/app_theme.dart';
import '../services/participant_data_service.dart';

/// The participant's cached profile photo, or her initials on a warm
/// gradient. Updates when the profile or photo changes.
class UserAvatar extends StatefulWidget {
  const UserAvatar({
    super.key,
    required this.name,
    this.radius = 28,
    this.ring = false,
  });

  final String? name;
  final double radius;

  /// Adds a light ring, for use on the brand gradient.
  final bool ring;

  @override
  State<UserAvatar> createState() => _UserAvatarState();
}

class _UserAvatarState extends State<UserAvatar> {
  File? _photo;

  @override
  void initState() {
    super.initState();
    ParticipantDataService.instance.profileVersion.addListener(_load);
    _load();
  }

  @override
  void dispose() {
    ParticipantDataService.instance.profileVersion.removeListener(_load);
    super.dispose();
  }

  Future<void> _load() async {
    File? file;
    try {
      file = await ParticipantDataService.instance.photoFile();
    } catch (_) {
      file = null;
    }
    if (mounted) setState(() => _photo = file);
  }

  @override
  Widget build(BuildContext context) {
    final brand = BrandColors.of(context);
    final size = widget.radius * 2;
    final initials = initialsOf(widget.name);

    final Widget inner = _photo != null
        ? Image.file(
            _photo!,
            width: size,
            height: size,
            fit: BoxFit.cover,
            gaplessPlayback: true,
            errorBuilder: (_, __, ___) => _Initials(initials: initials, size: size),
          )
        : _Initials(initials: initials, size: size);

    return Semantics(
      label: _photo != null
          ? 'Profile photo of ${widget.name ?? 'you'}'
          : 'Profile initials $initials',
      image: true,
      excludeSemantics: true,
      child: Container(
        padding: widget.ring ? const EdgeInsets.all(3) : EdgeInsets.zero,
        decoration: widget.ring
            ? BoxDecoration(
                shape: BoxShape.circle,
                color: brand.onHeader.withValues(alpha: 0.85),
              )
            : null,
        child: ClipOval(child: SizedBox.square(dimension: size, child: inner)),
      ),
    );
  }
}

class _Initials extends StatelessWidget {
  const _Initials({required this.initials, required this.size});

  final String initials;
  final double size;

  @override
  Widget build(BuildContext context) {
    // Deep brown text on peach/blush: well above 7:1.
    return DecoratedBox(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.peach, AppColors.blush],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          initials,
          textScaler: TextScaler.noScaling,
          style: TextStyle(
            color: AppColors.brown,
            fontWeight: FontWeight.w700,
            fontSize: size * 0.36,
          ),
        ),
      ),
    );
  }
}
