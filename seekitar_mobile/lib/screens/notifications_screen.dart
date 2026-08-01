import 'package:flutter/material.dart';
import '../models/notification.dart';
import '../services/api_compat.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});
  @override State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final _api = ApiProvider();
  List<AppNotification> _items = [];
  bool _loading = true;
  bool _markingAll = false;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final n = await _api.getNotifications(); if (mounted) setState(() { _items = n; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _markAll() async {
    setState(() => _markingAll = true);
    await _api.markAllRead();
    await _load();
    if (mounted) setState(() => _markingAll = false);
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Notifikasi'), actions: [TextButton(onPressed: _markingAll ? null : _markAll, child: Text(_markingAll ? '...' : 'Baca Semua'))]),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _items.isEmpty
      ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.notifications_none, size: 64, color: Colors.grey.shade300), const SizedBox(height: 16), const Text('Tidak ada notifikasi', style: TextStyle(color: Colors.grey))]))
      : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
        final n = _items[i];
        return ListTile(
          leading: CircleAvatar(backgroundColor: n.isRead ? Colors.grey.shade100 : Colors.green.shade50, child: Icon(n.isRead ? Icons.notifications_none : Icons.notifications_active, color: n.isRead ? Colors.grey : Colors.green)),
          title: Text(n.title, style: TextStyle(fontWeight: n.isRead ? FontWeight.normal : FontWeight.bold, fontSize: 14)),
          subtitle: Text(n.body, maxLines: 2, style: const TextStyle(fontSize: 13)),
          trailing: n.isRead ? null : Container(width: 10, height: 10, decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle)),
          onTap: () async { await _api.markRead(n.id); _load(); },
        );
      })),
  );
}
