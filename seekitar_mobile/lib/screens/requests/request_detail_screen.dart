import 'package:flutter/material.dart';
import '../../models/customer_request.dart';
import '../../models/order.dart' show Offer;
import '../../services/api_compat.dart';

class RequestDetailScreen extends StatefulWidget {
  final String requestId; final CustomerRequest? request;
  const RequestDetailScreen({super.key, this.requestId = '', this.request});
  @override State<RequestDetailScreen> createState() => _RequestDetailScreenState();
}
class _RequestDetailScreenState extends State<RequestDetailScreen> {
  final _api = ApiProvider(); CustomerRequest? _detail; List<Offer> _offers = []; bool _loading = true;
  @override void initState() { super.initState(); _load(); }
  String get _id => _detail?.id ?? widget.requestId;
  Future<void> _load() async {
    if (widget.request != null) _detail = widget.request;
    setState(() => _loading = true);
    try { final rq = await _api.getRequest(_id); final of = await _api.getRequestOffers(_id); if (mounted) setState(() { _detail = rq; _offers = of; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }
  Future<void> _extend() async {
    try { final r = await _api.extendRequest(_id); if (mounted) { setState(() => _detail = r); ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Permintaan diperpanjang 24 jam!'))); } }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
  }
  Future<void> _sendOffer() async {
    final priceCtrl = TextEditingController(), notesCtrl = TextEditingController();
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)), title: const Text('Kirim Penawaran'), content: Column(mainAxisSize: MainAxisSize.min, children: [TextField(controller: priceCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Harga (Rp)')), const SizedBox(height: 12), TextField(controller: notesCtrl, maxLines: 3, decoration: const InputDecoration(labelText: 'Catatan (opsional)'))]), actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Kirim'))]));
    if (ok == true && priceCtrl.text.isNotEmpty) { try { await _api.createOffer(_id, {'price': double.parse(priceCtrl.text), 'notes': notesCtrl.text.isNotEmpty ? notesCtrl.text : null}); _load(); if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Penawaran terkirim!'))); } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString()))); } }
  }
  @override Widget build(BuildContext ctx) {
    if (_loading) return Scaffold(appBar: AppBar(), body: const Center(child: CircularProgressIndicator()));
    final r = _detail; if (r == null) return Scaffold(appBar: AppBar(), body: const Center(child: Text('Tidak ditemukan')));
    return Scaffold(
      appBar: AppBar(title: const Text('Detail Permintaan'), actions: [if (!r.isExpired) IconButton(icon: const Icon(Icons.timelapse), tooltip: 'Perpanjang 24 jam', onPressed: _extend)]),
      body: RefreshIndicator(onRefresh: _load, child: ListView(padding: const EdgeInsets.all(16), children: [
        Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(r.title, style: Theme.of(ctx).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)), const SizedBox(height: 8),
          Row(children: [CircleAvatar(radius: 16, backgroundColor: Colors.orange.shade50, child: Text(r.initials, style: TextStyle(fontSize: 12, color: Colors.orange.shade700))), const SizedBox(width: 8), Text(r.userName ?? '', style: const TextStyle(fontWeight: FontWeight.w500))]),
          const SizedBox(height: 8),
          Row(children: [Icon(Icons.timer, size: 16, color: r.isExpired ? Colors.red : Colors.green), const SizedBox(width: 4), Text(r.timeLeft, style: TextStyle(color: r.isExpired ? Colors.red : Colors.green, fontWeight: FontWeight.w600))]),
          if (!r.isExpired) ...[const SizedBox(height: 10), OutlinedButton.icon(onPressed: _extend, icon: const Icon(Icons.timelapse, size: 16), label: const Text('Perpanjang 24 Jam'), style: OutlinedButton.styleFrom(foregroundColor: Colors.orange))],
        ]))),
        if (r.description?.isNotEmpty == true) Card(child: Padding(padding: const EdgeInsets.all(16), child: Text(r.description!, style: TextStyle(color: Colors.grey.shade700)))),
        const SizedBox(height: 8),
        Text('Penawaran (${_offers.length})', style: Theme.of(ctx).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
        if (_offers.isEmpty) const Center(child: Padding(padding: EdgeInsets.all(24), child: Text('Belum ada penawaran'))),
        ..._offers.map((o) => Card(child: ListTile(
          leading: CircleAvatar(backgroundColor: Colors.green.shade50, child: Text((o.storeName ?? 'T').substring(0, 1).toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.w800))),
          title: Text(o.storeName ?? '', style: const TextStyle(fontWeight: FontWeight.w600)),
          subtitle: Text('Rp ${o.price.toStringAsFixed(0)}${o.additionalCost != null ? ' + Rp ${o.additionalCost!.toStringAsFixed(0)}' : ''}'),
          trailing: o.estimatedHours != null ? Chip(label: Text('${o.estimatedHours} jam', style: const TextStyle(fontSize: 11))) : null,
        ))),
      ])),
      bottomNavigationBar: r.isExpired ? null : SafeArea(child: Padding(padding: const EdgeInsets.all(16), child: ElevatedButton.icon(onPressed: _sendOffer, icon: const Icon(Icons.send), label: const Text('Kirim Penawaran'), style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 52))))),
    );
  }
}
