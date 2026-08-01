import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../providers/app_state.dart';
import '../services/api_compat.dart';

class FullSettingsScreen extends StatefulWidget {
  const FullSettingsScreen({super.key});
  @override State<FullSettingsScreen> createState() => _FullSettingsScreenState();
}

class _FullSettingsScreenState extends State<FullSettingsScreen> {
  final _api = ApiProvider();

  Future<void> _exportData() async {
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try {
      await _api.exportData();
      if (mounted) { Navigator.pop(context); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Data pribadi berhasil diekspor. Hubungi dukungan untuk mengunduhnya.'))); }
    } catch (e) { if (mounted) { Navigator.pop(context); ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); } }
  }

  void _confirmDelete() => showDialog(context: context, builder: (ctx) => AlertDialog(
    title: const Text('Hapus Akun?'),
    content: const Text('Semua data pribadimu akan dianonimkan. Tindakan ini tidak bisa dibatalkan.'),
    actions: [
      TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
      ElevatedButton(onPressed: () async {
        Navigator.pop(ctx);
        try {
          await _api.deleteAccount();
          if (mounted) { await context.read<AppState>().logout(); context.go('/login'); }
        } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
      }, style: ElevatedButton.styleFrom(backgroundColor: Colors.red), child: const Text('Hapus Akun')),
    ],
  ));

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Pengaturan')),
    body: ListView(children: [
      _section('Akun'),
      ListTile(leading: const Icon(Icons.phone_android), title: const Text('Ganti Nomor WhatsApp'), trailing: const Icon(Icons.chevron_right), onTap: () {}),
      ListTile(leading: const Icon(Icons.download), title: const Text('Ekspor Data Saya'), subtitle: const Text('UU PDP Pasal 8'), trailing: const Icon(Icons.chevron_right), onTap: _exportData),
      ListTile(leading: const Icon(Icons.delete_forever, color: Colors.red), title: const Text('Hapus Akun', style: TextStyle(color: Colors.red)), trailing: const Icon(Icons.chevron_right, color: Colors.red), onTap: _confirmDelete),
      _section('Notifikasi'),
      ListTile(leading: const Icon(Icons.notifications_outlined), title: const Text('Preferensi Notifikasi'), trailing: const Icon(Icons.chevron_right), onTap: () => ctx.push('/notif-prefs')),
      _section('Lainnya'),
      ListTile(leading: const Icon(Icons.info_outline), title: const Text('Tentang Seekitar'), trailing: const Icon(Icons.chevron_right), onTap: () => ctx.push('/about')),
      ListTile(leading: const Icon(Icons.logout, color: Colors.red), title: const Text('Keluar', style: TextStyle(color: Colors.red)), onTap: () async { await ctx.read<AppState>().logout(); if (ctx.mounted) ctx.go('/login'); }),
      const SizedBox(height: 80),
    ]),
  );

  Widget _section(String title) => Padding(padding: const EdgeInsets.fromLTRB(16, 24, 16, 4), child: Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF168A4A))));
}
