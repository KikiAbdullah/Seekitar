import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../services/api_compat.dart';

class BlockedUsersScreen extends StatefulWidget {
  const BlockedUsersScreen({super.key});
  @override State<BlockedUsersScreen> createState() => _BlockedUsersScreenState();
}

class _BlockedUsersScreenState extends State<BlockedUsersScreen> {
  final _api = ApiProvider();
  List<Map<String,dynamic>> _items = [];
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final b = await _api.getBlockedUsers(); if (mounted) setState(() { _items = b; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Pengguna Diblokir')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _items.isEmpty ? const Center(child: Text('Tidak ada pengguna diblokir')) : ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
      final u = _items[i];
      return ListTile(
        leading: CircleAvatar(child: Text((u['name'] as String? ?? 'U').substring(0, 1).toUpperCase())),
        title: Text(u['name'] as String? ?? ''),
        trailing: TextButton(onPressed: () async { await _api.unblockUser(u['id'] as String); _load(); }, child: const Text('Buka Blokir', style: TextStyle(color: Colors.red))),
      );
    }),
  );
}
