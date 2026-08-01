import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:geolocator/geolocator.dart';
import '../providers/app_state.dart';
import '../services/api_compat.dart';
import '../models/store.dart';

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

  Future<void> _editProfile() async {
    final app = context.read<AppState>();
    final nc = TextEditingController(text: app.user?.name);
    final ac = TextEditingController(text: app.user?.address);
    showModalBottomSheet(context: context, isScrollControlled: true, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))), builder: (ctx) => Padding(padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom), child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))), const SizedBox(height: 20),
      const Text('Edit Profil', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)), const SizedBox(height: 16),
      TextField(controller: nc, decoration: const InputDecoration(labelText: 'Nama')), const SizedBox(height: 12),
      TextField(controller: ac, decoration: const InputDecoration(labelText: 'Alamat')), const SizedBox(height: 24),
      ElevatedButton(onPressed: () async { Navigator.pop(ctx); try { final p = await Geolocator.getCurrentPosition(); await app.updateProfile(name: nc.text, address: ac.text, lat: p.latitude, lng: p.longitude); } catch (_) {} }, child: const Text('Simpan')),
    ]))));
  }

  @override Widget build(BuildContext ctx) {
    final u = ctx.watch<AppState>().user; final t = Theme.of(ctx);
    return Scaffold(
      appBar: AppBar(title: const Text('Profil'), actions: [IconButton(icon: const Icon(Icons.settings_outlined, size: 22), onPressed: () => ctx.push('/settings'))]),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Card(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          child: Padding(padding: const EdgeInsets.all(20), child: Row(children: [
            Container(width: 68, height: 68, decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(22)), child: Center(child: Text(u?.displayAvatar ?? '?', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: t.colorScheme.primary)))),
            const SizedBox(width: 16),
            Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(u?.name ?? 'Lengkapi Profil', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 19)),
              const SizedBox(height: 2),
              Text(u?.phone ?? '', style: TextStyle(fontSize: 13, color: Colors.grey.shade500)),
              if (u?.verifiedAt != null) ...[const SizedBox(height: 4), Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4), decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(10)), child: const Row(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.verified, size: 14, color: Colors.green), SizedBox(width: 4), Text('Terverifikasi', style: TextStyle(fontSize: 11, color: Colors.green, fontWeight: FontWeight.w700))]))],
            ])),
            IconButton(icon: const Icon(Icons.edit_outlined, size: 22), onPressed: _editProfile),
          ])),
        ),
        const SizedBox(height: 20),
        Padding(padding: const EdgeInsets.only(left: 4), child: Text('Toko Saya', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800, fontSize: 17))),
        const SizedBox(height: 8),
        if (_loadingStores) const Center(child: CircularProgressIndicator()),
        ..._stores.map((s) => Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: ListTile(contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6), leading: CircleAvatar(radius: 26, backgroundColor: Colors.green.shade50, child: Text(s.name[0].toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.w800, fontSize: 16))), title: Row(children: [Text(s.name, style: const TextStyle(fontWeight: FontWeight.w700)), if (s.isVerified) const Padding(padding: EdgeInsets.only(left: 4), child: Icon(Icons.verified, size: 16, color: Colors.green))]), subtitle: Text('⭐ ${s.ratingAvg.toStringAsFixed(1)} · ${s.reviewsCount} ulasan', style: const TextStyle(fontSize: 12)), trailing: const Icon(Icons.chevron_right), onTap: () => ctx.push('/store/${s.id}/dashboard')))),
        if (_stores.isEmpty && !_loadingStores) Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: Padding(padding: const EdgeInsets.all(32), child: Center(child: Column(children: [
          Container(width: 64, height: 64, decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(22)), child: const Icon(Icons.store, size: 28, color: Color(0xFF168A4A))),
          const SizedBox(height: 14), const Text('Belum punya toko', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          const SizedBox(height: 18), ElevatedButton(onPressed: () => ctx.push('/create-store'), child: const Text('Buka Toko Gratis')),
        ])))),
        const SizedBox(height: 24),
        _tile(Icons.favorite_outline, 'Wishlist', ctx, '/favorites'),
        _tile(Icons.notifications_outlined, 'Notifikasi', ctx, '/notifications'),
        _tile(Icons.wallet_outlined, 'Dompet', ctx, '/wallet'),
        _tile(Icons.location_on_outlined, 'Alamat', ctx, '/addresses'),
        _tile(Icons.chat_outlined, 'Percakapan', ctx, '/conversations'),
        _tile(Icons.explore_outlined, 'Toko Terdekat', ctx, '/stores-nearby'),
        _tile(Icons.verified_user_outlined, 'Verifikasi KTP', ctx, '/verification'),
        _tile(Icons.help_outline, 'Bantuan & Legal', ctx, '/help-legal'),
        _tile(Icons.block_outlined, 'Pengguna Diblokir', ctx, '/blocked'),
        _tile(Icons.settings_outlined, 'Pengaturan Lengkap', ctx, '/settings'),
        const SizedBox(height: 20),
        ListTile(leading: const Icon(Icons.logout, color: Colors.red, size: 22), title: const Text('Keluar', style: TextStyle(color: Colors.red, fontWeight: FontWeight.w600)), onTap: () async { await ctx.read<AppState>().logout(); if (ctx.mounted) ctx.go('/login'); }),
        const SizedBox(height: 40),
      ]),
    );
  }

  Widget _tile(IconData icon, String title, BuildContext ctx, String route) => ListTile(leading: Icon(icon, size: 22), title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 15)), trailing: const Icon(Icons.chevron_right, size: 20, color: Colors.grey), onTap: () => ctx.push(route), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)));
}
