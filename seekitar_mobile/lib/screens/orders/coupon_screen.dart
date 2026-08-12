import 'package:flutter/material.dart';
import '../../core/constants.dart';
import '../../services/api_compat.dart';
import '../../services/dio_client.dart';

class CouponScreen extends StatefulWidget {
  final double orderTotal; final String orderId;
  const CouponScreen({super.key, this.orderTotal = 0, this.orderId = ''});
  @override State<CouponScreen> createState() => _CouponScreenState();
}

class _CouponScreenState extends State<CouponScreen> {
  final _api = ApiProvider(), _code = TextEditingController();
  Map<String,dynamic>? _result; bool _loading = false; String? _error;

  Future<void> _validate() async {
    if (_code.text.isEmpty) return;
    setState(() { _loading = true; _error = null; });
    try { final r = await _api.validateCoupon(_code.text, widget.orderTotal); if (mounted) setState(() => _result = r); }
    catch (e) { if (mounted) setState(() => _error = DioClient.friendly(e)); }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _apply() async {
    if (_loading) return;
    setState(() => _loading = true);
    try { await _api.applyCoupon(_code.text, widget.orderId); if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Kupon berhasil diterapkan!'))); Navigator.pop(context, true); } }
    catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: ${DioClient.friendly(e)}'))); }
    if (mounted) setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Kupon / Voucher')),
    body: Padding(padding: const EdgeInsets.all(20), child: Column(children: [
      Row(children: [Expanded(child: TextField(controller: _code, decoration: const InputDecoration(labelText: 'Kode Kupon', hintText: 'DISKON50'))), const SizedBox(width: 12), ElevatedButton(onPressed: _loading ? null : _validate, child: const Text('Cek'))]),
      if (_loading) const Padding(padding: EdgeInsets.only(top: 24), child: CircularProgressIndicator()),
      if (_error != null) Padding(padding: const EdgeInsets.only(top: 16), child: Text(_error!, style: const TextStyle(color: Colors.red))),
      if (_result != null) ...[const SizedBox(height: 16), Card(child: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
        Row(children: [const Icon(Icons.discount, color: Color(0xFF168A4A)), const SizedBox(width: 10), Expanded(child: Text(_result!['coupon']?['code'] ?? '', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)))]),
        const SizedBox(height: 8),
        Text('Diskon: ${AppConstants.formatRupiah((_result!['discount'] as num).toDouble())}', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF168A4A))),
        const SizedBox(height: 16),
        SizedBox(width: double.infinity, child: ElevatedButton(onPressed: _loading ? null : _apply, child: const Text('Pakai Kupon'))),
      ])))],
    ])),
  );
  @override void dispose() { _code.dispose(); super.dispose(); }
}
