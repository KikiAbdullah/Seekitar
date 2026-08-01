import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import '../core/constants.dart';
import '../models/order.dart';
import '../services/api_compat.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});
  @override State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> with SingleTickerProviderStateMixin {
  final _api = ApiProvider();
  late TabController _tab; List<Order> _b = [], _s = []; bool _loading = true;

  @override void initState() { super.initState(); _tab = TabController(length: 2, vsync: this); _load(); }
  Future<void> _load() async {
    setState(() => _loading = true);
    try { final r = await Future.wait([_api.getOrders(role: 'buyer'), _api.getOrders(role: 'seller')]); if (mounted) setState(() { _b = r[0]; _s = r[1]; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Pesanan'), bottom: TabBar(controller: _tab, tabs: const [Tab(text: 'Pembelian'), Tab(text: 'Penjualan')])),
    body: _loading ? const Center(child: CircularProgressIndicator()) : TabBarView(controller: _tab, children: [_list(_b, false), _list(_s, true)]),
  );

  Widget _list(List<Order> orders, bool seller) => orders.isEmpty ? const Center(child: Text('Belum ada pesanan', style: TextStyle(color: Colors.grey))) : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: orders.length, itemBuilder: (_, i) {
    final o = orders[i];
    return Card(margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5), child: InkWell(borderRadius: BorderRadius.circular(24), onTap: () => ctx.push('/order-detail', extra: o).then((_) => _load()), child: Padding(padding: const EdgeInsets.all(14), child: Row(children: [
      ClipRRect(borderRadius: BorderRadius.circular(14), child: (o.listingImages?.isNotEmpty == true) ? Image.network(o.listingImages!.first, width: 60, height: 60, fit: BoxFit.cover) : Container(width: 60, height: 60, color: Colors.green.shade50, child: const Icon(Icons.receipt, color: Colors.green))),
      const SizedBox(width: 14),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(o.listingTitle ?? 'Pesanan #${o.id.substring(0, 8)}', maxLines: 1, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
        const SizedBox(height: 4),
        Text(seller ? 'Pembeli: ${o.buyerName ?? '-'}' : 'Toko: ${o.storeName ?? '-'}', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
        const SizedBox(height: 4),
        Text(o.priceDisplay, style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14, color: Theme.of(context).colorScheme.primary)),
      ])),
      const SizedBox(width: 8),
      Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5), decoration: BoxDecoration(color: AppConstants.orderStatusColor(o.status).withOpacity(0.1), borderRadius: BorderRadius.circular(12)), child: Text(o.statusLabel, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppConstants.orderStatusColor(o.status)))),
    ]))));
  }));
}

class OrdDetail extends StatefulWidget { final Order order; const OrdDetail({required this.order}); @override State<OrdDetail> createState() => _OrdDetailState(); }
class _OrdDetailState extends State<OrdDetail> {
  final _api = ApiProvider();

  Future<void> _update(String s) async { try { await _api.updateOrderStatus(widget.order.id, s); if (mounted) Navigator.pop(context); } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()))); } }

  Future<void> _review() async {
    int r = 5; final c = TextEditingController();
    showModalBottomSheet(context: context, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))), builder: (ctx) => Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [
      Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))), const SizedBox(height: 20),
      const Text('Beri Ulasan', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
      const SizedBox(height: 16), RatingBar.builder(initialRating: 5, minRating: 1, itemSize: 40, itemBuilder: (_,__) => const Icon(Icons.star, color: Colors.amber), onRatingUpdate: (v) => r = v.toInt()),
      const SizedBox(height: 16), TextField(controller: c, maxLines: 3, decoration: const InputDecoration(labelText: 'Komentar (opsional)')),
      const SizedBox(height: 24), ElevatedButton(onPressed: () async { Navigator.pop(ctx); await _api.submitReview(widget.order.id, r, comment: c.text.isNotEmpty ? c.text : null); if (mounted) Navigator.pop(context); }, child: const Text('Kirim')),
    ])));
  }

  @override Widget build(BuildContext ctx) {
    final o = widget.order; final t = Theme.of(ctx);
    return Scaffold(
      appBar: AppBar(title: Text('Pesanan #${(o.orderNumber ?? o.id).substring(0, 8)}')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)), child: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [Text(o.listingTitle ?? 'Pesanan', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)), const Spacer(), Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: AppConstants.orderStatusColor(o.status).withOpacity(0.1), borderRadius: BorderRadius.circular(14)), child: Text(o.statusLabel, style: TextStyle(fontWeight: FontWeight.w700, color: AppConstants.orderStatusColor(o.status))))]),
          const SizedBox(height: 16), Text(o.priceDisplay, style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: t.colorScheme.primary)),
          const SizedBox(height: 16), _r('Toko', o.storeName ?? '-'), _r('Pembeli', o.buyerName ?? '-'), _r('Jumlah', '${o.quantity}'), _r('Pembayaran', o.paymentMethod ?? '-'), if (o.notes?.isNotEmpty == true) _r('Catatan', o.notes!),
        ]))),
        if (o.status == 'menunggu_konfirmasi' || o.status == 'diproses') Padding(padding: const EdgeInsets.only(top: 16), child: Row(children: [if (o.status == 'menunggu_konfirmasi') Expanded(child: ElevatedButton(onPressed: () => _update('diproses'), style: ElevatedButton.styleFrom(backgroundColor: Colors.blue), child: const Text('Proses'))), const SizedBox(width: 12), Expanded(child: OutlinedButton(onPressed: () => _update('selesai'), child: const Text('Selesai')))])),
        if (o.status == 'selesai') Padding(padding: const EdgeInsets.only(top: 16), child: ElevatedButton.icon(onPressed: _review, icon: const Icon(Icons.star, size: 20), label: const Text('Beri Ulasan'))),
      ]),
    );
  }

  Widget _r(String l, String v) => Padding(padding: const EdgeInsets.symmetric(vertical: 5), child: Row(children: [SizedBox(width: 100, child: Text(l, style: TextStyle(color: Colors.grey.shade500))), Expanded(child: Text(v, style: const TextStyle(fontWeight: FontWeight.w600)))]));
}
