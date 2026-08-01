import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import '../core/constants.dart';

class HelpLegalScreen extends StatelessWidget {
  const HelpLegalScreen({super.key});

  String get webBase => AppConstants.baseUrl.replaceAll('/api/v1', '');

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Bantuan & Legal')),
    body: ListView(children: [
      _section(ctx, 'Pusat Bantuan'),
      _tile(ctx, Icons.help_outline, 'Bantuan / FAQ', 'Bantuan'),
      _tile(ctx, Icons.support_agent, 'Kontak & Pengaduan (≤ 1×24 jam)', 'Kontak'),
      const Divider(),

      _section(ctx, 'Legal'),
      _tile(ctx, Icons.privacy_tip_outlined, 'Kebijakan Privasi (UU PDP)', 'Legal · Privasi'),
      _tile(ctx, Icons.description_outlined, 'Syarat & Ketentuan', 'Legal · Ketentuan'),
      _tile(ctx, Icons.cookie_outlined, 'Kebijakan Cookie', 'Legal · Cookie'),
      _tile(ctx, Icons.gavel_outlined, 'Kebijakan Pengembalian & Sengketa', 'Legal · Refund'),
      _tile(ctx, Icons.people_outline, 'Pedoman Komunitas', 'Legal · Pedoman'),
      _tile(ctx, Icons.verified_user_outlined, 'Kebijakan Verifikasi Identitas', 'Legal · Verifikasi'),
      const Divider(),

      _section(ctx, 'Keamanan'),
      _tile(ctx, Icons.security, 'Pusat Keamanan', 'Keamanan'),
      _tile(ctx, Icons.flag_outlined, 'Laporkan Konten Ilegal', 'Pelaporan'),

      const Divider(),
      _section(ctx, 'Lainnya'),
      _tile(ctx, Icons.monetization_on_outlined, 'Biaya & Harga', 'Biaya'),
      _tile(ctx, Icons.monitor_heart_outlined, 'Status Layanan', 'Status'),
      _tile(ctx, Icons.store_outlined, 'Untuk Penjual', 'Penjual'),
      _tile(ctx, Icons.article_outlined, 'Blog', 'Blog'),
      _tile(ctx, Icons.work_outline, 'Karier', 'Karier'),
    ]),
  );

  Widget _section(BuildContext ctx, String title) => Padding(
    padding: const EdgeInsets.fromLTRB(16, 20, 16, 4),
    child: Text(title, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Theme.of(ctx).colorScheme.primary)),
  );

  Widget _tile(BuildContext ctx, IconData icon, String title, String label) => ListTile(
    leading: Icon(icon, size: 22),
    title: Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
    subtitle: Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
    trailing: const Icon(Icons.chevron_right, size: 18, color: Colors.grey),
    onTap: () => ctx.push('/legal-webview', extra: {'title': title, 'label': label}),
  );
}

class LegalWebViewScreen extends StatelessWidget {
  final String title;
  final String label;

  const LegalWebViewScreen({super.key, required this.title, required this.label});

  String get url {
    final base = 'http://10.0.2.2:8000';
    switch (label) {
      case 'Bantuan': return '$base/bantuan';
      case 'Kontak': return '$base/kontak';
      case 'Legal · Privasi': return '$base/kebijakan-privasi';
      case 'Legal · Ketentuan': return '$base/syarat-ketentuan';
      case 'Legal · Cookie': return '$base/kebijakan-cookie';
      case 'Legal · Refund': return '$base/kebijakan-pengembalian';
      case 'Legal · Pedoman': return '$base/pedoman-komunitas';
      case 'Legal · Verifikasi': return '$base/kebijakan-verifikasi';
      case 'Keamanan': return '$base/keamanan';
      case 'Pelaporan': return '$base/kontak';
      case 'Biaya': return '$base/biaya';
      case 'Status': return '$base/status';
      case 'Penjual': return '$base/untuk-penjual';
      case 'Blog': return '$base/blog';
      case 'Karier': return '$base/karier';
      default: return '$base';
    }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: Text(title), actions: [
      IconButton(icon: const Icon(Icons.open_in_browser), tooltip: 'Buka di Browser', onPressed: () => launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication)),
    ]),
    body: Column(children: [
      Container(
        width: double.infinity,
        padding: const EdgeInsets.all(12),
        color: Colors.green.shade50,
        child: Row(children: [
          Icon(Icons.info_outline, size: 16, color: Colors.green.shade700),
          const SizedBox(width: 8),
          const Expanded(child: Text('Halaman ini dari situs web Seekitar', style: TextStyle(fontSize: 12, color: Colors.green))),
        ]),
      ),
      Expanded(child: _LegalWebView(url: url)),
    ]),
  );
}

class _LegalWebView extends StatelessWidget {
  final String url;
  const _LegalWebView({required this.url});

  @override Widget build(BuildContext ctx) {
    // Using native WebView via url_launcher for simplicity.
    // For production, add webview_flutter package for in-app browser.
    Future.microtask(() => launchUrl(Uri.parse(url), mode: LaunchMode.inAppBrowserView));
    return Center(
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        const CircularProgressIndicator(),
        const SizedBox(height: 16),
        Text('Membuka $url...', style: const TextStyle(color: Colors.grey)),
        const SizedBox(height: 8),
        TextButton.icon(
          icon: const Icon(Icons.open_in_browser),
          label: const Text('Buka di Browser'),
          onPressed: () => launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication),
        ),
      ]),
    );
  }
}

// ─── Router adapter — agar LegalWebViewScreen bisa menerima extra params ───
class LegalWebViewAdapter extends StatelessWidget {
  final Map<String, dynamic> extra;
  const LegalWebViewAdapter({super.key, required this.extra});
  @override Widget build(_) => LegalWebViewScreen(
    title: extra['title']?.toString() ?? '',
    label: extra['label']?.toString() ?? '',
  );
}
