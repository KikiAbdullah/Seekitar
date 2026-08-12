import 'package:flutter/material.dart';
import '../../models/address.dart';
import '../../services/api_compat.dart';
import '../../services/dio_client.dart';
import '../../services/location_service.dart';
import '../../widgets/error_view.dart';

class AddressScreen extends StatefulWidget {
  const AddressScreen({super.key});
  @override State<AddressScreen> createState() => _AddressScreenState();
}

class _AddressScreenState extends State<AddressScreen> {
  final _api = ApiProvider();
  List<UserAddress> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try { final a = await _api.getAddresses(); if (mounted) setState(() { _items = a; _loading = false; }); }
    catch (e) { if (mounted) setState(() { _error = DioClient.friendly(e); _loading = false; }); }
  }

  Future<void> _add() async {
    final labelCtrl = TextEditingController(), addrCtrl = TextEditingController();
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
      title: const Text('Alamat Baru'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(controller: labelCtrl, decoration: const InputDecoration(labelText: 'Label (contoh: Rumah)')),
        const SizedBox(height: 12),
        TextField(controller: addrCtrl, maxLines: 3, decoration: const InputDecoration(labelText: 'Alamat Lengkap')),
      ]),
      actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Simpan'))],
    ));
    if (ok == true) {
      try {
        // Koordinat untuk DISIMPAN — wajib GPS asli; kalau tidak tersedia,
        // JANGAN menyimpan koordinat tebakan yang salah.
        final pos = await locate();
        if (pos == null) {
          if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Aktifkan GPS untuk menyimpan alamat.')));
          return;
        }
        await _api.createAddress({'label': labelCtrl.text, 'address': addrCtrl.text, 'latitude': pos.latitude, 'longitude': pos.longitude});
        if (mounted) await _load();
      } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: ${DioClient.friendly(e)}'))); }
    }
  }

  /// Hapus alamat dengan konfirmasi — aksi destruktif tidak boleh langsung.
  Future<void> _confirmDelete(UserAddress a) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus Alamat?'),
        content: Text('Hapus alamat "${a.label}"?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
          ElevatedButton(onPressed: () => Navigator.pop(ctx, true), style: ElevatedButton.styleFrom(backgroundColor: Colors.red), child: const Text('Hapus')),
        ],
      ),
    );
    if (ok == true && mounted) {
      try { await _api.deleteAddress(a.id); if (mounted) await _load(); }
      catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(DioClient.friendly(e)))); }
    }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Alamat')),
    floatingActionButton: FloatingActionButton(onPressed: _add, child: const Icon(Icons.add)),
    body: _loading ? const Center(child: CircularProgressIndicator())
      : _error != null ? ErrorView(message: _error!, onRetry: _load)
      : _items.isEmpty ? const Center(child: Text('Belum ada alamat'))
      : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
      final a = _items[i];
      return Card(child: ListTile(
        leading: Icon(a.isDefault ? Icons.location_on : Icons.location_on_outlined, color: a.isDefault ? Colors.green : Colors.grey),
        title: Row(children: [Text(a.label, style: const TextStyle(fontWeight: FontWeight.w600)), if (a.isDefault) const SizedBox(width: 8), if (a.isDefault) Container(padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2), decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(6)), child: const Text('Utama', style: TextStyle(fontSize: 10, color: Colors.green)))]),
        subtitle: Text(a.address, maxLines: 2),
        trailing: PopupMenuButton<String>(onSelected: (v) async {
          try {
            if (v == 'default') { await _api.setDefaultAddress(a.id); if (mounted) await _load(); }
            if (v == 'delete') { await _confirmDelete(a); }
          } catch (e) {
            if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(DioClient.friendly(e))));
          }
        }, itemBuilder: (_) => [
          if (!a.isDefault) const PopupMenuItem(value: 'default', child: Text('Jadikan Utama')),
          const PopupMenuItem(value: 'delete', child: Text('Hapus', style: TextStyle(color: Colors.red))),
        ]),
      ));
    })),
  );
}
