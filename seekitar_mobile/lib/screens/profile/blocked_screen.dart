import 'package:flutter/material.dart';
import '../../core/text_utils.dart';
import '../../services/api_compat.dart';
import '../../services/dio_client.dart';
import '../../widgets/error_view.dart';

class BlockedUsersScreen extends StatefulWidget {
  const BlockedUsersScreen({super.key});
  @override State<BlockedUsersScreen> createState() => _BlockedUsersScreenState();
}

class _BlockedUsersScreenState extends State<BlockedUsersScreen> {
  final _api = ApiProvider();
  List<Map<String,dynamic>> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try { final b = await _api.getBlockedUsers(); if (mounted) setState(() { _items = b; _loading = false; }); }
    catch (e) { if (mounted) setState(() { _error = DioClient.friendly(e); _loading = false; }); }
  }

  Future<void> _unblock(Map<String,dynamic> u) async {
    try {
      await _api.unblockUser(u['id'] as String);
      if (mounted) await _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(DioClient.friendly(e))));
    }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Pengguna Diblokir')),
    body: _loading ? const Center(child: CircularProgressIndicator())
      : _error != null ? ErrorView(message: _error!, onRetry: _load)
      : _items.isEmpty ? const Center(child: Text('Tidak ada pengguna diblokir'))
      : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
      final u = _items[i];
      return ListTile(
        leading: CircleAvatar(child: Text(avatarInitials(u['name'] as String?, maxChars: 1))),
        title: Text(u['name'] as String? ?? ''),
        trailing: TextButton(onPressed: () => _unblock(u), child: const Text('Buka Blokir', style: TextStyle(color: Colors.red))),
      );
    })),
  );
}
