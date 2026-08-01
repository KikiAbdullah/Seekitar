import 'package:flutter/material.dart';
import '../services/api_compat.dart';
import '../models/wallet.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});
  @override State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final _api = ApiProvider();
  Wallet? _wallet;
  List<WalletTransaction> _txs = [];
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final r = await Future.wait([_api.getWallet(), _api.getWalletTransactions()]);
      if (mounted) setState(() { _wallet = r[0]; _txs = r[1]; _loading = false; });
    } catch (_) { if (mounted) setState(() => _loading = false); }
  }

  Future<void> _topup() async {
    final ctrl = TextEditingController();
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      title: const Text('Top Up Saldo'),
      content: TextField(controller: ctrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Jumlah (Rp)', hintText: '50000')),
      actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Top Up'))],
    ));
    if (ok == true && ctrl.text.isNotEmpty) {
      try { await _api.topup(double.parse(ctrl.text)); _load(); if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Top up berhasil!'))); }
      catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    }
  }

  Future<void> _withdraw() async {
    final amountCtrl = TextEditingController();
    final bankCtrl = TextEditingController();
    final accCtrl = TextEditingController();
    final ok = await showDialog<bool>(context: context, builder: (ctx) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
      title: const Text('Tarik Saldo'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(controller: amountCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Jumlah (Rp)', hintText: '50000')),
        const SizedBox(height: 12),
        TextField(controller: bankCtrl, decoration: const InputDecoration(labelText: 'Nama Bank', hintText: 'BCA / BRI / Mandiri')),
        const SizedBox(height: 12),
        TextField(controller: accCtrl, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Nomor Rekening')),
      ]),
      actions: [TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')), ElevatedButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Tarik'))],
    ));
    if (ok == true && amountCtrl.text.isNotEmpty) {
      try {
        await _api.withdraw({
          'amount': double.parse(amountCtrl.text),
          'bank_name': bankCtrl.text,
          'bank_account': accCtrl.text,
        });
        _load();
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Penarikan diajukan. Diproses 1x24 jam.')));
      } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal: $e'))); }
    }
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    return Scaffold(
      appBar: AppBar(title: const Text('Dompet')),
      body: _loading ? const Center(child: CircularProgressIndicator()) : RefreshIndicator(
        onRefresh: _load,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          Card(child: Padding(padding: const EdgeInsets.all(24), child: Column(children: [
            const Text('Saldo', style: TextStyle(color: Colors.grey)),
            const SizedBox(height: 8),
            Text(_wallet?.balanceDisplay ?? 'Rp 0', style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: t.colorScheme.primary)),
            const SizedBox(height: 20),
            Row(children: [
              Expanded(child: ElevatedButton.icon(onPressed: _topup, icon: const Icon(Icons.add_circle_outline, size: 20), label: const Text('Top Up'))),
              const SizedBox(width: 12),
              Expanded(child: OutlinedButton.icon(onPressed: _withdraw, icon: const Icon(Icons.credit_card, size: 20), label: const Text('Tarik'))),
            ]),
          ]))),
          const SizedBox(height: 16),
          Text('Riwayat Transaksi', style: t.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          if (_txs.isEmpty) const Center(child: Padding(padding: EdgeInsets.all(32), child: Text('Belum ada transaksi'))),
          ..._txs.map((tx) => Card(child: ListTile(
            title: Text(tx.description ?? tx.type, style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(tx.amountDisplay, style: TextStyle(color: tx.type == 'withdrawal' ? Colors.red : Colors.green, fontWeight: FontWeight.w600)),
            trailing: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
              Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4), decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(8)), child: Text(tx.status, style: const TextStyle(fontSize: 11))),
              Text(tx.createdAt.toString().substring(0, 10), style: TextStyle(fontSize: 10, color: Colors.grey.shade400)),
            ]),
          ))),
        ]),
      ),
    );
  }
}
