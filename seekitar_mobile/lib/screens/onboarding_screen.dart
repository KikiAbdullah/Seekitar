import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});
  @override State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final _pageCtrl = PageController();
  int _page = 0;

  static const _pages = [
    _OnboardPage(title: 'Pasar di Sekitarmu', desc: 'Temukan barang, jasa, dan sewa dari tetangga terdekat. Fokus satu kabupaten.', icon: Icons.store, color: Color(0xFF168A4A)),
    _OnboardPage(title: 'Gratis Tanpa Komisi', desc: 'Buka toko gratis selamanya. Tidak ada biaya langganan, tidak ada potongan transaksi.', icon: Icons.wallet, color: Color(0xFF34D399)),
    _OnboardPage(title: 'Dua Arah, Satu Pasar', desc: 'Pembeli pasang kebutuhan, penjual kirim penawaran. Transaksi langsung, rating tercatat.', icon: Icons.swap_horiz, color: Color(0xFFFF9800)),
  ];

  @override void dispose() { _pageCtrl.dispose(); super.dispose(); }

  Future<void> _finish() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('onboarding_seen', true);
    if (mounted) context.go('/login');
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    return Scaffold(
      body: SafeArea(child: Column(children: [
        Align(alignment: Alignment.topRight, child: TextButton(onPressed: _finish, child: const Text('Lewati', style: TextStyle(fontWeight: FontWeight.w600)))),
        Expanded(child: PageView.builder(controller: _pageCtrl, onPageChanged: (i) => setState(() => _page = i), itemCount: _pages.length, itemBuilder: (_, i) {
          final p = _pages[i];
          return Padding(padding: const EdgeInsets.symmetric(horizontal: 40), child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            Container(width: 140, height: 140, decoration: BoxDecoration(color: p.color.withOpacity(0.1), borderRadius: BorderRadius.circular(40)), child: Icon(p.icon, size: 64, color: p.color)),
            const SizedBox(height: 48),
            Text(p.title, style: t.textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900, fontSize: 26, letterSpacing: -0.5), textAlign: TextAlign.center),
            const SizedBox(height: 16),
            Text(p.desc, style: TextStyle(fontSize: 15, color: Colors.grey.shade600, height: 1.6), textAlign: TextAlign.center),
          ]));
        })),
        Padding(padding: const EdgeInsets.all(24), child: Row(mainAxisAlignment: MainAxisAlignment.center, children: List.generate(_pages.length, (i) => Container(margin: const EdgeInsets.symmetric(horizontal: 4), width: _page == i ? 24 : 8, height: 8, decoration: BoxDecoration(color: _page == i ? t.colorScheme.primary : Colors.grey.shade300, borderRadius: BorderRadius.circular(4)))))),
        Padding(padding: const EdgeInsets.fromLTRB(24, 0, 24, 32), child: SizedBox(width: double.infinity, height: 56, child: ElevatedButton(onPressed: () {
          if (_page < _pages.length - 1) { _pageCtrl.animateToPage(_page + 1, duration: const Duration(milliseconds: 300), curve: Curves.easeInOut); }
          else { _finish(); }
        }, child: Text(_page < _pages.length - 1 ? 'Lanjut' : 'Mulai', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700))))),
      ])),
    );
  }
}

class _OnboardPage { final String title, desc; final IconData icon; final Color color; const _OnboardPage({required this.title, required this.desc, required this.icon, required this.color}); }
