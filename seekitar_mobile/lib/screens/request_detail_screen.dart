import 'package:flutter/material.dart';
import '../models/customer_request.dart';
import '../models/order.dart' show Offer;
import '../services/api_compat.dart';

class RequestDetailScreen extends StatefulWidget {
  final String requestId;
  final CustomerRequest? request;
  const RequestDetailScreen({super.key, this.requestId = '', this.request});

  @override State<RequestDetailScreen> createState() => _RequestDetailScreenState();
}

class _RequestDetailScreenState extends State<RequestDetailScreen> {
  final _api = ApiProvider();
  CustomerRequest? _detail;
  List<Offer> _offers = [];
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    if (widget.request != null) { _detail = widget.request; }
    setState(() => _loading = true);
    try {
      final reqId = _detail?.id ?? widget.requestId;
      if (reqId.isNotEmpty) {
        final o = await _api.getRequestOffers(reqId);
        final d = widget.request ?? await _api.getRequest(reqId);
        if (mounted) setState(() { _detail = d; _offers = o; _loading = false; });
      }
    } catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _sendOffer() async {
    final priceCtrl = TextEditingController(), notesCtrl = TextEditingController();
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
      title: const Text('Kirim Penawaran'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(controller: priceCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Harga (Rp)')),
        const SizedBox(height: 12),
        TextField(controller: notesCtrl, maxLines: 3, decoration: const InputDecoration(labelText: 'Catatan (opsional)')),
      ]),
      actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Kirim'))],
    ));
    if (ok == true && priceCtrl.text.isNotEmpty) {
      try { await _api.createOffer(_detail?.id ?? widget.requestId, {'price': double.parse(priceCtrl.text), 'notes': notesCtrl.text}); _load(); }
      catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()))); }
    }
  }

  @override Widget build(BuildContext ctx) {
    if (_loading) return Scaffold(appBar: AppBar(), body: const Center(child: CircularProgressIndicator()));
    final r = _detail;
    if (r == null) return Scaffold(appBar: AppBar(), body: const Center(child: Text('Tidak ditemukan')));

    return Scaffold(
      appBar: AppBar(title: const Text('Detail Permintaan')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(r.title, style: Theme.of(ctx).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Row(children: [CircleAvatar(radius: 16, backgroundColor: Colors.orange.shade50, child: Text(r.userInitials ?? '?', style: TextStyle(fontSize: 12, color: Colors.orange.shade700))), const SizedBox(width: 8), Text(r.userName ?? '', style: const TextStyle(fontWeight: FontWeight.w500))]),
          const SizedBox(height: 8),
          Row(children: [Icon(Icons.timer, size: 16, color: r.isExpired ? Colors.red : Colors.green), const SizedBox(width: 4), Text(r.timeLeft, style: TextStyle(color: r.isExpired ? Colors.red : Colors.green, fontWeight: FontWeight.w600))]),
        ]))),
        if (r.description?.isNotEmpty == true) Card(child: Padding(padding: const EdgeInsets.all(16), child: Text(r.description!, style: TextStyle(color: Colors.grey.shade700)))),
        const SizedBox(height: 8),
        Text('Penawaran (${_offers.length})', style: Theme.of(ctx).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
        if (_offers.isEmpty) const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('Belum ada penawaran'))),
        ..._offers.map((o) => Card(child: ListTile(
          leading: CircleAvatar(backgroundColor: Colors.green.shade50, child: Text((o.storeName ?? 'T').substring(0, 1).toUpperCase(), style: TextStyle(color: Colors.green.shade700))),
          title: Text(o.storeName ?? ''),
          subtitle: Text('Rp ${o.price.toStringAsFixed(0)}${o.additionalCost != null ? ' + Rp ${o.additionalCost!.toStringAsFixed(0)}' : ''}'),
          trailing: o.estimatedHours != null ? Chip(label: Text('${o.estimatedHours} jam', style: const TextStyle(fontSize: 11))) : null,
        ))),
      ]),
      bottomNavigationBar: r.isExpired ? null : SafeArea(child: Padding(padding: const EdgeInsets.all(16), child: ElevatedButton.icon(onPressed: _sendOffer, icon: const Icon(Icons.send), label: const Text('Kirim Penawaran')))),
    );
  }
}
