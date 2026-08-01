import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:geolocator/geolocator.dart';
import '../core/theme.dart';
import '../models/listing.dart';
import '../models/customer_request.dart';
import '../services/api_compat.dart';
import 'listing_detail_screen.dart';
import 'request_detail_screen.dart';
import '../widgets/offline_banner.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});
  @override State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _api = ApiProvider();
  List<Listing> _listings = [];
  List<CustomerRequest> _requests = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    if (!mounted) return;
    setState(() { _loading = true; _error = null; });
    try {
      final pos = await _determinePosition();
      final res = await _api.home(pos.latitude, pos.longitude);
      if (!mounted) return;
      final lRaw = res['listings'], rRaw = res['requests'];
      setState(() {
        if (lRaw is Map && lRaw['data'] is List) _listings = (lRaw['data'] as List).map((d) => Listing.fromJson(d as Map<String,dynamic>)).toList();
        if (rRaw is Map && rRaw['data'] is List) _requests = (rRaw['data'] as List).map((d) => CustomerRequest.fromJson(d as Map<String,dynamic>)).toList();
        _loading = false;
      });
    } catch (e) { if (mounted) setState(() { _error = e.toString(); _loading = false; }); }
  }

  Future<Position> _determinePosition() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) throw '';
      var p = await Geolocator.checkPermission();
      if (p == LocationPermission.denied) p = await Geolocator.requestPermission();
      if (p == LocationPermission.denied || p == LocationPermission.deniedForever) throw '';
      return await Geolocator.getCurrentPosition();
    } catch (_) { return const Position(latitude: -7.5, longitude: 112.0, timestamp: null, accuracy: 0, altitude: 0, altitudeAccuracy: 0, heading: 0, headingAccuracy: 0, speed: 0, speedAccuracy: 0); }
  }

  BuildContext get ctx => context;

  @override Widget build(BuildContext context) {
    final t = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Image.asset('assets/images/logo_lockup.png', height: 28),
        actions: [
          IconButton(icon: const Icon(Icons.notifications_outlined, size: 22), onPressed: () => ctx.push('/notifications')),
          IconButton(icon: const Icon(Icons.favorite_outline, size: 22), onPressed: () => ctx.push('/favorites')),
        ],
      ),
      body: _loading ? _skeleton() : _error != null ? _err(t) : RefreshIndicator(onRefresh: _load, child: CustomScrollView(slivers: [
        SliverToBoxAdapter(child: _hero(t)),
        if (_listings.isEmpty && _requests.isEmpty) SliverFillRemaining(child: _empty()),
        SliverToBoxAdapter(child: _sectionHeader('Trending', null)),
        SliverToBoxAdapter(child: SizedBox(height: 200, child: ListView.builder(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16), itemCount: (_listings.take(6)).length, itemBuilder: (_, i) => _cardWide(_listings[i])))),
        SliverToBoxAdapter(child: _sectionHeader('\u{1F4CD} Terdekat', () => ctx.push('/search'))),
        SliverList(delegate: SliverChildBuilderDelegate((_, i) => _cardRow(_listings[i]), childCount: _listings.length)),
        if (_requests.isNotEmpty) ...[
          SliverToBoxAdapter(child: _sectionHeader('Kebutuhan Sekitar', null)),
          SliverList(delegate: SliverChildBuilderDelegate((_, i) => _reqCard(_requests[i]), childCount: _requests.take(3).length)),
        ],
        const SliverToBoxAdapter(child: SizedBox(height: 100)),
      ])),
    );
  }

  Widget _hero(ThemeData t) => Padding(
    padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
    child: ClipRRect(
      borderRadius: BorderRadius.circular(28),
      child: Container(
        height: 200,
        decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Colors.green.shade700, Colors.green.shade500, const Color(0xFF34D399)])),
        child: Stack(children: [
          Positioned(right: -40, top: -40, child: Container(width: 200, height: 200, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withOpacity(0.08)))),
          Positioned(left: -20, bottom: -60, child: Container(width: 160, height: 160, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.black.withOpacity(0.06)))),
          Padding(padding: const EdgeInsets.all(24), child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
            Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(20)), child: const Text('Pasar Lokal Satu Kabupaten', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 1.2))),
            const Spacer(),
            Text('Yang kamu\nbutuhkan, ada\ndi sekitar.', style: TextStyle(fontSize: 26, height: 1.15, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: -0.5, shadows: [Shadow(color: Colors.black.withOpacity(0.15), blurRadius: 4)])),
            const SizedBox(height: 16),
            Row(children: [_pill('Barang'), const SizedBox(width: 8), _pill('Jasa'), const SizedBox(width: 8), _pill('Sewa')]),
          ])),
        ]),
      ),
    ),
  );

  Widget _pill(String label) => Container(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8), decoration: BoxDecoration(color: Colors.white.withOpacity(0.18), borderRadius: BorderRadius.circular(24)), child: Text(label, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)));

  Widget _sectionHeader(String title, VoidCallback? onTap) => Padding(padding: const EdgeInsets.fromLTRB(20, 28, 20, 10), child: Row(children: [
    Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.3)),
    const Spacer(),
    if (onTap != null) GestureDetector(onTap: onTap, child: Text('Lihat semua', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Theme.of(context).colorScheme.primary))),
  ]));

  Widget _cardWide(Listing l) => GestureDetector(
    onTap: () => ctx.push('/listing/${l.id}', extra: l),
    child: Container(
      width: 260, margin: const EdgeInsets.only(right: 14),
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(24), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 16, offset: const Offset(0, 6))]),
      child: ClipRRect(borderRadius: BorderRadius.circular(24), child: Stack(fit: StackFit.expand, children: [
        l.images.isNotEmpty ? Image.network(l.images.first, fit: BoxFit.cover, errorBuilder: (_,__,___) => _imgPlaceholder()) : _imgPlaceholder(),
        Positioned.fill(child: Container(decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.transparent, Colors.black.withOpacity(0.7)])))),
        Positioned(top: 12, left: 12, child: Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5), decoration: BoxDecoration(color: Colors.white.withOpacity(0.9), borderRadius: BorderRadius.circular(14)), child: Text(l.typeLabel, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.primary)))),
        Positioned(bottom: 16, left: 16, right: 16, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(l.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700, height: 1.2)),
          const SizedBox(height: 6),
          Text(l.priceDisplay, style: const TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w600)),
        ])),
      ])),
    ),
  );

  Widget _cardRow(Listing l) => Card(
    margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
    child: InkWell(
      borderRadius: BorderRadius.circular(24),
      onTap: () => ctx.push('/listing/${l.id}', extra: l),
      child: Padding(padding: const EdgeInsets.all(14), child: Row(children: [
        ClipRRect(borderRadius: BorderRadius.circular(16), child: l.images.isNotEmpty ? Image.network(l.images.first, width: 72, height: 72, fit: BoxFit.cover, errorBuilder: (_,__,___) => _tinyPlaceholder()) : _tinyPlaceholder()),
        const SizedBox(width: 14),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(l.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
          const SizedBox(height: 4),
          Text(l.storeName ?? '', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
          const SizedBox(height: 4),
          Text(l.priceDisplay, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Theme.of(context).colorScheme.primary)),
        ])),
      ])),
    ),
  );

  Widget _reqCard(CustomerRequest r) => Card(
    margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
    child: ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      leading: CircleAvatar(radius: 24, backgroundColor: Colors.orange.shade50, child: Text(r.userInitials ?? '?', style: TextStyle(fontSize: 14, color: Colors.orange.shade700, fontWeight: FontWeight.w700))),
      title: Text(r.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
      subtitle: Row(children: [Icon(Icons.timer, size: 14, color: r.isExpired ? Colors.red : Colors.orange.shade700), const SizedBox(width: 4), Text(r.timeLeft, style: TextStyle(color: r.isExpired ? Colors.red : Colors.orange.shade700, fontSize: 12, fontWeight: FontWeight.w600))]),
      trailing: Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: Colors.orange.shade50, borderRadius: BorderRadius.circular(16)), child: Text('${r.offersCount} tawaran', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.orange.shade700))),
      onTap: () => ctx.push('/request/${r.id}', extra: r),
    ),
  );

  Widget _imgPlaceholder() => Container(decoration: BoxDecoration(gradient: LinearGradient(colors: [Colors.green.shade100, Colors.green.shade50])));
  Widget _tinyPlaceholder() => Container(width: 72, height: 72, decoration: BoxDecoration(borderRadius: BorderRadius.circular(16), gradient: LinearGradient(colors: [Colors.green.shade100, Colors.green.shade50])), child: const Icon(Icons.image, color: Colors.green, size: 28));

  Widget _skeleton() => ListView(padding: EdgeInsets.zero, children: [
    Padding(padding: const EdgeInsets.all(16), child: Container(height: 200, decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(28)))),
    const SizedBox(height: 8),
    Padding(padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12), child: Container(height: 18, width: 140, decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(8)))),
    ...List.generate(4, (_) => Padding(padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), child: Container(height: 100, decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(24))))),
  ]);

  Widget _empty() => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
    Container(width: 100, height: 100, decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(32)), child: const Icon(Icons.store_mall_directory, size: 44, color: Color(0xFF168A4A))),
    const SizedBox(height: 20),
    const Text('Belum ada listing', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
    const SizedBox(height: 6),
    const Text('Tarik ke bawah untuk memuat ulang', style: TextStyle(color: Colors.grey)),
    const SizedBox(height: 16),
    ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh, size: 18), label: const Text('Muat Ulang')),
  ]));

  Widget _err(ThemeData t) => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
    Container(width: 80, height: 80, decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(28)), child: Icon(Icons.cloud_off, size: 36, color: Colors.red.shade300)),
    const SizedBox(height: 16),
    const Text('Gagal memuat', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700)),
    const SizedBox(height: 20),
    ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh, size: 18), label: const Text('Coba Lagi')),
  ]));
}
