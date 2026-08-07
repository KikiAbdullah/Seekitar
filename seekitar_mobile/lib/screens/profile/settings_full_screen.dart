import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/app_state.dart';
import '../../services/api_compat.dart';

class FullSettingsScreen extends StatefulWidget { const FullSettingsScreen({super.key}); @override State<FullSettingsScreen> createState() => _FullSettingsScreenState(); }
class _FullSettingsScreenState extends State<FullSettingsScreen> {
  final _api = ApiProvider();
  String? _newPhone; bool _otpSent = false, _loadingOtp = false; int _countdown = 0;

  Future<void> _sendOtp(String phone) async {
    setState(() { _loadingOtp = true; });
    try { await _api.requestPhoneChangeOtp(phone); if (mounted) { setState(() { _otpSent = true; _newPhone = phone; }); _startCountdown(); } }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    if (mounted) setState(() => _loadingOtp = false);
  }
  void _startCountdown() { _countdown = 60; Future.doWhile(() async { await Future.delayed(const Duration(seconds: 1)); if (!mounted) return false; setState(() => _countdown--); return _countdown > 0; }); }
  Future<void> _changePhone() async {
    final phoneCtrl = TextEditingController();
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      title: const Text('Ganti Nomor WhatsApp'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(controller: phoneCtrl, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Nomor Baru', hintText: '6281234567890', prefixText: '+')),
        if (_otpSent && _newPhone != null) ...[const SizedBox(height: 12), const Text('OTP dikirim ke nomor baru. Cek WhatsApp.', style: TextStyle(fontSize: 13, color: Colors.green))],
      ]),
      actions: [
        TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
        ElevatedButton(onPressed: _otpSent ? null : () async { Navigator.pop(ctx, true); if (phoneCtrl.text.length >= 10) await _sendOtp(phoneCtrl.text); else ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor tidak valid'))); }, child: Text(_otpSent ? 'OTP Terkirim' : 'Lanjut')),
      ],
    ));
    if (ok == true && _otpSent && _newPhone != null) {
      final otpCtrl = TextEditingController();
      final v = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title: const Text('Verifikasi OTP'),
        content: TextField(controller: otpCtrl, keyboardType: TextInputType.number, maxLength: 6, decoration: const InputDecoration(labelText: 'Kode OTP', counterText: '')),
        actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Verifikasi'))],
      ));
      if (v == true) {
        try { await _api.verifyPhoneChangeOtp(_newPhone!, otpCtrl.text); if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor WhatsApp berhasil diganti!')));
            setState(() { _otpSent = false; _newPhone = null; }); } }
        catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
      }
    }
  }

  Future<void> _exportData() async {
    showDialog(context: context, barrierDismissible: false, builder: (_) => const Center(child: CircularProgressIndicator()));
    try { await _api.exportData(); if (mounted) { Navigator.pop(context); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Data berhasil diekspor.'))); } }
    catch (e) { if (mounted) { Navigator.pop(context); ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); } }
  }
  void _confirmDelete() => showDialog(context: context, builder: (ctx) => AlertDialog(title: const Text('Hapus Akun?'), content: const Text('Semua data akan dianonimkan.'), actions: [TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')), ElevatedButton(onPressed: () async { Navigator.pop(ctx); try { await _api.deleteAccount(); if (mounted) { await context.read<AppState>().logout(); context.go('/login'); } } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); } }, style: ElevatedButton.styleFrom(backgroundColor: Colors.red), child: const Text('Hapus Akun'))]));

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Pengaturan')),
    body: ListView(children: [
      _section('Akun'),
      ListTile(leading: const Icon(Icons.phone_android), title: const Text('Ganti Nomor WhatsApp'), subtitle: const Text('Verifikasi OTP ke nomor baru'), trailing: const Icon(Icons.chevron_right), onTap: _changePhone),
      ListTile(leading: const Icon(Icons.download), title: const Text('Ekspor Data Saya'), subtitle: const Text('UU PDP Pasal 8'), trailing: const Icon(Icons.chevron_right), onTap: _exportData),
      ListTile(leading: const Icon(Icons.delete_forever, color: Colors.red), title: const Text('Hapus Akun', style: TextStyle(color: Colors.red)), trailing: const Icon(Icons.chevron_right, color: Colors.red), onTap: _confirmDelete),
      _section('Bantuan & Hukum'),
      ListTile(leading: const Icon(Icons.help_outline), title: const Text('Pusat Bantuan & Legal'), trailing: const Icon(Icons.chevron_right), onTap: () => context.push('/help-legal')),
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
