import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../providers/app_state.dart';
import '../../services/api_compat.dart';
import '../../services/dio_client.dart';

class FullSettingsScreen extends StatefulWidget { const FullSettingsScreen({super.key}); @override State<FullSettingsScreen> createState() => _FullSettingsScreenState(); }
class _FullSettingsScreenState extends State<FullSettingsScreen> {
  final _api = ApiProvider();
  String? _newPhone; bool _otpSent = false; int _countdown = 0; bool _exporting = false;

  Future<void> _sendOtp(String phone) async {
    try { await _api.requestPhoneChangeOtp(phone); if (mounted) { setState(() { _otpSent = true; _newPhone = phone; }); _startCountdown(); } }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: ${DioClient.friendly(e)}'))); }
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
        ElevatedButton(onPressed: _otpSent ? null : () {
          // Validasi SEBELUM pop, tanpa gap async — menghindari pemakaian
          // BuildContext setelah pop & misuse dialog.
          if (phoneCtrl.text.length < 10) {
            ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Nomor tidak valid')));
            return;
          }
          Navigator.pop(ctx, true);
          unawaited(_sendOtp(phoneCtrl.text));
        }, child: Text(_otpSent ? 'OTP Terkirim' : 'Lanjut')),
      ],
    ));
    if (ok == true && mounted && _otpSent && _newPhone != null) {
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
        catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: ${DioClient.friendly(e)}'))); }
      }
    }
  }

  Future<void> _exportData() async {
    if (_exporting) return;
    _exporting = true;
    // Dialog loading non-dismissible (termasuk tombol back via PopScope),
    // di-pop eksplisit via rootNavigator setelah selesai/gagal.
    unawaited(showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const PopScope(canPop: false, child: Center(child: CircularProgressIndicator())),
    ));
    try {
      await _api.exportData();
      if (mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Data berhasil diekspor.')));
      }
    } catch (e) {
      if (mounted) {
        Navigator.of(context, rootNavigator: true).pop();
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: ${DioClient.friendly(e)}')));
      }
    }
    _exporting = false;
  }
  /// Hapus akun wajib mengetik "HAPUS" — aksi ireversibel tidak boleh
  /// terpicu hanya dengan satu ketukan.
  void _confirmDelete() {
    final ctrl = TextEditingController();
    showDialog(context: context, builder: (ctx) => StatefulBuilder(
      builder: (ctx, setDialogState) => AlertDialog(
        title: const Text('Hapus Akun?'),
        content: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Seluruh data Anda akan dianonimkan. Ketik HAPUS untuk mengonfirmasi.'),
          const SizedBox(height: 12),
          TextField(
            controller: ctrl,
            onChanged: (_) => setDialogState(() {}),
            decoration: const InputDecoration(labelText: 'Ketik HAPUS', border: OutlineInputBorder()),
          ),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            onPressed: ctrl.text.trim() == 'HAPUS' ? () async {
              Navigator.pop(ctx);
              final app = context.read<AppState>();
              final router = GoRouter.of(context);
              try { await _api.deleteAccount(); if (mounted) await app.logout(); if (mounted) router.go('/login'); }
              catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: ${DioClient.friendly(e)}'))); }
            } : null,
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Hapus Akun'),
          ),
        ],
      ),
    ));
  }

  Future<void> _confirmLogout() async {
    final app = context.read<AppState>();
    final router = GoRouter.of(context);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar?'),
        content: const Text('Anda akan keluar dari akun Seekitar.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Keluar')),
        ],
      ),
    );
    if (ok == true && mounted) {
      await app.logout();
      if (mounted) router.go('/login');
    }
  }

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
      ListTile(leading: const Icon(Icons.logout, color: Colors.red), title: const Text('Keluar', style: TextStyle(color: Colors.red)), onTap: _confirmLogout),
      const SizedBox(height: 80),
    ]),
  );
  Widget _section(String title) => Padding(padding: const EdgeInsets.fromLTRB(16, 24, 16, 4), child: Text(title, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF168A4A))));
}
