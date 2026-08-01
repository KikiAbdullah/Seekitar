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
  bool _saving = false;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final c = await _api.getNotifPreferences(); if (mounted) setState(() { _channels = c; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      await _api.updateNotifPreferences(_channels.map((c) => {'key': c.key, 'enabled': c.enabled}).toList());
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Preferensi disimpan')));
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    if (mounted) setState(() => _saving = false);
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Notifikasi'), actions: [TextButton(onPressed: _saving ? null : _save, child: Text(_saving ? 'Menyimpan...' : 'Simpan'))]),
    body: _loading ? const Center(child: CircularProgressIndicator())
      : RefreshIndicator(onRefresh: _load, child: ListView(children: _channels.map((c) => SwitchListTile(
        title: Text(c.label, style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 15)),
        subtitle: Text(c.enabled ? 'Aktif' : 'Nonaktif', style: TextStyle(fontSize: 12, color: c.enabled ? Colors.green : Colors.grey)),
        value: c.enabled, onChanged: (v) => setState(() => c.enabled = v),
        activeColor: Theme.of(ctx).colorScheme.primary,
      )).toList())),
  );
}
