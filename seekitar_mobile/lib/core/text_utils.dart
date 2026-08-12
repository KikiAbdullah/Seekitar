/// Utilitas teks yang aman terhadap string kosong/null — mencegah
/// `RangeError` dari `substring`/`[0]` pada nama yang tidak ada isinya.

/// Mengambil maksimal `n` karakter pertama dari `value`.
///
/// Tidak pernah melempar untuk string kosong/null atau lebih pendek dari `n`:
///   - null/kosong        → `fallback`
///   - panjang <= n       → seluruh string (trim)
///   - panjang > n        → `n` karakter pertama
String firstChars(String? value, int n, {String fallback = '?'}) {
  final s = value == null || value.trim().isEmpty ? fallback : value.trim();
  if (s.length <= n) return s;
  return s.substring(0, n);
}

/// Inisial (1-2 huruf) untuk avatar dari sebuah nama — aman terhadap
/// string kosong. `maxChars` default 2 (umum untuk avatar toko/pengguna).
String avatarInitials(String? name, {int maxChars = 2, String fallback = '?'}) {
  final initials = firstChars(name, maxChars, fallback: fallback);
  return initials.toUpperCase();
}
