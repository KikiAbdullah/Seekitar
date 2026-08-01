import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';

class OfflineBanner extends StatefulWidget {
  final Widget child;
  final VoidCallback? onRetry;
  const OfflineBanner({super.key, required this.child, this.onRetry});

  @override State<OfflineBanner> createState() => _OfflineBannerState();
}

class _OfflineBannerState extends State<OfflineBanner> {
  bool _online = true;
  late StreamSubscription<List<ConnectivityResult>> _sub;
  bool _retrying = false;

  @override void initState() {
    super.initState();
    _check();
    _sub = Connectivity().onConnectivityChanged.listen((r) {
      final now = !r.contains(ConnectivityResult.none);
      if (now != _online) setState(() { _online = now; _retrying = false; });
    });
  }

  Future<void> _check() async {
    final r = await Connectivity().checkConnectivity();
    setState(() => _online = !r.contains(ConnectivityResult.none));
  }

  void _retry() async {
    setState(() => _retrying = true);
    widget.onRetry?.call();
    await _check();
    if (mounted) setState(() => _retrying = false);
  }

  @override void dispose() { _sub.cancel(); super.dispose(); }

  @override Widget build(BuildContext ctx) => Column(children: [
    if (!_online) MaterialBanner(
      content: const Text('Tidak ada koneksi internet', style: TextStyle(color: Colors.white, fontSize: 13)),
      backgroundColor: Colors.red.shade600,
      actions: [TextButton(onPressed: _retrying ? null : _retry, child: Text(_retrying ? 'Memeriksa...' : 'Coba Lagi', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)))],
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
    ),
    Expanded(child: widget.child),
  ]);
}
