import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';
import '../core/theme.dart';
import '../models/listing.dart';
import '../models/store.dart';
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
  bool _isMine = false;
  bool _chatting = false;
  final _pageCtrl = PageController();
  int _imgIdx = 0;
  bool _reporting = false;

  @override void initState() { super.initState(); _load(); }
  @override void dispose() { _pageCtrl.dispose(); super.dispose(); }

  String get _id => _detail?.id ?? widget.listingId;

  Future<void> _load() async {
    if (widget.listing != null) { setState(() { _detail = widget.listing; _loading = false; }); _checkMine(); return; }
    setState(() => _loading = true);
    try { final d = await _api.getListing(widget.listingId); setState(() { _detail = d; _loading = false; }); _checkMine(); }
    catch (_) { setState(() => _loading = false); }
  }

  Future<void> _checkMine() async {
    try {
      final stores = await _api.mine();
      if (mounted) setState(() => _isMine = stores.any((s) => s.id == _detail?.storeId));
    } catch (_) {}
  }

  Future<void> _chat() async {
    final ownerId = _detail?.storeOwnerId;
    if (ownerId == null || ownerId.isEmpty) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Toko belum dapat dihubungi.')));
      return;
    }
    setState(() => _chatting = true);
    try {
      final conv = await _api.createConversation(ownerId);
      if (mounted) ctx.push('/chat/${conv.id}', extra: conv);
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e')));
    }
    if (mounted) setState(() => _chatting = false);
  }

  void _buy() {
    final l = _detail;
    if (l == null) return;
    ctx.push('/checkout', extra: l);
  }

  BuildContext get ctx => context;

  Future<void> _share() async {
    try {
      final res = await _api.shareListing(_id);
      final text = res['whatsapp_text']?.toString() ?? _detail?.title ?? '';
      if (text.isNotEmpty) {
        await SharePlus.instance.share(ShareParams(
          text: text,
          subject: _detail?.title ?? 'Listing Seekitar',
        ));
      }
    } catch (_) {}
  }

  Future<void> _report() async {
    final reasonCtrl = TextEditingController();
    final reason = await showModalBottomSheet<String>(context: context, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))), builder: (ctx) => Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))), const SizedBox(height: 20),
      const Text('Laporkan Listing', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)), const SizedBox(height: 16),
      ...[
        ['Penipuan', 'scam'], ['Konten tidak pantas', 'inappropriate'], ['Barang ilegal', 'illegal'],
        ['Spam', 'spam'], ['Lainnya', 'other'],
      ].map((r) => ListTile(title: Text(r[0]), leading: const Icon(Icons.flag_outlined, size: 20), onTap: () => Navigator.pop(ctx, r[1]), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)))),
    ])));
    if (reason != null && mounted) {
      setState(() => _reporting = true);
      try {
        await _api.report('listing', _id, reason);
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Laporan terkirim. Tim kami akan meninjaunya.')));
      } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
      if (mounted) setState(() => _reporting = false);
    }
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    if (_loading) return Scaffold(appBar: AppBar(), body: const Center(child: CircularProgressIndicator()));
    final l = _detail;
    if (l == null) return Scaffold(appBar: AppBar(), body: const Center(child: Text('Listing tidak ditemukan')));

    return Scaffold(
      appBar: AppBar(title: const Text('Detail'), actions: [
        IconButton(icon: const Icon(Icons.share), onPressed: _share),
        IconButton(icon: Icon(_reporting ? Icons.hourglass_empty : Icons.flag_outlined), onPressed: _reporting ? null : _report),
      ]),
      bottomNavigationBar: _bottomBar(t),
      body: SingleChildScrollView(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(height: 280, child: l.images.isNotEmpty ? Stack(children: [
          PageView.builder(controller: _pageCtrl, onPageChanged: (i) => setState(() => _imgIdx = i), itemCount: l.images.length, itemBuilder: (_, i) => Image.network(l.images[i], width: double.infinity, fit: BoxFit.cover, errorBuilder: (_,__,___) => _galleryFallback())),
          Positioned(top: 12, left: 12, child: Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: Colors.black54, borderRadius: BorderRadius.circular(8)), child: Text(l.typeLabel, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600)))),
          if (l.images.length > 1) Positioned(bottom: 16, left: 0, right: 0, child: Center(child: SmoothPageIndicator(controller: _pageCtrl, count: l.images.length, effect: const WormEffect(activeDotColor: Colors.white, dotColor: Colors.white38, dotHeight: 8, dotWidth: 8)))),
        ]) : _galleryFallback()),
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
            const SizedBox(height: 8), Text(l.description!, style: TextStyle(color: Colors.grey.shade700, height: 1.6)),
            const SizedBox(height: 16),
          ],
          Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
            if (l.stockQty != null) _row(Icons.inventory, 'Stok', '${l.stockQty}'),
            if (l.slot != null) _row(Icons.people, 'Slot', '${l.slot}'),
            _row(Icons.calendar_today, 'Dipasang', l.createdAt != null ? '${l.createdAt!.day}/${l.createdAt!.month}/${l.createdAt!.year}' : '-'),
          ]))),
        ])),
        Container(margin: const EdgeInsets.all(16), decoration: BoxDecoration(borderRadius: BorderRadius.circular(20), gradient: LinearGradient(colors: [Colors.white, AppTheme.heroGradientStart, Colors.white])), child: Padding(padding: const EdgeInsets.all(20), child: Column(children: [
          const Icon(Icons.phone_android, size: 44, color: Color(0xFF168A4A)), const SizedBox(height: 12),
          Text('Transaksi di Aplikasi', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: t.colorScheme.primary)),
          const SizedBox(height: 6),
          Text('Untuk membeli atau chat penjual, gunakan aplikasi Seekitar di ponselmu. Gratis, tanpa komisi.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, fontSize: 13, height: 1.5)),
        ]))),
        const SizedBox(height: 40),
      ])),
    );
  }

  Widget _galleryFallback() => Container(height: 280, decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [AppTheme.heroGradientStart, AppTheme.primarySubtle])), child: const Center(child: Icon(Icons.image, size: 64, color: Color(0xFF168A4A))));
  Widget _row(IconData icon, String label, String value) => Padding(padding: const EdgeInsets.symmetric(vertical: 6), child: Row(children: [Icon(icon, size: 20, color: Colors.grey.shade500), const SizedBox(width: 10), Text(label, style: TextStyle(color: Colors.grey.shade600)), const Spacer(), Text(value, style: const TextStyle(fontWeight: FontWeight.w600))]));

  Widget _bottomBar(ThemeData t) {
    if (_isMine) {
      return SafeArea(child: Padding(padding: const EdgeInsets.all(12), child: Container(width: double.infinity, padding: const EdgeInsets.symmetric(vertical: 14), decoration: BoxDecoration(color: AppTheme.primarySubtle, borderRadius: BorderRadius.circular(16)), child: const Row(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.store, size: 18, color: Color(0xFF168A4A)), SizedBox(width: 8), Text('Ini listing milikmu', style: TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF168A4A)))]))));
    }
    return SafeArea(child: Container(padding: const EdgeInsets.fromLTRB(16, 8, 16, 12), decoration: BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 12)]), child: Row(children: [
      OutlinedButton.icon(onPressed: _chatting ? null : _chat, icon: const Icon(Icons.chat_bubble_outline, size: 18), label: const Text('Chat'), style: OutlinedButton.styleFrom(minimumSize: const Size(96, 52))),
      const SizedBox(width: 12),
      Expanded(child: ElevatedButton.icon(
        onPressed: _detail?.price == null ? null : _buy,
        icon: const Icon(Icons.shopping_cart_outlined, size: 18),
        label: Text(_detail?.price == null ? 'Hubungi Penjual' : 'Beli Sekarang'),
        style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(52)),
      )),
    ])));
  }
}
