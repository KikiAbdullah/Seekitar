import 'package:flutter/material.dart';
import '../models/notification.dart';
import '../services/api_compat.dart';

class NotifPrefsScreen extends StatefulWidget {
  const NotifPrefsScreen({super.key});
  @override State<NotifPrefsScreen> createState() => _NotifPrefsScreenState();
}

class _NotifPrefsScreenState extends State<NotifPrefsScreen> {
  final _api = ApiProvider();
  List<NotificationPreference> _channels = [];
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final c = await _api.getNotifPreferences(); if (mounted) setState(() { _channels = c; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _save() async {
    await _api.updateNotifPreferences(_channels.map((c) => {'key': c.key, 'enabled': c.enabled}).toList());
    if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Preferensi disimpan')));
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Notifikasi'), actions: [TextButton(onPressed: _save, child: const Text('Simpan'))]),
    body: _loading ? const Center(child: CircularProgressIndicator()) : ListView(children: _channels.map((c) => SwitchListTile(title: Text(c.label), value: c.enabled, onChanged: (v) => setState(() => c.enabled = v))).toList()),
  );
}
