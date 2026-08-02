import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../core/constants.dart';
import '../core/theme.dart';
import '../models/listing.dart';
import '../services/api_compat.dart';

/// Alur transaksi: konfirmasi pembelian → kirim ke POST /orders → lanjut ke
/// detail pesanan. Data diambil langsung dari listing yang sedang dibuka.
class CheckoutScreen extends StatefulWidget {
  final Listing listing;
  const CheckoutScreen({super.key, required this.listing});

  @override State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _api = ApiProvider();
  final _addressCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();

  int _qty = 1;
  String _delivery = 'pickup';
  String _payment = 'cod';
  bool _submitting = false;

  Listing get l => widget.listing;

  int get _maxQty {
    if (l.listingType == 'product') return l.stockQty ?? 1000;
    return 1000;
  }

  double get _unitPrice => l.price ?? 0;
  double get _subtotal => _unitPrice * _qty;
  double get _total => _subtotal;

  @override
  void dispose() {
    _addressCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_delivery == 'delivery' && _addressCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Alamat pengiriman wajib diisi.')));
      return;
    }
    setState(() => _submitting = true);
    try {
      final order = await _api.createOrder({
        'listing_id': l.id,
        'quantity': _qty,
        'payment_method': _payment,
        'delivery_method': _delivery,
        if (_delivery == 'delivery') 'shipping_address': _addressCtrl.text.trim(),
        if (_notesCtrl.text.trim().isNotEmpty) 'notes': _notesCtrl.text.trim(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Pesanan berhasil dibuat!')));
      ctx.pushReplacement('/order-detail/${order.id}', extra: order);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal membuat pesanan: $e')));
        setState(() => _submitting = false);
      }
    }
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    return Scaffold(
      appBar: AppBar(title: const Text('Checkout')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        _listingCard(t),
        const SizedBox(height: 20),
        _section('Jumlah', [
          Row(children: [
            IconButton.outlined(icon: const Icon(Icons.remove), onPressed: _qty > 1 ? () => setState(() => _qty--) : null),
            SizedBox(width: 56, child: Center(child: Text('$_qty', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)))),
            IconButton.outlined(icon: const Icon(Icons.add), onPressed: _qty < _maxQty ? () => setState(() => _qty++) : null),
            const Spacer(),
            Text('Stok: ${l.stockQty ?? (l.listingType == 'product' ? '-' : 'Unlimited')}', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
          ]),
        ]),
        const SizedBox(height: 12),
        _section('Metode Penerimaan', [
          _radioCard('pickup', Icons.store_outlined, 'Ambil di Tempat', 'Ambil sendiri di toko penjual', _delivery == 'pickup'),
          const SizedBox(height: 8),
          _radioCard('delivery', Icons.local_shipping_outlined, 'Diantar Penjual', 'Penjual mengantar ke alamatmu', _delivery == 'delivery'),
          if (_delivery == 'delivery') ...[
            const SizedBox(height: 12),
            TextField(controller: _addressCtrl, minLines: 2, maxLines: 3, decoration: const InputDecoration(labelText: 'Alamat Pengiriman *', hintText: 'Jalan, RT/RW, kelurahan, kecamatan', border: OutlineInputBorder())),
          ],
        ]),
        const SizedBox(height: 12),
        _section('Metode Pembayaran', [
          _radioCard('cod', Icons.payments_outlined, 'Bayar di Tempat (COD)', 'Bayar langsung saat barang diterima', _payment == 'cod'),
          const SizedBox(height: 8),
          _radioCard('transfer', Icons.account_balance_outlined, 'Transfer Bank', 'Unggah bukti transfer setelah pesanan dibuat', _payment == 'transfer'),
        ]),
        const SizedBox(height: 12),
        _section('Catatan (opsional)', [
          TextField(controller: _notesCtrl, minLines: 2, maxLines: 3, decoration: const InputDecoration(labelText: 'Catatan untuk penjual', border: OutlineInputBorder())),
        ]),
        const SizedBox(height: 20),
        Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
          _priceRow('Subtotal', AppConstants.formatRupiah(_subtotal)),
          _priceRow('Biaya layanan', 'Gratis'),
          Divider(color: Colors.grey.shade200),
          Row(children: [const Text('Total', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)), const Spacer(), Text(AppConstants.formatRupiah(_total), style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: t.colorScheme.primary))]),
        ]))),
        const SizedBox(height: 24),
      ]),
      bottomNavigationBar: SafeArea(child: Container(padding: const EdgeInsets.fromLTRB(16, 8, 16, 12), decoration: BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 12)]), child: Row(children: [
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisSize: MainAxisSize.min, children: [
          Text('Total', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
          Text(AppConstants.formatRupiah(_total), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: Color(0xFF168A4A))),
        ])),
        const SizedBox(width: 16),
        Expanded(flex: 2, child: ElevatedButton.icon(onPressed: _submitting ? null : _submit, icon: const Icon(Icons.lock_outline, size: 18), label: Text(_submitting ? 'Memproses...' : 'Buat Pesanan'), style: ElevatedButton.styleFrom(minimumSize: const Size.fromHeight(52)))),
      ]))),
    );
  }

  Widget _listingCard(ThemeData t) => Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: Padding(padding: const EdgeInsets.all(14), child: Row(children: [
    ClipRRect(borderRadius: BorderRadius.circular(14), child: l.images.isNotEmpty ? Image.network(l.images.first, width: 76, height: 76, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _ph()) : _ph()),
    const SizedBox(width: 14),
    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3), decoration: BoxDecoration(color: AppTheme.primarySubtle, borderRadius: BorderRadius.circular(8)), child: Text(AppConstants.typeLabel(l.listingType), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: t.colorScheme.primary))),
      const SizedBox(height: 6),
      Text(l.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
      const SizedBox(height: 4),
      Text(l.storeName ?? 'Toko', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
      const SizedBox(height: 6),
      Text(AppConstants.formatRupiah(_unitPrice), style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15, color: t.colorScheme.primary)),
    ])),
  ])));

  Widget _ph() => Container(width: 76, height: 76, decoration: BoxDecoration(borderRadius: BorderRadius.circular(14), gradient: LinearGradient(colors: [Colors.green.shade100, Colors.green.shade50])), child: const Icon(Icons.image, color: Colors.green, size: 26));

  Widget _section(String title, List<Widget> children) => Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
    Padding(padding: const EdgeInsets.only(left: 4, bottom: 8), child: Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15))),
    ...children,
  ]);

  Widget _radioCard(String value, IconData icon, String title, String subtitle, bool selected) => InkWell(
    borderRadius: BorderRadius.circular(16),
    onTap: () => setState(() {
      if (value == 'pickup' || value == 'delivery') { _delivery = value; } else { _payment = value; }
    }),
    child: Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(borderRadius: BorderRadius.circular(16), border: Border.all(color: selected ? const Color(0xFF168A4A) : Colors.grey.shade300, width: selected ? 1.6 : 1), color: selected ? AppTheme.primarySubtle : Colors.white), child: Row(children: [
      Icon(icon, color: selected ? const Color(0xFF168A4A) : Colors.grey.shade400, size: 26),
      const SizedBox(width: 12),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(title, style: TextStyle(fontWeight: FontWeight.w700, color: selected ? const Color(0xFF168A4A) : Colors.grey.shade800)),
        Text(subtitle, style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
      ])),
      Icon(selected ? Icons.radio_button_checked : Icons.radio_button_unchecked, color: selected ? const Color(0xFF168A4A) : Colors.grey.shade400),
    ])),
  );

  Widget _priceRow(String label, String value) => Padding(padding: const EdgeInsets.symmetric(vertical: 3), child: Row(children: [Text(label, style: TextStyle(color: Colors.grey.shade600)), const Spacer(), Text(value, style: const TextStyle(fontWeight: FontWeight.w600))]));

  BuildContext get ctx => context;
}
