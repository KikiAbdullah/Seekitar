import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_compat.dart';

class AboutScreen extends StatefulWidget {
  const AboutScreen({super.key});
  @override State<AboutScreen> createState() => _AboutScreenState();
}

class _AboutScreenState extends State<AboutScreen> {
  final _api = ApiProvider();
  Map<String,dynamic>? _config;
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final c = await _api.config(); if (mounted) setState(() { _config = c; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  String _val(String key) => _config?[key]?.toString() ?? '-';

  @override Widget build(BuildContext ctx) {
    if (_loading) return Scaffold(appBar: AppBar(title: const Text('Tentang')), body: const Center(child: CircularProgressIndicator()));
    final t = Theme.of(ctx);
    final regency = _val('regency');
    final company = _val('company_name');
    final pse = _val('pse_registration_number');
    final whatsapp = _val('whatsapp');

    return Scaffold(
      appBar: AppBar(title: const Text('Tentang')),
      body: ListView(padding: const EdgeInsets.all(20), children: [
        Center(child: Image.asset('assets/images/logo.png', width: 80, height: 80)),
        const SizedBox(height: 20),
        Text(company, textAlign: TextAlign.center, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
        const SizedBox(height: 4),
        Text('Pasar Lokal $regency', textAlign: TextAlign.center, style: const TextStyle(fontSize: 14, color: Colors.grey)),

        if (pse.isNotEmpty && pse != '-') ...[
          const SizedBox(height: 8),
          Center(child: Container(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6), decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(12)), child: Text('PSE: $pse', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Colors.green)))),
        ],

        const SizedBox(height: 32),
        Card(child: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Tentang', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 10),
          Text('Seekitar adalah pasar lokal dua arah dalam satu kabupaten. Beli dan jual barang, jasa, serta sewa di sekitar Anda — gratis, tanpa komisi.', style: TextStyle(color: Colors.grey.shade700, height: 1.6)),
          const SizedBox(height: 16),
          Container(padding: const EdgeInsets.all(12), decoration: BoxDecoration(color: t.colorScheme.primary.withOpacity(0.08), borderRadius: BorderRadius.circular(12)), child: Row(children: [Icon(Icons.map, size: 18, color: t.colorScheme.primary), const SizedBox(width: 8), const Expanded(child: Text('Fokus pada satu kabupaten — barang & jasa dari tetangga terdekatmu', style: TextStyle(fontSize: 13)))])),
        ]))),

        const SizedBox(height: 12),
        Card(child: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Kontak', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 10),
          _row(Icons.mail_outline, 'Pengaduan', _val('email_complaint'), 'mailto:${_val('email_complaint')}'),
          _row(Icons.report_outlined, 'Konten Ilegal', _val('email_abuse'), 'mailto:${_val('email_abuse')}'),
          _row(Icons.privacy_tip_outlined, 'Privasi (UU PDP)', _val('email_privacy'), 'mailto:${_val('email_privacy')}'),
          _row(Icons.security, 'Keamanan', _val('email_security'), 'mailto:${_val('email_security')}'),
          _row(Icons.phone, 'WhatsApp', '+62 $whatsapp', 'https://wa.me/$whatsapp'),
          const SizedBox(height: 8),
          Text(_val('company_address'), style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
        ]))),

        const SizedBox(height: 12),
        Card(child: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          const Text('Nilai Kami', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 10),
          _value('Gratis Selamanya', 'Tidak ada biaya pendaftaran, langganan, atau komisi transaksi.'),
          _value('Fokus Lokal', 'Hanya transaksi dalam satu kabupaten — uang berputar di komunitasmu.'),
          _value('Verifikasi Nyata', 'Setiap penjual diverifikasi KTP — pembeli tahu penjualnya orang sungguhan.'),
          _value('Dua Arah', 'Pembeli pasang kebutuhan, penjual kirim penawaran. Transaksi langsung tercatat.'),
        ]))),
        const SizedBox(height: 40),
      ]),
    );
  }

  Widget _row(IconData icon, String label, String value, String link) => Padding(padding: const EdgeInsets.symmetric(vertical: 5), child: Row(children: [
    Icon(icon, size: 18, color: Colors.grey.shade500), const SizedBox(width: 8),
    SizedBox(width: 90, child: Text(label, style: TextStyle(color: Colors.grey.shade500, fontSize: 13))),
    Expanded(child: GestureDetector(onTap: () => launchUrl(Uri.parse(link)), child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13))))));

  Widget _value(String title, String desc) => Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
    const Icon(Icons.check_circle, size: 18, color: Color(0xFF168A4A)), const SizedBox(width: 10),
    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(title, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
      Text(desc, style: TextStyle(color: Colors.grey.shade600, fontSize: 12, height: 1.4)),
    ])),
  ]));
}
