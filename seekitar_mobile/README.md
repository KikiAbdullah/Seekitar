# Seekitar Mobile

Aplikasi Flutter **Seekitar** (Android & iOS) — klien dari
[`seekitar-server`](../seekitar-server/README.md) (`/api/v1`).

## Menjalankan

Kebutuhan: Flutter 3.44+ (Dart 3.12+).

```bash
flutter pub get
dart run build_runner build --delete-conflicting-outputs
flutter run --dart-define-from-file=config/dev.json
```

> ⚠️ `build_runner` **wajib** dijalankan sebelum `flutter run` — berkas
> `.g.dart` (json_serializable/Riverpod codegen) tidak di-commit, jadi tanpa
> langkah ini build gagal dengan ratusan galat "tidak ditemukan".
>
> Emulator Android memetakan host ke `10.0.2.2`, bukan `localhost` — sesuaikan
> `baseUrl` di `config/dev.json`.

## Kontrak yang perlu diingat

- Masuk tanpa kata sandi: `POST /auth/request-otp` → `POST /auth/verify-otp`
  → simpan Bearer token di secure storage.
- `verification_level` pada JSON pengguna adalah **turunan baca-saja**
  (1 = masuk OTP, 2 = KTP disetujui, 3 = punya toko terverifikasi) — tidak
  ada endpoint untuk mengubahnya.
- Ganti nomor HP lewat dua langkah OTP (`/auth/phone/request-otp` →
  `verify-otp`); unggah ulang berkas KTP membuka peninjauan admin ulang.

Panduan lengkap arsitektur (Riverpod 3, GoRouter, FCM, dsb.) ada di
[`Mobile_Implementation_Guide.md`](../Mobile_Implementation_Guide.md);
kontrak endpoint di [`API_DOCUMENTATION.md`](../API_DOCUMENTATION.md).
