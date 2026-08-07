import 'dart:math' as math;

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme.dart';
import '../../models/customer_request.dart';
import '../../models/listing.dart';
import '../../services/api_compat.dart';

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
  String _regency = '';

  /// Fallback saat GPS tidak tersedia: pusat Kabupaten Pasuruan.
  static const double _fallbackLat = -7.5;
  static const double _fallbackLng = 112.0;

  /// Gambar hero beranda — disalin dari server `public/img/web/hero-baru.jpg`
  /// ke aset aplikasi (`assets/images/hero.jpg`) agar tampil tanpa jaringan.
  static const String _heroAsset = 'assets/images/hero.jpg';

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
        if (lRaw is List) _listings = lRaw.map((d) => Listing.fromJson(d as Map<String,dynamic>)).toList();
        if (rRaw is List) _requests = rRaw.map((d) => CustomerRequest.fromJson(d as Map<String,dynamic>)).toList();
        _loading = false;
      });
      // Muat nama kabupaten dari /config — gagal tidak menggagalkan feed.
      _loadConfig();
    } catch (e) {
      if (!mounted) return;
      setState(() { _error = _friendlyError(e); _loading = false; });
    }
  }

  Future<void> _loadConfig() async {
    try {
      final c = await _api.config();
      if (mounted) setState(() => _regency = c['regency']?.toString() ?? '');
    } catch (_) {}
  }

  /// Pesan error yang bisa dibaca pengguna; detail teknis hanya di debug.
  String _friendlyError(Object e) {
    if (kDebugMode) return e.toString();
    final s = e.toString();
    if (s.contains('401')) return 'Sesi berakhir. Silakan masuk kembali.';
    if (s.contains('Connection') || s.contains('SocketException') || s.contains('Timeout')) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet.';
    }
    return 'Terjadi kesalahan saat memuat data.';
  }

  Future<Position> _determinePosition() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) return _fallbackPosition();
      var p = await Geolocator.checkPermission();
      if (p == LocationPermission.denied) p = await Geolocator.requestPermission();
      if (p == LocationPermission.denied || p == LocationPermission.deniedForever) {
        return _fallbackPosition();
      }
      // Timeout agar layar tidak menggantung saat GPS lambat/indoor.
      return await Geolocator.getCurrentPosition()
          .timeout(const Duration(seconds: 15));
    } catch (_) {
      return _fallbackPosition();
    }
  }

  Position _fallbackPosition() => Position(
        latitude: _fallbackLat,
        longitude: _fallbackLng,
        timestamp: DateTime.now(),
        accuracy: 0,
        altitude: 0,
        altitudeAccuracy: 0,
        heading: 0,
        headingAccuracy: 0,
        speed: 0,
        speedAccuracy: 0,
      );

  @override Widget build(BuildContext context) {
    final t = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Image.asset('assets/images/logo_lockup.png', height: 28), actions: [
        IconButton(icon: const Icon(Icons.category_outlined, size: 22), onPressed: () => context.push('/categories')),
        IconButton(icon: const Icon(Icons.notifications_outlined, size: 22), onPressed: () => context.push('/notifications')),
        IconButton(icon: const Icon(Icons.favorite_outline, size: 22), onPressed: () => context.push('/favorites')),
      ]),
      body: _loading ? _skeleton() : _error != null ? _err(t) : RefreshIndicator(onRefresh: _load, child: CustomScrollView(slivers: [
        SliverToBoxAdapter(child: _hero(t)),
        if (_regency.isNotEmpty) SliverToBoxAdapter(child: _statsRow(t)),
        if (_listings.isEmpty && _requests.isEmpty) SliverFillRemaining(child: _empty()),
        if (_listings.isNotEmpty) ...[
          SliverToBoxAdapter(child: _sectionHeader('Trending', null)),
          SliverToBoxAdapter(child: SizedBox(height: 200, child: ListView.builder(scrollDirection: Axis.horizontal, padding: const EdgeInsets.symmetric(horizontal: 16), itemCount: math.min(6, _listings.length), itemBuilder: (_, i) => _cardWide(_listings[i])))),
          SliverToBoxAdapter(child: _sectionHeader('Terdekat', () => context.push('/search'))),
          SliverList(delegate: SliverChildBuilderDelegate((_, i) => _cardRow(_listings[i]), childCount: _listings.length)),
        ],
        if (_requests.isNotEmpty) ...[
          SliverToBoxAdapter(child: _sectionHeader('Kebutuhan Sekitar', null)),
          SliverList(delegate: SliverChildBuilderDelegate((_, i) => _reqCard(_requests[i]), childCount: math.min(3, _requests.length))),
        ],
        const SliverToBoxAdapter(child: SizedBox(height: 32)),
      ])),
    );
  }

  // ── HERO ──
  Widget _hero(ThemeData t) => Padding(
    padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
    child: ClipRRect(borderRadius: BorderRadius.circular(28), child: SizedBox(
      height: 200,
      child: Stack(fit: StackFit.expand, children: [
        // Latar: hero-baru.jpg (disalin ke aset aplikasi — tanpa jaringan).
        Image.asset(_heroAsset, fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => _heroFallback()),
        // Overlay agar teks tetap terbaca di atas gambar yang terang.
        DecoratedBox(decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.centerLeft,
            end: Alignment.centerRight,
            colors: [
              Colors.black.withOpacity(0.62),
              Colors.black.withOpacity(0.30),
              Colors.transparent,
            ],
          ),
        )),
        DecoratedBox(decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Colors.transparent, Colors.black.withOpacity(0.25)],
          ),
        )),
        // Isi hero dibungkus Center + FittedBox(scaleDown) agar TIDAK pernah
        // overflow: konten (±160px) semula lebih tinggi dari ruang dalam
        // 200-48=152px sehingga RenderFlex meluap ~13px. FittedBox mengecilkan
        // konten otomatis bila ruang kurang, termasuk saat textScaleFactor
        // sistem diperbesar.
        Positioned(
          left: 24,
          right: 24,
          top: 16,
          bottom: 16,
          child: FittedBox(
            fit: BoxFit.scaleDown,
            alignment: Alignment.centerLeft,
            child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: Colors.black.withOpacity(0.35), borderRadius: BorderRadius.circular(20)), child: Text(_regency.isNotEmpty ? 'Pasar Lokal $_regency' : 'Pasar Lokal Satu Kabupaten', style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 1.2))),
                  const SizedBox(height: 14),
                  Text('Yang kamu\nbutuhkan, ada\ndi sekitar.', style: TextStyle(fontSize: 26, height: 1.15, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: -0.5, shadows: [Shadow(color: Colors.black.withOpacity(0.35), blurRadius: 6), Shadow(color: Colors.black.withOpacity(0.2), blurRadius: 2, offset: const Offset(0, 1))])),
                  const SizedBox(height: 14),
                  Row(children: [_pill('Barang'), const SizedBox(width: 8), _pill('Jasa'), const SizedBox(width: 8), _pill('Sewa')]),
                ],
              ),
            ),
          ),
      ]),
    )),
  );

  /// Fallback hero bila gambar server tidak bisa dimuat (offline/dns) —
  /// tetap memakai gradien hijau brand + ornamen, bukan area kosong.
  Widget _heroFallback() => DecoratedBox(decoration: BoxDecoration(
    gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [AppTheme.primary, const Color(0xFF1FA05A), const Color(0xFF34D399)]),
  ), child: Stack(children: [
    Positioned(right: -40, top: -40, child: Container(width: 200, height: 200, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withOpacity(0.08)))),
    Positioned(left: -20, bottom: -60, child: Container(width: 160, height: 160, decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.black.withOpacity(0.06)))),
  ]));

  Widget _pill(String label) => Container(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8), decoration: BoxDecoration(color: Colors.black.withOpacity(0.35), borderRadius: BorderRadius.circular(24)), child: Text(label, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)));

  // ── STATS ROW ──
  Widget _statsRow(ThemeData t) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
    child: Row(children: [
      _stat(Icons.verified, '${_listings.length}', 'listing di sekitarmu'),
      const SizedBox(width: 10),
      _stat(Icons.location_on, '1', 'kabupaten'),
      const SizedBox(width: 10),
      _stat(Icons.wallet, 'Gratis', 'tanpa komisi'),
    ]),
  );

  Widget _stat(IconData icon, String value, String label) => Expanded(child: Container(padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 10), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)]), child: Row(children: [
    Container(width: 32, height: 32, decoration: BoxDecoration(color: AppTheme.primarySubtle, borderRadius: BorderRadius.circular(8)), child: Icon(icon, size: 16, color: Theme.of(context).colorScheme.primary)),
    const SizedBox(width: 8),
    Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(value, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Colors.grey.shade800)),
      Text(label, style: TextStyle(fontSize: 9, color: Colors.grey.shade500)),
    ]),
  ])));

  // ── SECTION HEADER ──
  Widget _sectionHeader(String title, VoidCallback? onTap) => Padding(padding: const EdgeInsets.fromLTRB(20, 28, 20, 10), child: Row(children: [
    Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.3)),
    const Spacer(),
    if (onTap != null) GestureDetector(onTap: onTap, child: Text('Lihat semua', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Theme.of(context).colorScheme.primary))),
  ]));

  // ── CARDS ──
  Widget _cardWide(Listing l) => GestureDetector(
    onTap: () => context.push('/listing/${l.id}', extra: l),
    child: Container(width: 260, margin: const EdgeInsets.only(right: 14), decoration: BoxDecoration(borderRadius: BorderRadius.circular(24), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 16, offset: const Offset(0, 6))]),
      child: ClipRRect(borderRadius: BorderRadius.circular(24), child: Stack(fit: StackFit.expand, children: [
        l.images.isNotEmpty ? _networkImage(l.images.first, fit: BoxFit.cover, ph: _ph()) : _ph(),
        Positioned.fill(child: Container(decoration: BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.transparent, Colors.black.withOpacity(0.7)])))),
        Positioned(top: 12, left: 12, child: Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5), decoration: BoxDecoration(color: Colors.white.withOpacity(0.9), borderRadius: BorderRadius.circular(14)), child: Text(l.typeLabel, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.primary)))),
        Positioned(bottom: 16, left: 16, right: 16, child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(l.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700, height: 1.2)),
          const SizedBox(height: 6), Text(l.priceDisplay, style: const TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w600)),
        ])),
      ]))),
  );

  Widget _cardRow(Listing l) => Card(margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5), child: InkWell(borderRadius: BorderRadius.circular(24), onTap: () => context.push('/listing/${l.id}', extra: l), child: Padding(padding: const EdgeInsets.all(14), child: Row(children: [
    ClipRRect(borderRadius: BorderRadius.circular(16), child: l.images.isNotEmpty ? _networkImage(l.images.first, width: 72, height: 72, fit: BoxFit.cover, ph: _tinyPh()) : _tinyPh()),
    const SizedBox(width: 14),
    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(l.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
      const SizedBox(height: 4), Text(l.storeName ?? '', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
      const SizedBox(height: 4), Text(l.priceDisplay, style: TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Theme.of(context).colorScheme.primary)),
    ])),
  ]))));

  Widget _reqCard(CustomerRequest r) => Card(margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5), child: ListTile(
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
    leading: CircleAvatar(radius: 24, backgroundColor: Colors.orange.shade50, child: Text(r.initials, style: TextStyle(fontSize: 14, color: Colors.orange.shade700, fontWeight: FontWeight.w700))),
    title: Text(r.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
    subtitle: Row(children: [Icon(Icons.timer, size: 14, color: r.isExpired ? Colors.red : Colors.orange.shade700), const SizedBox(width: 4), Text(r.timeLeft, style: TextStyle(color: r.isExpired ? Colors.red : Colors.orange.shade700, fontSize: 12, fontWeight: FontWeight.w600))]),
    trailing: Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: Colors.orange.shade50, borderRadius: BorderRadius.circular(16)), child: Text('${r.offersCount} tawaran', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Colors.orange.shade700))),
    onTap: () => context.push('/request/${r.id}', extra: r),
  ));

  /// Gambar jaringan dengan placeholder saat loading & saat gagal.
  Widget _networkImage(String url, {double? width, double? height, BoxFit fit = BoxFit.cover, required Widget ph, int? cacheWidth}) {
    final img = Image.network(
      url,
      width: width, height: height, fit: fit,
      // Ukuran cache disesuaikan: 480 px cukup untuk kartu kecil, hero
      // memakai 1170 px (≈ lebar layar 390dp × 3x) agar tetap tajam.
      cacheWidth: cacheWidth ?? 480,
      loadingBuilder: (_, child, progress) => progress == null ? child : ph,
      errorBuilder: (_, __, ___) => ph,
    );
    return width == null && height == null ? img : SizedBox(width: width, height: height, child: img);
  }

  Widget _ph() => Container(decoration: BoxDecoration(gradient: LinearGradient(colors: [Colors.green.shade100, Colors.green.shade50])));
  Widget _tinyPh() => Container(width: 72, height: 72, decoration: BoxDecoration(borderRadius: BorderRadius.circular(16), gradient: LinearGradient(colors: [Colors.green.shade100, Colors.green.shade50])), child: const Icon(Icons.image, color: Colors.green, size: 28));

  // ── SKELETON / EMPTY / ERROR ──
  Widget _skeleton() => ListView(padding: EdgeInsets.zero, children: [
    Padding(padding: const EdgeInsets.all(16), child: Container(height: 200, decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(28)))),
    const SizedBox(height: 8),
    Padding(padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12), child: Container(height: 18, width: 140, decoration: BoxDecoration(color: Colors.grey.shade200, borderRadius: BorderRadius.circular(8)))),
    ...List.generate(4, (_) => Padding(padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), child: Container(height: 100, decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(24))))),
  ]);

  Widget _empty() => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
    Container(width: 100, height: 100, decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(32)), child: const Icon(Icons.store_mall_directory, size: 44, color: Color(0xFF168A4A))),
    const SizedBox(height: 20),
    const Text('Belum ada listing atau kebutuhan', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
    const SizedBox(height: 6),
    const Text('Tarik ke bawah untuk memuat ulang', style: TextStyle(color: Colors.grey)),
    const SizedBox(height: 16),
    ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh, size: 18), label: const Text('Muat Ulang')),
  ]));

  Widget _err(ThemeData t) => Center(child: SingleChildScrollView(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [
    Container(width: 80, height: 80, decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(28)), child: Icon(Icons.cloud_off, size: 36, color: Colors.red.shade300)),
    const SizedBox(height: 16),
    const Text('Gagal memuat', style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700)),
    if (_error != null) ...[
      const SizedBox(height: 8),
      Text(_error!, textAlign: TextAlign.center, style: TextStyle(fontSize: 13, color: Colors.grey.shade600, height: 1.4)),
    ],
    const SizedBox(height: 20),
    ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh, size: 18), label: const Text('Coba Lagi')),
  ])));
}
