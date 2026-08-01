import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../providers/app_state.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});
  @override State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final _phoneCtrl = TextEditingController(), _otpCtrl = TextEditingController();
  bool _otpSent = false, _loading = false;
  String? _error;
  int _countdown = 0;
  late final AnimationController _anim;
  late final Animation<double> _slide;

  @override void initState() {
    super.initState();
    _anim = AnimationController(vsync: this, duration: const Duration(milliseconds: 400));
    _slide = Tween<double>(begin: 0, end: 1).animate(CurvedAnimation(parent: _anim, curve: Curves.easeOutCubic));
    _anim.forward();
  }

  @override void dispose() { _phoneCtrl.dispose(); _otpCtrl.dispose(); _anim.dispose(); super.dispose(); }

  void _startCountdown() { _countdown = 60; Future.doWhile(() async { await Future.delayed(const Duration(seconds: 1)); if (!mounted) return false; setState(() => _countdown--); return _countdown > 0; }); }

  Future<void> _sendOtp() async {
    final p = _phoneCtrl.text.trim();
    if (p.length < 10) { setState(() => _error = 'Masukkan nomor WhatsApp yang valid'); return; }
    setState(() { _loading = true; _error = null; });
    try { await context.read<AppState>().requestOtp(p); if (mounted) { setState(() => _otpSent = true); _startCountdown(); } }
    catch (e) { if (mounted) setState(() => _error = e.toString().replaceAll('Exception: ', '')); }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _verifyOtp() async {
    final o = _otpCtrl.text.trim();
    if (o.length < 4) { setState(() => _error = 'Masukkan kode OTP'); return; }
    setState(() { _loading = true; _error = null; });
    try { await context.read<AppState>().verifyOtp(_phoneCtrl.text.trim(), o); if (mounted) context.go('/home'); }
    catch (e) { if (mounted) setState(() => _error = e.toString().replaceAll('Exception: ', '')); }
    if (mounted) setState(() => _loading = false);
  }

  @override Widget build(BuildContext ctx) {
    final t = Theme.of(ctx);
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Color(0xFFE9FAF1), Color(0xFFF8FAF9)])),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 28),
              child: FadeTransition(
                opacity: _slide,
                child: SlideTransition(
                  position: Tween<Offset>(begin: const Offset(0, 0.08), end: Offset.zero).animate(_anim),
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 420),
                    child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                      const SizedBox(height: 48),
                      Center(child: Container(
                        width: 100, height: 100,
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(32), boxShadow: [BoxShadow(color: t.colorScheme.primary.withOpacity(0.12), blurRadius: 30, offset: const Offset(0, 12))]),
                        child: Image.asset('assets/images/logo.png', width: 56, height: 56),
                      )),
                      const SizedBox(height: 28),
                      Text('Selamat datang\ndi Seekitar', textAlign: TextAlign.center, style: t.textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900, height: 1.15, letterSpacing: -0.5)),
                      const SizedBox(height: 8),
                      Text('Masuk dengan nomor WhatsApp.\nTanpa kata sandi.', textAlign: TextAlign.center, style: TextStyle(fontSize: 14, color: Colors.grey.shade500, height: 1.4)),
                      const SizedBox(height: 40),

                      // Phone field
                      Container(
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 12)]),
                        child: TextField(
                          controller: _phoneCtrl, keyboardType: TextInputType.phone,
                          enabled: !_otpSent, onSubmitted: (_) => _sendOtp(),
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600, letterSpacing: 0.5),
                          decoration: InputDecoration(
                            labelText: 'Nomor WhatsApp',
                            hintText: '8123-4567-890',
                            hintStyle: TextStyle(color: Colors.grey.shade300, fontSize: 18),
                            prefixIcon: Padding(padding: const EdgeInsets.only(left: 16, right: 8), child: Text('+62', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18, color: t.colorScheme.primary))),
                            prefixIconConstraints: const BoxConstraints(minWidth: 60),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
                          ),
                        ),
                      ),

                      if (_otpSent) ...[
                        const SizedBox(height: 16),
                        Container(
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 12)]),
                          child: TextField(
                            controller: _otpCtrl, keyboardType: TextInputType.number, maxLength: 6,
                            onSubmitted: (_) => _verifyOtp(),
                            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w800, letterSpacing: 8),
                            textAlign: TextAlign.center,
                            decoration: InputDecoration(
                              labelText: 'Kode OTP',
                              counterText: '',
                              labelStyle: TextStyle(color: Colors.grey.shade400, fontSize: 13),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(20), borderSide: BorderSide.none),
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Align(alignment: Alignment.centerRight, child: TextButton(
                          onPressed: _countdown > 0 ? null : () { setState(() => _otpSent = false); _otpCtrl.clear(); _sendOtp(); },
                          child: Text(_countdown > 0 ? 'Kirim ulang dalam ${_countdown}s' : 'Kirim ulang OTP', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _countdown > 0 ? Colors.grey : t.colorScheme.primary)),
                        )),
                      ],

                      if (_error != null) ...[
                        const SizedBox(height: 12),
                        Container(padding: const EdgeInsets.all(14), decoration: BoxDecoration(color: Colors.red.shade50, borderRadius: BorderRadius.circular(16)), child: Row(children: [
                          Icon(Icons.error_outline, size: 18, color: Colors.red.shade500),
                          const SizedBox(width: 10),
                          Expanded(child: Text(_error!, style: TextStyle(color: Colors.red.shade600, fontSize: 13))),
                        ])),
                      ],

                      const SizedBox(height: 24),
                      SizedBox(
                        height: 56,
                        child: ElevatedButton(
                          onPressed: _loading ? null : (_otpSent ? _verifyOtp : _sendOtp),
                          style: ElevatedButton.styleFrom(backgroundColor: t.colorScheme.primary, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)), elevation: 0),
                          child: _loading ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : Text(_otpSent ? 'Verifikasi & Masuk' : 'Kirim Kode OTP', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700)),
                        ),
                      ),
                      if (_otpSent) ...[
                        const SizedBox(height: 12),
                        TextButton(onPressed: () { setState(() { _otpSent = false; _otpCtrl.clear(); _error = null; }); _anim.forward(); }, child: const Text('← Ganti nomor WhatsApp')),
                      ],
                      const SizedBox(height: 40),
                      Text('Dengan masuk, kamu menyetujui Syarat & Ketentuan dan Kebijakan Privasi.', textAlign: TextAlign.center, style: TextStyle(fontSize: 11, color: Colors.grey.shade400, height: 1.4)),
                    ]),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
