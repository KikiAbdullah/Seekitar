import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import '../../providers/app_state.dart';
import '../../services/api_compat.dart';
import '../../models/store.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});
  @override State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _api = ApiProvider();
  List<Store> _stores = [];
  bool _loadingStores = true;

  @override void initState() { super.initState(); _loadStores(); }

  Future<void> _loadStores() async {
    setState(() => _loadingStores = true);
    try { final s = await _api.mine(); if (mounted) setState(() { _stores = s; _loadingStores = false; }); }
    catch (_) { if (mounted) setState(() => _loadingStores = false); }
  }

  /// Pull-to-refresh: muat ulang data profil & toko dari server. Dipakai
  /// misalnya setelah verifikasi KTP disetujui admin — status "Terverifikasi"
  /// tidak muncul sampai data user di-refresh dari API.
  Future<void> _refresh() async {
    await Future.wait([
      context.read<AppState>().refreshUser(),
      _loadStores(),
    ]);
  }

  Future<void> _editProfile() async {
    final app = context.read<AppState>();
    final nc = TextEditingController(text: app.user?.name);
    final ac = TextEditingController(text: app.user?.address);
    await showModalBottomSheet(context: context, isScrollControlled: true, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))), builder: (ctx) => Padding(padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom), child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))), const SizedBox(height: 20),
      const Text('Edit Profil', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)), const SizedBox(height: 16),
      TextField(controller: nc, decoration: const InputDecoration(labelText: 'Nama')), const SizedBox(height: 12),
      TextField(controller: ac, decoration: const InputDecoration(labelText: 'Alamat')), const SizedBox(height: 24),
      ElevatedButton(onPressed: () async {
        Navigator.pop(ctx);
        double? lat, lng;
        try { final p = await Geolocator.getCurrentPosition(); lat = p.latitude; lng = p.longitude; } catch (_) {}
        try { await app.updateProfile(name: nc.text, address: ac.text, lat: lat, lng: lng); } catch (_) {}
      }, child: const Text('Simpan')),
    ]))));
  }

  @override Widget build(BuildContext ctx) {
    final u = ctx.watch<AppState>().user; final t = Theme.of(ctx);
    return Scaffold(
      appBar: AppBar(title: const Text('Profil'), actions: [IconButton(icon: const Icon(Icons.settings_outlined, size: 22), onPressed: () => ctx.push('/settings'))]),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
        Card(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          child: Padding(padding: const EdgeInsets.all(20), child: Row(children: [
            Container(width: 68, height: 68, decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(22)), child: Center(child: Text(u?.displayAvatar ?? '?', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: t.colorScheme.primary)))),
            const SizedBox(width: 16),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(u?.name ?? 'Lengkapi Profil', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 19)),
              const SizedBox(height: 2),
              Text(u?.phone ?? '', style: TextStyle(fontSize: 13, color: Colors.grey.shade500)),
              if (u?.isVerified == true) ...[
                const SizedBox(height: 4),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(10)),
                  child: const Row(mainAxisSize: MainAxisSize.min, children: [
                    Icon(Icons.verified, size: 14, color: Colors.green),
                    SizedBox(width: 4),
                    Text('Terverifikasi', style: TextStyle(fontSize: 11, color: Colors.green, fontWeight: FontWeight.w700)),
                  ]),
                ),
              ],
            ])),
            IconButton(icon: const Icon(Icons.edit_outlined, size: 22), onPressed: _editProfile),
          ])),
        ),
        if (u != null && u.needsVerification) ...[
          const SizedBox(height: 12),
          _verificationBanner(
            t,
            color: Colors.orange,
            icon: Icons.verified_user_outlined,
            title: 'Verifikasi KTP wajib',
            subtitle: 'Setelah daftar, lengkapi verifikasi identitas agar bisa buka toko dan bertransaksi penuh.',
            action: 'Verifikasi Sekarang',
            onTap: () => ctx.push('/verification'),
          ),
        ] else if (u != null && u.isKtpPending) ...[
          const SizedBox(height: 12),
          _verificationBanner(
            t,
            color: Colors.blue,
            icon: Icons.hourglass_top_outlined,
            title: 'Verifikasi sedang ditinjau',
            subtitle: 'Berkas KTP sudah terkirim. Admin meninjau maksimal 1×24 jam.',
            action: 'Lihat Status',
            onTap: () => ctx.push('/verification'),
          ),
        ],
        const SizedBox(height: 20),
        Padding(padding: const EdgeInsets.only(left: 4), child: Text('Toko Saya', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800, fontSize: 17))),
        const SizedBox(height: 8),
        if (_loadingStores) const Center(child: CircularProgressIndicator()),
        ..._stores.map((s) => Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: ListTile(contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), leading: CircleAvatar(radius: 26, backgroundColor: Colors.green.shade50, child: Text(s.name[0].toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.w800, fontSize: 16))), title: Row(children: [Text(s.name, style: const TextStyle(fontWeight: FontWeight.w700)), if (s.isVerified) const Padding(padding: EdgeInsets.only(left: 4), child: Icon(Icons.verified, size: 16, color: Colors.green))]), subtitle: Text('⭐ ${s.ratingAvg.toStringAsFixed(1)} · ${s.reviewsCount} ulasan', style: const TextStyle(fontSize: 12)), trailing: const Icon(Icons.chevron_right), onTap: () => ctx.push('/store/${s.id}/dashboard')))),
        if (_stores.isEmpty && !_loadingStores) Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: Padding(padding: const EdgeInsets.all(32), child: Center(child: Column(children: [
          Container(width: 64, height: 64, decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(22)), child: const Icon(Icons.store, size: 28, color: Color(0xFF168A4A))),
          const SizedBox(height: 14), const Text('Belum punya toko', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          const SizedBox(height: 18),
          ElevatedButton(
            onPressed: () {
              if (u?.needsVerification == true || u?.isKtpPending == true) {
                ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                  content: Text(u!.needsVerification
                      ? 'Verifikasi KTP wajib dulu sebelum buka toko.'
                      : 'Tunggu verifikasi KTP disetujui admin dulu.'),
                  action: SnackBarAction(label: 'Verifikasi', onPressed: () => ctx.push('/verification')),
                ));
                return;
              }
              ctx.push('/create-store');
            },
            child: const Text('Buka Toko Gratis'),
          ),
        ])))),
        const SizedBox(height: 24),
        _tile(Icons.favorite_outline, 'Wishlist', ctx, '/favorites'),
        _tile(Icons.notifications_outlined, 'Notifikasi', ctx, '/notifications'),
        _tile(Icons.wallet_outlined, 'Dompet', ctx, '/wallet'),
        _tile(Icons.location_on_outlined, 'Alamat', ctx, '/addresses'),
        _tile(Icons.chat_outlined, 'Percakapan', ctx, '/conversations'),
        _tile(Icons.explore_outlined, 'Toko Terdekat', ctx, '/stores-nearby'),
        _verificationTile(ctx, u),
        _tile(Icons.help_outline, 'Bantuan & Legal', ctx, '/help-legal'),
        _tile(Icons.block_outlined, 'Pengguna Diblokir', ctx, '/blocked'),
        _tile(Icons.settings_outlined, 'Pengaturan Lengkap', ctx, '/settings'),
        const SizedBox(height: 20),
        ListTile(leading: const Icon(Icons.logout, color: Colors.red, size: 22), title: const Text('Keluar', style: TextStyle(color: Colors.red, fontWeight: FontWeight.w600)), onTap: () async { await ctx.read<AppState>().logout(); if (ctx.mounted) ctx.go('/login'); }),
        const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _verificationBanner(
    ThemeData t, {
    required MaterialColor color,
    required IconData icon,
    required String title,
    required String subtitle,
    required String action,
    required VoidCallback onTap,
  }) =>
      Material(
        color: color.shade50,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(color: color.shade100, borderRadius: BorderRadius.circular(14)),
                child: Icon(icon, color: color.shade700, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Expanded(child: Text(title, style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: color.shade900))),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(color: color.shade600, borderRadius: BorderRadius.circular(8)),
                      child: const Text('WAJIB', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 0.4)),
                    ),
                  ]),
                  const SizedBox(height: 4),
                  Text(subtitle, style: TextStyle(fontSize: 13, height: 1.35, color: color.shade800)),
                  const SizedBox(height: 10),
                  Text(action, style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: color.shade700)),
                ]),
              ),
              Icon(Icons.chevron_right, color: color.shade400),
            ]),
          ),
        ),
      );

  Widget _verificationTile(BuildContext ctx, dynamic u) {
    Color? badgeColor;
    String? badge;
    if (u?.isVerified == true) {
      badge = 'Selesai';
      badgeColor = Colors.green;
    } else if (u?.isKtpPending == true) {
      badge = 'Ditinjau';
      badgeColor = Colors.blue;
    } else {
      badge = 'Wajib';
      badgeColor = Colors.orange;
    }
    final c = badgeColor!;
    return ListTile(
      leading: const Icon(Icons.verified_user_outlined, size: 22),
      title: Row(children: [
        const Expanded(child: Text('Verifikasi KTP', style: TextStyle(fontWeight: FontWeight.w500, fontSize: 15))),
        if (badge != null)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(color: c.withOpacity(0.12), borderRadius: BorderRadius.circular(8)),
            child: Text(badge, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: c)),
          ),
      ]),
      trailing: const Icon(Icons.chevron_right, size: 20, color: Colors.grey),
      onTap: () => ctx.push('/verification'),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
    );
  }

  Widget _tile(IconData icon, String title, BuildContext ctx, String route) => ListTile(leading: Icon(icon, size: 22), title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 15)), trailing: const Icon(Icons.chevron_right, size: 20, color: Colors.grey), onTap: () => ctx.push(route), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)));
}
