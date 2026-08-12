import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../providers/app_state.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});
  @override State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _c;
  late final Animation<double> _fade, _scale;

  @override void initState() {
    super.initState();
    _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1200));
    _fade = Tween<double>(begin: 0, end: 1).animate(CurvedAnimation(parent: _c, curve: Curves.easeOut));
    _scale = Tween<double>(begin: 0.6, end: 1).animate(CurvedAnimation(parent: _c, curve: const Interval(0, 0.7, curve: Curves.elasticOut)));
    _c.forward();
    _check();
  }

  @override void dispose() { _c.dispose(); super.dispose(); }

  Future<void> _check() async {
    final app = context.read<AppState>();
    final started = DateTime.now();
    const minShown = Duration(milliseconds: 1500);
    // Tunggu auto-login (AuthService.init) selesai — pakai batas waktu
    // supaya splash tidak menggantung bila server lambat/mati. Sebelumnya
    // menunggu durasi tetap 2s sehingga user yang sudah login bisa salah
    // diarahkan ke onboarding/login saat /auth/me lambat.
    final deadline = started.add(const Duration(seconds: 20));
    while (app.isLoading && mounted && DateTime.now().isBefore(deadline)) {
      await Future.delayed(const Duration(milliseconds: 100));
    }
    if (!mounted) return;
    // Logo animasi (1200ms) tetap tampil walau init sangat cepat.
    final elapsed = DateTime.now().difference(started);
    if (elapsed < minShown) {
      await Future.delayed(minShown - elapsed);
    }
    if (!mounted) return;
    final prefs = await SharedPreferences.getInstance();
    final seenOnboarding = prefs.getBool('onboarding_seen') ?? false;
    if (!mounted) return;
    if (app.isLoggedIn) {
      context.go('/home');
    } else if (!seenOnboarding) {
      context.go('/onboarding');
    } else {
      context.go('/login');
    }
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFFE9FAF1), Color(0xFFF8FAF9)])),
        child: Center(child: FadeTransition(opacity: _fade, child: ScaleTransition(scale: _scale, child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(width: 110, height: 110, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(36), boxShadow: [BoxShadow(color: t.colorScheme.primary.withOpacity(0.12), blurRadius: 40, offset: const Offset(0, 16))]), child: Image.asset('assets/images/logo.png', width: 64, height: 64)),
          const SizedBox(height: 32),
          Text('Seekitar', style: t.textTheme.headlineLarge?.copyWith(fontWeight: FontWeight.w900, fontSize: 34, letterSpacing: -0.5, color: t.colorScheme.primary)),
          const SizedBox(height: 8),
          Text('Pasar Lokal Satu Kabupaten', style: TextStyle(fontSize: 15, color: Colors.grey.shade500, fontWeight: FontWeight.w500)),
          const SizedBox(height: 56),
          SizedBox(width: 32, height: 32, child: CircularProgressIndicator(strokeWidth: 2.5, color: t.colorScheme.primary.withOpacity(0.4))),
        ])))),
      ),
    );
  }
}
