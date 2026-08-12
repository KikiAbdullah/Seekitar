import 'package:flutter_test/flutter_test.dart';

import 'package:seekitar_mobile/core/constants.dart';

void main() {
  test('formatRupiah memformat ribuan dengan benar', () {
    expect(AppConstants.formatRupiah(5000000), 'Rp 5.000.000');
    expect(AppConstants.formatRupiah(15000), 'Rp 15.000');
    expect(AppConstants.formatRupiah(15000.5), 'Rp 15.001');
    expect(AppConstants.formatRupiah(0), 'Rp 0');
  });

  test('typeLabel menerjemahkan tipe listing', () {
    expect(AppConstants.typeLabel('product'), 'Barang');
    expect(AppConstants.typeLabel('service'), 'Jasa');
    expect(AppConstants.typeLabel('rental'), 'Sewa');
    expect(AppConstants.typeLabel('lainnya'), 'lainnya');
  });
}
