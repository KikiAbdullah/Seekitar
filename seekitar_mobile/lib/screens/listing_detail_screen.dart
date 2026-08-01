import 'package:flutter/material.dart';
import 'package:share_plus/share_plus.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import '../core/theme.dart';
import '../models/listing.dart';
import '../services/api_compat.dart';

class ListingDetailScreen extends StatefulWidget {
  final String listingId;
  final Listing? listing;
  const ListingDetailScreen({super.key, this.listingId = '', this.listing});

  @override State<ListingDetailScreen> createState() => _ListingDetailScreenState();
}

class _ListingDetailScreenState extends State<ListingDetailScreen> {
  final _api = ApiProvider();
  Listing? _detail;
  bool _loading = true;
  final _pageCtrl = PageController();
  int _imgIdx = 0;

  @override void initState() { super.initState(); _load(); }
  @override void dispose() { _pageCtrl.dispose(); super.dispose(); }

  Future<void> _load() async {
    if (widget.listing != null) { setState(() { _detail = widget.listing; _loading = false; }); return; }
    setState(() => _loading = true);
    try { final d = await _api.getListing(widget.listingId); setState(() { _detail = d; _loading = false; }); }
    catch (_) { setState(() => _loading = false); }
  }

  Future<void> _share() async {
    try {
      final res = await _api.shareListing(_detail?.id ?? widget.listingId);
      final text = res['whatsapp_text']?.toString() ?? _detail?.title ?? '';
      await SharePlus.instance.share(ShareParams(
        text: text,
        subject: _detail?.title ?? 'Listing Seekitar',
      ));
    } catch (_) {}
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    if (_loading) return Scaffold(appBar: AppBar(), body: const Center(child: CircularProgressIndicator()));
    final l = _detail;
    if (l == null) return Scaffold(appBar: AppBar(), body: const Center(child: Text('Listing tidak ditemukan')));

    return Scaffold(
      appBar: AppBar(title: const Text('Detail'), actions: [IconButton(icon: const Icon(Icons.share), onPressed: _share), IconButton(icon: const Icon(Icons.flag_outlined), onPressed: () {})]),
      body: SingleChildScrollView(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        // ─── Galeri ───
        SizedBox(height: 280, child: l.images.isNotEmpty ? Stack(children: [
          PageView.builder(controller: _pageCtrl, onPageChanged: (i) => setState(() => _imgIdx = i), itemCount: l.images.length, itemBuilder: (_, i) => Image.network(l.images[i], width: double.infinity, fit: BoxFit.cover, errorBuilder: (_,__,___) => _galleryFallback())),
          Positioned(top: 12, left: 12, child: Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: Colors.black54, borderRadius: BorderRadius.circular(8)), child: Text(l.typeLabel, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13)))),
          if (l.images.length > 1) Positioned(bottom: 16, left: 0, right: 0, child: Center(child: SmoothPageIndicator(controller: _pageCtrl, count: l.images.length, effect: const WormEffect(activeDotColor: Colors.white, dotColor: Colors.white38, dotHeight: 8, dotWidth: 8)))),
        ]) : _galleryFallback()),

        // ─── Info ───
        Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(l.title, style: t.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Text(l.priceDisplay, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: t.colorScheme.primary)),
          const SizedBox(height: 16),

          if (l.storeName != null) Card(child: Padding(padding: const EdgeInsets.all(16), child: Row(children: [
            CircleAvatar(radius: 28, backgroundColor: Colors.green.shade50, child: Text((l.storeName ?? 'T').substring(0, 2).toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.bold))),
            const SizedBox(width: 12),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [Text(l.storeName!, style: const TextStyle(fontWeight: FontWeight.w600)), if (l.storeVerified == true) ...[const SizedBox(width: 4), const Icon(Icons.verified, size: 18, color: Colors.green)]],),
              Text(l.storeDistrict ?? '', style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
            ])),
          ]))),

          const SizedBox(height: 16),
          if (l.description?.isNotEmpty == true) ...[
            Text('Deskripsi', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text(l.description!, style: TextStyle(color: Colors.grey.shade700, height: 1.6)),
            const SizedBox(height: 16),
          ],

          Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
            if (l.stockQty != null) _row(Icons.inventory, 'Stok', '${l.stockQty}'),
            if (l.slot != null) _row(Icons.people, 'Slot', '${l.slot}'),
            _row(Icons.calendar_today, 'Dipasang', l.createdAt != null ? '${l.createdAt!.day}/${l.createdAt!.month}/${l.createdAt!.year}' : '-'),
          ]))),
        ])),

        // ─── CTA ───
        Container(
          margin: const EdgeInsets.all(16),
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(20), gradient: LinearGradient(colors: [Colors.white, AppTheme.heroGradientStart, Colors.white])),
          child: Stack(children: [
            Positioned(right: -10, top: -10, child: ClipRRect(borderRadius: BorderRadius.circular(16), child: Image.asset('assets/images/hero.jpg', width: 140, height: 140, fit: BoxFit.cover, opacity: const AlwaysStoppedAnimation(0.12)))),
            Padding(padding: const EdgeInsets.all(20), child: Column(children: [
              const Icon(Icons.phone_android, size: 44, color: Color(0xFF168A4A)),
              const SizedBox(height: 12),
              Text('Transaksi di Aplikasi', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: t.colorScheme.primary)),
              const SizedBox(height: 6),
              Text('Untuk membeli atau chat penjual, gunakan aplikasi Seekitar di ponselmu. Gratis, tanpa komisi.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, fontSize: 13, height: 1.5)),
            ])),
          ]),
        ),
        const SizedBox(height: 40),
      ])),
    );
  }

  Widget _galleryFallback() => Container(
    height: 280,
    decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [AppTheme.heroGradientStart, AppTheme.primarySubtle])),
    child: Stack(children: [
      Positioned.fill(child: Image.asset('assets/images/hero.jpg', fit: BoxFit.cover, opacity: const AlwaysStoppedAnimation(0.25))),
      const Center(child: Icon(Icons.image, size: 64, color: Color(0xFF168A4A))),
    ]),
  );

  Widget _row(IconData icon, String label, String value) => Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Row(children: [Icon(icon, size: 20, color: Colors.grey.shade500), const SizedBox(width: 10), Text(label, style: TextStyle(color: Colors.grey.shade600)), const Spacer(), Text(value, style: const TextStyle(fontWeight: FontWeight.w600))]));
}
