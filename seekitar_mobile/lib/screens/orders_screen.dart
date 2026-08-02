import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../core/constants.dart';
import '../models/order.dart';
import '../services/api_compat.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});
  @override State<OrdersScreen> createState() => _OrdersScreenState();
}
class _OrdersScreenState extends State<OrdersScreen> with SingleTickerProviderStateMixin {
  final _api = ApiProvider();
  late TabController _tab; List<Order> _b = [], _s = []; bool _loading = true; String? _error;
  BuildContext get ctx => context;
  @override void initState() { super.initState(); _tab = TabController(length: 2, vsync: this); _load(); }
  @override void dispose() { _tab.dispose(); super.dispose(); }
  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final results = await Future.wait<Object>([_api.getOrders(role: 'buyer'), _api.getOrders(role: 'seller')]);
      final buyerOrders = results[0] as List<Order>;
      final sellerOrders = results[1] as List<Order>;
      if (mounted) setState(() { _b = buyerOrders; _s = sellerOrders; _loading = false; });
    }
    catch (e) { if (mounted) setState(() { _error = e.toString(); _loading = false; }); }
  }
  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Pesanan'), bottom: TabBar(controller: _tab, tabs: const [Tab(text: 'Pembelian'), Tab(text: 'Penjualan')])),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _error != null ? _err() : TabBarView(controller: _tab, children: [_list(_b, false), _list(_s, true)]),
  );
  Widget _err() => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [const Icon(Icons.cloud_off, size: 48, color: Colors.grey), const SizedBox(height: 12), Text(_error!, style: const TextStyle(color: Colors.grey)), const SizedBox(height: 16), ElevatedButton.icon(onPressed: _load, icon: const Icon(Icons.refresh), label: const Text('Coba Lagi'))]));
  Widget _list(List<Order> orders, bool seller) => orders.isEmpty ? const Center(child: Text('Belum ada pesanan', style: TextStyle(color: Colors.grey))) : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: orders.length, itemBuilder: (_, i) {
    final o = orders[i];
    return Card(margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 5), child: InkWell(borderRadius: BorderRadius.circular(24), onTap: () async { await ctx.push('/order-detail/${o.id}', extra: o); if (mounted) _load(); }, child: Padding(padding: const EdgeInsets.all(14), child: Row(children: [
      ClipRRect(borderRadius: BorderRadius.circular(14), child: (o.listingImages?.isNotEmpty == true) ? Image.network(o.listingImages!.first, width: 60, height: 60, fit: BoxFit.cover) : Container(width: 60, height: 60, color: Colors.green.shade50, child: const Icon(Icons.receipt, color: Colors.green))),
      const SizedBox(width: 14),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(o.listingTitle ?? 'Pesanan #${o.id.substring(0, 8)}', maxLines: 1, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
        const SizedBox(height: 4), Text(seller ? 'Pembeli: ${o.buyerName ?? '-'}' : 'Toko: ${o.storeName ?? '-'}', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
        const SizedBox(height: 4), Text(o.priceDisplay, style: TextStyle(fontWeight: FontWeight.w800, fontSize: 14, color: Theme.of(context).colorScheme.primary)),
      ])),
      const SizedBox(width: 8),
      Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5), decoration: BoxDecoration(color: AppConstants.orderStatusColor(o.status).withOpacity(0.1), borderRadius: BorderRadius.circular(12)), child: Text(o.displayStatusLabel, style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: AppConstants.orderStatusColor(o.status)))),
    ]))));
  }));
}
