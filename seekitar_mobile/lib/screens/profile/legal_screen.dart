import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../services/api_compat.dart';

class HelpLegalScreen extends StatefulWidget {
  const HelpLegalScreen({super.key});
  @override State<HelpLegalScreen> createState() => _HelpLegalScreenState();
}

class _HelpLegalScreenState extends State<HelpLegalScreen> {
  final _api = ApiProvider();
  String _webBase = '';

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    try {
      final c = await _api.config();
      final base = c['regency']?.toString() ?? '';
      // Derive web URL from play store or hardcoded
      _webBase = 'https://seekitar.id';
      if (mounted) setState(() {});
    } catch (_) {
      _webBase = 'https://seekitar.id';
      if (mounted) setState(() {});
    }
  }

  String _url(String path) => '$_webBase$path';

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Bantuan & Legal')),
    body: ListView(children: [
      _section(ctx, 'Pusat Bantuan'),
      _tile(ctx, Icons.help_outline, 'Bantuan / FAQ', _url('/bantuan'), 'FAQ'),
      _tile(ctx, Icons.support_agent, 'Kontak & Pengaduan (≤ 1×24 jam)', _url('/kontak'), 'Kontak'),
      const Divider(),

      _section(ctx, 'Legal'),
      _tile(ctx, Icons.privacy_tip_outlined, 'Kebijakan Privasi (UU PDP)', _url('/kebijakan-privasi'), 'Privasi'),
      _tile(ctx, Icons.description_outlined, 'Syarat & Ketentuan', _url('/syarat-ketentuan'), 'Ketentuan'),
      _tile(ctx, Icons.cookie_outlined, 'Kebijakan Cookie', _url('/kebijakan-cookie'), 'Cookie'),
      _tile(ctx, Icons.gavel_outlined, 'Pengembalian & Sengketa', _url('/kebijakan-pengembalian'), 'Refund'),
      _tile(ctx, Icons.people_outline, 'Pedoman Komunitas', _url('/pedoman-komunitas'), 'Pedoman'),
      _tile(ctx, Icons.verified_user_outlined, 'Verifikasi Identitas', _url('/kebijakan-verifikasi'), 'Verifikasi'),
      const Divider(),

      _section(ctx, 'Keamanan'),
      _tile(ctx, Icons.security, 'Pusat Keamanan', _url('/keamanan'), 'Keamanan'),
      _tile(ctx, Icons.flag_outlined, 'Laporkan Konten Ilegal', _url('/kontak'), 'Pelaporan'),

      const Divider(),
      _section(ctx, 'Lainnya'),
      _tile(ctx, Icons.monetization_on_outlined, 'Biaya & Harga', _url('/biaya'), 'Biaya'),
      _tile(ctx, Icons.monitor_heart_outlined, 'Status Layanan', _url('/status'), 'Status'),
      _tile(ctx, Icons.store_outlined, 'Untuk Penjual', _url('/untuk-penjual'), 'Penjual'),
      _tile(ctx, Icons.article_outlined, 'Blog', _url('/blog'), 'Blog'),
      _tile(ctx, Icons.work_outline, 'Karier', _url('/karier'), 'Karier'),
    ]),
  );

  Widget _section(BuildContext ctx, String title) => Padding(padding: const EdgeInsets.fromLTRB(16, 20, 16, 4), child: Text(title, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Theme.of(ctx).colorScheme.primary)));

  Widget _tile(BuildContext ctx, IconData icon, String title, String url, String label) => ListTile(
    leading: Icon(icon, size: 22),
    title: Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
    subtitle: Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
    trailing: const Icon(Icons.open_in_new, size: 16, color: Colors.grey),
    onTap: () => launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication),
  );
}
