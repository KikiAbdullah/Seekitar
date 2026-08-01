import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';

class OfflineBanner extends StatefulWidget {
  final Widget child;
  const OfflineBanner({super.key, required this.child});
  @override State<OfflineBanner> createState() => _OfflineBannerState();
}

class _OfflineBannerState extends State<OfflineBanner> {
  bool _online = true; late StreamSubscription _sub;

  @override void initState() {
    super.initState();
    _sub = Connectivity().onConnectivityChanged.listen((r) => setState(() => _online = !r.contains(ConnectivityResult.none)));
  }

  @override void dispose() { _sub.cancel(); super.dispose(); }

  @override Widget build(BuildContext ctx) => Column(children: [
    if (!_online) MaterialBanner(content: const Text('Tidak ada koneksi internet', style: TextStyle(color: Colors.white)), backgroundColor: Colors.red.shade600, actions: [TextButton(onPressed: () {}, child: const Text('COBA LAGI', style: TextStyle(color: Colors.white)))], padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6)),
    Expanded(child: widget.child),
  ]);
}
