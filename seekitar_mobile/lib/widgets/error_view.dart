import 'package:flutter/material.dart';

/// Tampilan error standar + tombol "Coba Lagi".
///
/// Dipakai tiap screen yang gagal memuat data, supaya kegagalan TIDAK
/// terlihat seperti "data kosong" dan pengguna selalu punya jalur retry.
class ErrorView extends StatelessWidget {
  final String message;
  final Future<void> Function()? onRetry;
  final bool retrying;

  const ErrorView({
    super.key,
    required this.message,
    this.onRetry,
    this.retrying = false,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, size: 48, color: Colors.grey),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.grey),
            ),
            if (onRetry != null) ...[
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: retrying ? null : onRetry,
                icon: const Icon(Icons.refresh),
                label: const Text('Coba Lagi'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
