import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:go_router/go_router.dart';
import '../services/api_compat.dart';

class VerificationScreen extends StatefulWidget {
  const VerificationScreen({super.key});
  @override State<VerificationScreen> createState() => _VerificationScreenState();
}

class _VerificationScreenState extends State<VerificationScreen> {
  final _api = ApiProvider();
  final _nikCtrl = TextEditingController();
  File? _ktp, _selfie;
  bool _loading = false;
  String? _status;

  Future<void> _pick(bool isKtp) async {
    final picker = ImagePicker();
    final x = await picker.pickImage(source: ImageSource.camera, maxWidth: 1280);
    if (x != null) {
      final compressed = await FlutterImageCompress.compressAndGetFile(x.path, '${x.path}_comp.jpg', quality: 75);
      if (compressed != null) setState(() { if (isKtp) _ktp = File(compressed.path); else _selfie = File(compressed.path); });
    }
  }

  Future<void> _submit() async {
    if (_ktp == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Foto KTP wajib diisi'))); return; }
    if (_selfie == null) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Selfie wajib diisi'))); return; }
    setState(() => _loading = true);
    try {
      await _api.uploadKtp(_ktp!, _selfie!, nik: _nikCtrl.text.isNotEmpty ? _nikCtrl.text : null);
      setState(() => _status = 'submitted');
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Berkas terkirim! Ditinjau maksimal 1x24 jam.')));
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    if (mounted) setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    final body = _status == 'submitted'
        ? Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(color: t.colorScheme.primary.withOpacity(0.08), borderRadius: BorderRadius.circular(28)),
            child: Column(children: [
              const Icon(Icons.check_circle, size: 72, color: Color(0xFF168A4A)),
              const SizedBox(height: 20),
              const Text('Berkas Terkirim!', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              Text('Admin akan meninjau identitasmu dalam 1x24 jam. Kamu akan mendapat notifikasi setelah selesai.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, height: 1.5)),
              const SizedBox(height: 24),
              OutlinedButton(onPressed: () => Navigator.pop(context), child: const Text('Kembali')),
            ]),
          )
        : _buildForm(t);
    return Scaffold(
      appBar: AppBar(title: const Text('Verifikasi Identitas')),
      body: ListView(padding: const EdgeInsets.all(20), children: [body]),
    );
  }

  Widget _buildForm(ThemeData t) => Column(children: [
    Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(16)), child: Row(children: [Icon(Icons.info_outline, color: Colors.blue.shade700), const SizedBox(width: 10), const Expanded(child: Text('Verifikasi diperlukan untuk membuka toko. Data KTP dienkripsi.', style: TextStyle(fontSize: 13)))])),
    const SizedBox(height: 20),
    Text('Foto KTP', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
    const SizedBox(height: 10),
    GestureDetector(
      onTap: () => _pick(true),
      child: Container(
        height: 180, decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.grey.shade300, width: 1.5)),
        child: _ktp != null ? ClipRRect(borderRadius: BorderRadius.circular(18), child: Image.file(_ktp!, fit: BoxFit.cover, width: double.infinity))
            : const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.camera_alt, size: 36, color: Colors.grey), SizedBox(height: 8), Text('Ketuk untuk foto KTP', style: TextStyle(color: Colors.grey))])),
      ),
    ),
    const SizedBox(height: 20),
    Text('Selfie dengan KTP', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
    const SizedBox(height: 10),
    GestureDetector(
      onTap: () => _pick(false),
      child: Container(
        height: 180, decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.grey.shade300, width: 1.5)),
        child: _selfie != null ? ClipRRect(borderRadius: BorderRadius.circular(18), child: Image.file(_selfie!, fit: BoxFit.cover, width: double.infinity))
            : const Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.face, size: 36, color: Colors.grey), SizedBox(height: 8), Text('Ketuk untuk selfie', style: TextStyle(color: Colors.grey))])),
      ),
    ),
    const SizedBox(height: 16),
    TextField(controller: _nikCtrl, keyboardType: TextInputType.number, maxLength: 16, decoration: const InputDecoration(labelText: 'NIK (opsional)', hintText: '16 digit NIK KTP')),
    const SizedBox(height: 24),
    SizedBox(height: 56, child: ElevatedButton(onPressed: _loading ? null : _submit, child: _loading ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : const Text('Kirim Verifikasi', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700)))),
  ]);
  @override void dispose() { _nikCtrl.dispose(); super.dispose(); }
}
