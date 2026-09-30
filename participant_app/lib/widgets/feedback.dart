import 'package:flutter/material.dart';

import '../core/network/app_exception.dart';

/// Compact floating SnackBar. Replaces any SnackBar already showing so
/// messages never stack up.
void showAppSnackBar(
  BuildContext context,
  String message, {
  bool error = false,
  String? actionLabel,
  VoidCallback? onAction,
}) {
  final messenger = ScaffoldMessenger.maybeOf(context);
  if (messenger == null) return;

  final scheme = Theme.of(context).colorScheme;

  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        content: Text(
          message,
          maxLines: 3,
          overflow: TextOverflow.ellipsis,
          style: error ? TextStyle(color: scheme.onErrorContainer) : null,
        ),
        backgroundColor: error ? scheme.errorContainer : null,
        closeIconColor: error ? scheme.onErrorContainer : null,
        duration: Duration(seconds: error ? 5 : 3),
        action: actionLabel != null && onAction != null
            ? SnackBarAction(
                label: actionLabel,
                onPressed: onAction,
                textColor: error ? scheme.onErrorContainer : null,
              )
            : null,
      ),
    );
}

/// Shows the friendly message for [error] (never the raw exception text).
void showErrorSnackBar(
  BuildContext context,
  Object error, {
  VoidCallback? onRetry,
}) {
  final mapped = AppException.from(error);

  // A 401 is handled globally (the user is returned to sign-in).
  if (mapped.kind == AppErrorKind.unauthorised) return;

  showAppSnackBar(
    context,
    mapped.message,
    error: true,
    actionLabel: onRetry != null && mapped.isRetryable ? 'Retry' : null,
    onAction: onRetry,
  );
}

Future<bool> confirmDialog(
  BuildContext context, {
  required String title,
  required String message,
  required String confirmLabel,
  bool destructive = false,
}) async {
  final result = await showDialog<bool>(
    context: context,
    builder: (dialogContext) {
      final scheme = Theme.of(dialogContext).colorScheme;

      return AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            style: destructive
                ? FilledButton.styleFrom(
                    backgroundColor: scheme.error,
                    foregroundColor: scheme.onError,
                  )
                : null,
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(confirmLabel),
          ),
        ],
      );
    },
  );

  return result ?? false;
}
