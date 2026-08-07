import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../core/theme.dart';
import '../../models/order.dart';
import '../../providers/app_state.dart';
import '../../services/api_compat.dart';

/// Detail pesanan — titik tengah alur transaksi.
///
/// Memuat ulang data dari server (GET /orders/{id}) supaya selalu segar,
/// lalu menampilkan aksi sesuai peran (pembeli/penjual) dan status pesanan.
class OrderDetailScreen extends StatefulWidget {
  final String orderId;
  final Order? order;
  const OrderDetailScreen({super.key, required this.orderId, this.order});

  @override State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  final _api = ApiProvider();
  Order? _order;
  bool _loading = false;
  bool _processing = false;

  @override void initState() { super.initState(); _order = widget.order; _load(); }

  Order? get o => _order;

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final fresh = await _api.getOrder(widget.orderId);
      if (mounted) setState(() { _order = fresh; _loading = false; });
    } catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _apply(Future Function() fn, {String? done}) async {
    setState(() => _processing = true);
    try {
      await fn();
      if (done != null && mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(done)));
      if (mounted) await _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e')));
    }
    if (mounted) setState(() => _processing = false);
  }

  Future<void> _transition(String status, {String? reason}) => _apply(() => _api.updateOrderStatus(widget.orderId, status, reason: reason));

  Future<void> _cancel() async {
    final c = TextEditingController();
    final reason = await showModalBottomSheet<String>(context: context, isScrollControlled: true, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))), builder: (ctx) => Padding(padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom), child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))), const SizedBox(height: 20),
      const Text('Batalkan Pesanan', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)), const SizedBox(height: 12),
      TextField(controller: c, maxLines: 2, decoration: const InputDecoration(labelText: 'Alasan pembatalan (wajib)')),
      const SizedBox(height: 20),
      ElevatedButton(onPressed: () { Navigator.pop(ctx, c.text.trim().isNotEmpty ? c.text.trim() : null); }, child: const Text('Batalkan Pesanan')),
    ]))));
    if (reason != null && mounted) await _transition('dibatalkan', reason: reason);
  }

  Future<void> _uploadProof() async {
    final x = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: 1200);
    if (x == null) return;
    await _apply(() => _api.uploadPaymentProof(widget.orderId, File(x.path)), done: 'Bukti pembayaran terkirim!');
  }

  Future<void> _dispute() async {
    final reason = await showModalBottomSheet<String>(context: context, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))), builder: (ctx) => Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))), const SizedBox(height: 20),
      const Text('Laporkan Masalah', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)), const SizedBox(height: 16),
      ...[
        ['Barang tidak sesuai', 'item_not_matching'], ['Barang rusak', 'item_damaged'],
        ['Pesanan tidak dikirim', 'not_shipped'], ['Penjual tidak merespon', 'seller_unresponsive'],
        ['Lainnya', 'other'],
      ].map((r) => ListTile(title: Text(r[0]), leading: const Icon(Icons.warning_amber, size: 20), onTap: () => Navigator.pop(ctx, r[1]), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)))),
    ])));
    if (reason != null && mounted) await _apply(() => _api.disputeOrder(widget.orderId, reason), done: 'Sengketa diajukan. Admin akan meninjau dalam 1x24 jam.');
  }

  Future<void> _review() async {
    int rating = 5;
    final c = TextEditingController();
    await showModalBottomSheet(context: context, isScrollControlled: true, shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))), builder: (ctx) => Padding(padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom), child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [
      Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))), const SizedBox(height: 20),
      const Text('Beri Ulasan', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
      const SizedBox(height: 16),
      RatingBar.builder(initialRating: 5, minRating: 1, itemSize: 40, itemBuilder: (_, __) => const Icon(Icons.star, color: Colors.amber), onRatingUpdate: (v) => rating = v.toInt()),
      const SizedBox(height: 16),
      TextField(controller: c, maxLines: 3, decoration: const InputDecoration(labelText: 'Komentar (opsional)')),
      const SizedBox(height: 24),
      ElevatedButton(onPressed: () async {
        Navigator.pop(ctx);
        await _apply(() => _api.submitReview(widget.orderId, rating, comment: c.text.trim().isNotEmpty ? c.text.trim() : null), done: 'Ulasan terkirim!');
      }, child: const Text('Kirim')),
    ]))));
  }

  Future<void> _chat() async {
    final order = o;
    if (order == null) return;
    final myId = context.read<AppState>().user?.id;
    final isBuyer = order.buyerId == myId;
    final participantId = isBuyer ? order.storeOwnerId : order.buyerId;
    if (participantId == null || participantId.isEmpty) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Lawan bicara tidak tersedia.')));
      return;
    }
    setState(() => _processing = true);
    try {
      final conv = await _api.createConversation(participantId, orderId: order.id);
      if (mounted) ctx.push('/chat/${conv.id}', extra: conv);
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    if (mounted) setState(() => _processing = false);
  }

  bool get _isBuyer => o != null && context.read<AppState>().user?.id == o!.buyerId;

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    final order = o;
    if (order == null) {
      return Scaffold(appBar: AppBar(title: const Text('Detail Pesanan')), body: Center(child: _loading ? const CircularProgressIndicator() : const Text('Pesanan tidak ditemukan')));
    }
    final isBuyer = _isBuyer;
    final color = AppConstants.orderStatusColor(order.status);

    return Scaffold(
      appBar: AppBar(title: Text('Pesanan #${_short(order)}')),
      body: RefreshIndicator(onRefresh: _load, child: ListView(padding: const EdgeInsets.all(16), children: [
        _statusHeader(t, order, color),
        if (order.isFinal && order.cancelReason?.isNotEmpty == true) _cancelReasonCard(t),
        if (order.status == 'dispute') _disputeBanner(),
        const SizedBox(height: 16),
        if (order.listingId != null) _listingCard(t, order),
        const SizedBox(height: 12),
        _storeCard(t, order, isBuyer),
        const SizedBox(height: 16),
        _infoCard(t, order),
        if (order.isTransfer) ...[
          const SizedBox(height: 16),
          _paymentCard(t, order, isBuyer),
        ],
        const SizedBox(height: 16),
        _actions(t, order, isBuyer),
        const SizedBox(height: 40),
      ])),
    );
  }

  String _short(Order order) {
    final s = order.orderNumber ?? order.id;
    return s.length > 12 ? s.substring(s.length - 10) : s;
  }

  // ── STATUS HEADER + STEpper ──
  Widget _statusHeader(ThemeData t, Order order, Color color) => Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)), child: Padding(padding: const EdgeInsets.all(20), child: Column(children: [
    Row(children: [
      Container(width: 44, height: 44, decoration: BoxDecoration(color: color.withOpacity(0.12), borderRadius: BorderRadius.circular(14)), child: Icon(_statusIcon(order.status), color: color, size: 24)),
      const SizedBox(width: 12),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(order.displayStatusLabel, style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: color)),
        Text(order.orderNumber != null ? 'No. Pesanan: ${order.orderNumber}' : '', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
      ])),
    ]),
    const SizedBox(height: 18),
    if (!order.isFinal) _stepper(order.status) else Text(order.status == 'dibatalkan' ? 'Pesanan dibatalkan' : 'Pesanan selesai', style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
  ])));

  Widget _stepper(String status) {
    const steps = ['Dibuat', 'Diproses', 'Dikirim', 'Selesai'];
    final orderIdx = {'menunggu_konfirmasi': 0, 'diproses': 1, 'dikirim': 2, 'selesai': 3}[status] ?? 0;
    final current = status == 'selesai' ? 4 : orderIdx;
    return Row(children: List.generate(steps.length * 2 - 1, (i) {
      if (i.isOdd) {
        return Expanded(child: Container(height: 2, color: i ~/ 2 < current ? const Color(0xFF168A4A) : Colors.grey.shade300));
      }
      final idx = i ~/ 2;
      final done = idx < current;
      return Container(width: 26, height: 26, decoration: BoxDecoration(shape: BoxShape.circle, color: done ? const Color(0xFF168A4A) : Colors.grey.shade200, border: Border.all(color: done ? const Color(0xFF168A4A) : Colors.grey.shade400)), child: Center(child: Icon(done ? Icons.check : Icons.circle, size: 14, color: done ? Colors.white : Colors.grey.shade400)));
    }));
  }

  Widget _cancelReasonCard(ThemeData t) => Container(margin: const EdgeInsets.only(top: 10), padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(16)), child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
    Icon(Icons.info_outline, size: 18, color: Colors.red.shade400),
    const SizedBox(width: 10),
    Expanded(child: Text('Alasan: ${o?.cancelReason ?? '-'}', style: TextStyle(fontSize: 13, color: Colors.red.shade700))),
  ]));

  Widget _disputeBanner() => Container(margin: const EdgeInsets.only(top: 10), padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(16)), child: const Row(children: [
    Icon(Icons.gavel, size: 18, color: Colors.red),
    SizedBox(width: 10),
    Expanded(child: Text('Pesanan dibekukan sampai admin memutuskan sengketa.', style: TextStyle(fontSize: 13, color: Colors.red))),
  ]));

  // ── CARDS ──
  Widget _listingCard(ThemeData t, Order order) => Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: InkWell(borderRadius: BorderRadius.circular(20), onTap: () => ctx.push('/listing/${order.listingId}'), child: Padding(padding: const EdgeInsets.all(14), child: Row(children: [
    ClipRRect(borderRadius: BorderRadius.circular(14), child: order.listingImages?.isNotEmpty == true ? Image.network(order.listingImages!.first, width: 64, height: 64, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _ph()) : _ph()),
    const SizedBox(width: 14),
    Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(order.listingTitle ?? 'Listing', maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
      const SizedBox(height: 4),
      Text('${order.quantity} × ${order.priceDisplay}', style: TextStyle(fontSize: 13, color: Colors.grey.shade600)),
    ])),
    const Icon(Icons.chevron_right, color: Colors.grey),
  ]))));

  Widget _storeCard(ThemeData t, Order order, bool isBuyer) => Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: ListTile(
    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
    leading: CircleAvatar(radius: 24, backgroundColor: Colors.green.shade50, child: Text((order.storeName ?? 'T').substring(0, 1).toUpperCase(), style: TextStyle(color: Colors.green.shade700, fontWeight: FontWeight.w800, fontSize: 16))),
    title: Text(order.storeName ?? 'Toko', style: const TextStyle(fontWeight: FontWeight.w700)),
    subtitle: Text(isBuyer ? 'Penjual' : 'Pembeli: ${o?.buyerName ?? '-'}', style: TextStyle(fontSize: 12, color: Colors.grey.shade500)),
    trailing: OutlinedButton.icon(onPressed: _processing ? null : _chat, icon: const Icon(Icons.chat_bubble_outline, size: 16), label: const Text('Chat')),
  ));

  Widget _infoCard(ThemeData t, Order order) => Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: Padding(padding: const EdgeInsets.all(16), child: Column(children: [
    _r('Jumlah', '${order.quantity}'),
    _r('Pembayaran', order.paymentLabel),
    _r('Penerimaan', order.deliveryLabel),
    if (order.isDelivery) _r('Alamat', order.shippingAddress ?? '-'),
    if (order.notes?.isNotEmpty == true) _r('Catatan', order.notes!),
    if (order.createdAt != null) _r('Dibuat', _fmt(order.createdAt!)),
    if (order.completedAt != null) _r('Selesai', _fmt(order.completedAt!)),
    Divider(color: Colors.grey.shade200),
    Row(children: [const Text('Total', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)), const Spacer(), Text(order.priceDisplay, style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: t.colorScheme.primary))]),
  ])));

  Widget _paymentCard(ThemeData t, Order order, bool isBuyer) {
    final hasBank = order.bankAccount?.isNotEmpty == true;
    return Card(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), child: Padding(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Text('Pembayaran Transfer', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
      const SizedBox(height: 10),
      if (isBuyer && hasBank) ...[
        Container(width: double.infinity, padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: AppTheme.primarySubtle, borderRadius: BorderRadius.circular(14)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Transfer ${order.priceDisplay} ke', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
          const SizedBox(height: 4),
          Text(order.bankAccountName ?? '', style: const TextStyle(fontWeight: FontWeight.w800)),
          Text(order.bankAccount ?? '-', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: t.colorScheme.primary)),
        ])),
        const SizedBox(height: 12),
      ] else if (isBuyer) ...[
        Text('Rekening penjual akan tampil di sini.', style: TextStyle(fontSize: 13, color: Colors.grey.shade500)),
        const SizedBox(height: 12),
      ],
      Row(children: [
        Icon(order.requiresPaymentProof ? Icons.upload_file : Icons.check_circle, size: 20, color: order.requiresPaymentProof ? Colors.orange : Colors.green),
        const SizedBox(width: 8),
        Expanded(child: Text(order.requiresPaymentProof ? 'Belum ada bukti pembayaran' : 'Bukti pembayaran sudah diunggah', style: TextStyle(fontSize: 13, color: order.requiresPaymentProof ? Colors.orange.shade800 : Colors.green.shade800))),
      ]),
      if (order.requiresPaymentProof) ...[
        const SizedBox(height: 12),
        SizedBox(width: double.infinity, child: ElevatedButton.icon(onPressed: _processing ? null : _uploadProof, icon: const Icon(Icons.receipt_long), label: const Text('Upload Bukti Pembayaran'), style: ElevatedButton.styleFrom(backgroundColor: Colors.orange, minimumSize: const Size.fromHeight(48)))),
      ],
    ])));
  }

  Widget _actions(ThemeData t, Order order, bool isBuyer) {
    final acts = <Widget>[];
    if (isBuyer) {
      if (order.requiresPaymentProof && (order.status == 'menunggu_konfirmasi' || order.status == 'diproses')) {
        acts.add(OutlinedButton.icon(onPressed: _processing ? null : _uploadProof, icon: const Icon(Icons.receipt_long, size: 20), label: const Text('Upload Bukti Pembayaran'), style: OutlinedButton.styleFrom(minimumSize: const Size(double.infinity, 48))));
      }
      if (order.status == 'menunggu_konfirmasi') {
        acts.add(OutlinedButton.icon(onPressed: _processing ? null : _cancel, icon: const Icon(Icons.close, size: 20), label: const Text('Batalkan Pesanan'), style: OutlinedButton.styleFrom(foregroundColor: Colors.red, minimumSize: const Size(double.infinity, 48))));
      }
      if (order.status == 'dikirim') {
        acts.add(ElevatedButton.icon(onPressed: _processing ? null : () => _transition('selesai'), icon: const Icon(Icons.task_alt, size: 20), label: const Text('Konfirmasi Diterima'), style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 48))));
      }
    } else {
      if (order.status == 'menunggu_konfirmasi') {
        acts.add(ElevatedButton.icon(onPressed: _processing ? null : () => _transition('diproses'), icon: const Icon(Icons.play_arrow, size: 20), label: const Text('Terima & Proses'), style: ElevatedButton.styleFrom(backgroundColor: Colors.blue, minimumSize: const Size(double.infinity, 48))));
        acts.add(const SizedBox(height: 8));
        acts.add(OutlinedButton.icon(onPressed: _processing ? null : _cancel, icon: const Icon(Icons.close, size: 20), label: const Text('Batalkan'), style: OutlinedButton.styleFrom(foregroundColor: Colors.red, minimumSize: const Size(double.infinity, 48))));
      } else if (order.status == 'diproses') {
        acts.add(ElevatedButton.icon(onPressed: _processing ? null : () => _transition('dikirim'), icon: const Icon(Icons.local_shipping_outlined, size: 20), label: Text(order.isDelivery ? 'Tandai Dikirim' : 'Tandai Siap Diambil'), style: ElevatedButton.styleFrom(backgroundColor: Colors.blue, minimumSize: const Size(double.infinity, 48))));
        acts.add(const SizedBox(height: 8));
        acts.add(OutlinedButton.icon(onPressed: _processing ? null : _cancel, icon: const Icon(Icons.close, size: 20), label: const Text('Batalkan'), style: OutlinedButton.styleFrom(foregroundColor: Colors.red, minimumSize: const Size(double.infinity, 48))));
      } else if (order.status == 'dikirim') {
        acts.add(ElevatedButton.icon(onPressed: _processing ? null : () => _transition('selesai'), icon: const Icon(Icons.task_alt, size: 20), label: const Text('Tandai Selesai'), style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 48))));
      }
    }
    if (order.canReview) {
      acts.add(const SizedBox(height: 8));
      acts.add(ElevatedButton.icon(onPressed: _processing ? null : _review, icon: const Icon(Icons.star, size: 20), label: const Text('Beri Ulasan'), style: ElevatedButton.styleFrom(minimumSize: const Size(double.infinity, 48))));
    }
    if (!order.isFinal && order.status != 'dispute') {
      acts.add(const SizedBox(height: 8));
      acts.add(OutlinedButton.icon(onPressed: _processing ? null : _dispute, icon: const Icon(Icons.report_problem_outlined, size: 20), label: const Text('Laporkan Masalah / Sengketa'), style: OutlinedButton.styleFrom(foregroundColor: Colors.red, minimumSize: const Size(double.infinity, 48))));
    }
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: acts);
  }

  Widget _ph() => Container(width: 64, height: 64, decoration: BoxDecoration(borderRadius: BorderRadius.circular(14), gradient: LinearGradient(colors: [Colors.green.shade100, Colors.green.shade50])), child: const Icon(Icons.image, color: Colors.green, size: 26));

  IconData _statusIcon(String s) => {'menunggu_konfirmasi': Icons.schedule, 'diproses': Icons.build, 'dikirim': Icons.local_shipping, 'selesai': Icons.check_circle, 'dibatalkan': Icons.cancel, 'dispute': Icons.gavel}[s] ?? Icons.receipt;

  String _fmt(DateTime d) => '${d.day}/${d.month}/${d.year} ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}';

  Widget _r(String l, String v) => Padding(padding: const EdgeInsets.symmetric(vertical: 5), child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [SizedBox(width: 110, child: Text(l, style: TextStyle(color: Colors.grey.shade500))), Expanded(child: Text(v, style: const TextStyle(fontWeight: FontWeight.w600)))]));

  BuildContext get ctx => context;
}
