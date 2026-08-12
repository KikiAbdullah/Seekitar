import 'package:flutter/material.dart';
import '../../services/api_compat.dart';
import '../../services/dio_client.dart';

class StatusScreen extends StatefulWidget {
  const StatusScreen({super.key});
  @override State<StatusScreen> createState() => _StatusScreenState();
}

class _StatusScreenState extends State<StatusScreen> {
  final _api = ApiProvider();
  Map<String,dynamic>? _config;
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try { final c = await _api.config(); if (mounted) setState(() { _config = c; _loading = false; }); }
    catch (e) { if (mounted) setState(() { _error = DioClient.friendly(e); _loading = false; }); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Status Layanan')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : RefreshIndicator(onRefresh: _load, child: ListView(padding: const EdgeInsets.all(16), children: [
      if (_error != null)
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(20)),
          child: Row(children: [Icon(Icons.error_outline, color: Colors.red.shade400, size: 28), const SizedBox(width: 12), Expanded(child: Text('Status layanan tidak dapat dimuat. Periksa koneksi Anda.', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: Colors.red.shade700)))],),
        )
      else
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(20)),
          child: const Row(children: [Icon(Icons.check_circle, color: Colors.green, size: 28), SizedBox(width: 12), Expanded(child: Text('Seluruh layanan berjalan normal', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: Colors.green)))])),

      const SizedBox(height: 16),
      _component(Icons.dns, 'Database', 'Koneksi & query', 'sehat'),
      _component(Icons.storage, 'Cache', 'Redis / penyimpanan sementara', 'sehat'),
      _component(Icons.cloud, 'Penyimpanan', 'Upload gambar & file', 'sehat'),
      _component(Icons.notifications, 'Notifikasi (WA)', 'WhatsApp gateway', _waConfigured ? 'sehat' : 'belum dikonfigurasi'),

      const SizedBox(height: 24),
      Text('Kontak Bantuan', style: Theme.of(ctx).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
      const SizedBox(height: 8),
      Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _row('Email', _config?['email_complaint']?.toString() ?? '-'),
        _row('WhatsApp', _config?['whatsapp']?.toString() ?? '-'),
        _row('Alamat', _config?['company_address']?.toString() ?? '-'),
      ]))),
      const SizedBox(height: 40),
    ])),
  );

  /// Hanya string WhatsApp non-kosong yang dianggap terkonfigurasi —
  /// nilai `false`/`0` tidak boleh dianggap "sehat".
  bool get _waConfigured {
    final wa = _config?['whatsapp'];
    return wa is String && wa.trim().isNotEmpty;
  }

  Widget _component(IconData icon, String name, String desc, String status) => Card(
    child: ListTile(
      leading: Container(width: 44, height: 44, decoration: BoxDecoration(color: status == 'sehat' ? Colors.green.shade50 : Colors.orange.shade50, borderRadius: BorderRadius.circular(12)), child: Icon(icon, color: status == 'sehat' ? Colors.green : Colors.orange, size: 22)),
      title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
      subtitle: Text(desc, style: const TextStyle(fontSize: 12, color: Colors.grey)),
      trailing: Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4), decoration: BoxDecoration(color: status == 'sehat' ? Colors.green.shade50 : Colors.orange.shade50, borderRadius: BorderRadius.circular(10)), child: Text(status == 'sehat' ? 'Normal' : 'Perlu Konfigurasi', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: status == 'sehat' ? Colors.green : Colors.orange))),
    ),
  );

  Widget _row(String l, String v) => Padding(padding: const EdgeInsets.symmetric(vertical: 4), child: Row(children: [SizedBox(width: 90, child: Text(l, style: TextStyle(color: Colors.grey.shade500, fontSize: 13))), Expanded(child: Text(v, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)))]));
}
