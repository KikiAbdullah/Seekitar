<?php

namespace Database\Seeders;

use App\Enums\ListingType;
use App\Enums\OrderStatus;
use App\Enums\StoreType;
use App\Models\Category;
use App\Models\CustomerRequest;
use App\Models\Dispute;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use App\Models\UserDevice;
use Database\Factories\Support\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Data demo BERVOLUME untuk seluruh tabel.
 *
 * ⚠️ HANYA local & testing. Penjagaannya diulang di sini supaya seeder tetap
 * aman meski dipanggil langsung lewat `db:seed --class=DemoDataSeeder`.
 *
 * KENAPA TERPISAH DARI DummyDataSeeder
 * ------------------------------------
 * `DummyDataSeeder` sengaja kecil dan DETERMINISTIK: dua toko dan satu
 * permintaan dengan nama tetap, dipakai pengujian manual yang butuh data
 * yang selalu sama. Seeder ini kebalikannya — ratusan baris acak untuk
 * menguji paginasi, filter, pengurutan jarak, dan beban tabel admin.
 * Menggabungkan keduanya berarti tidak ada lagi cara mendapat data kecil
 * yang bisa diprediksi.
 *
 * URUTAN PEMBUATAN MENGIKUTI KETERGANTUNGAN FK:
 *   pengguna → toko → listing → permintaan → penawaran → pesanan
 *            → ulasan → laporan → perangkat & favorit
 *
 * SEMUA data berada di Kabupaten Pasuruan (PRD §2). Koordinat sedunia akan
 * membuat `scopeNearby()` tidak pernah mengembalikan apa pun, sehingga fitur
 * utama produk justru tidak bisa dicoba.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Volume data.
     *
     * Bisa ditimpa lewat env agar test bisa memakai angka kecil tanpa
     * mengubah kode: `SEEKITAR_DEMO_SCALE=0.1 php artisan db:seed`.
     */
    private const VOLUME = [
        'pembeli'        => 120,
        'penjual'        => 45,
        'toko'           => 60,
        'listing'        => 260,
        'permintaan'     => 90,
        'pesanan'        => 200,
        'perangkat'      => 100,
        'favorit'        => 150,
    ];

    /** @var array<string, int> */
    private array $volume = [];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException(
                'DemoDataSeeder membuat ratusan baris data palsu dan HANYA boleh '
                .'jalan di local/testing. Environment saat ini: '.app()->environment()
            );
        }

        $this->hitungVolume();

        // Kategori WAJIB ada lebih dulu: customer_requests.category_id
        // NOT NULL + FK RESTRICT.
        $kategori = Category::whereNotNull('parent_id')->get();

        if ($kategori->isEmpty()) {
            throw new RuntimeException(
                'Belum ada subkategori. Jalankan CategorySeeder lebih dulu — '
                .'lihat urutan di DatabaseSeeder.'
            );
        }

        $this->command?->info('Menyiapkan data demo Kabupaten Pasuruan…');

        $penjual = $this->buatPenjual();
        $pembeli = $this->buatPembeli();

        $toko     = $this->buatToko($penjual, $kategori);
        $listing  = $this->buatListing($toko);
        $requests = $this->buatPermintaan($pembeli, $kategori);

        $this->buatPenawaran($requests, $toko);
        $pesanan = $this->buatPesanan($pembeli, $toko, $listing);

        $this->buatUlasan($pesanan);
        $this->buatLaporan($pesanan);
        $this->buatPerangkat($pembeli->merge($penjual));
        $this->buatFavorit($pembeli, $listing);

        $this->ringkasan();
    }

    /**
     * Membagi `$jumlah` baris ke beberapa keadaan secara proporsional.
     *
     * KENAPA BUKAN `$i % n`
     * ---------------------
     * Pola modulo tampak rapi tetapi diam-diam rapuh pada volume kecil: dengan
     * 11 baris, `$i % 11 === 0` hanya benar sekali — dan bila indeks itu sudah
     * diambil cabang lain, keadaannya TIDAK PERNAH lahir. Akibatnya filter di
     * panel admin punya pilihan yang selalu kosong.
     *
     * Di sini tiap keadaan dijamin muncul minimal SEKALI (`max(1, …)`), lalu
     * urutannya diacak supaya data tidak berkelompok rapi per status.
     *
     * @param  array<string, float>  $bobot  keadaan => proporsi (total ±1.0)
     * @return list<string>
     */
    private function alokasiStatus(int $jumlah, array $bobot): array
    {
        // Satu jatah WAJIB untuk tiap keadaan, disisihkan lebih dulu.
        $wajib = array_keys($bobot);

        // Sisanya dibagi menurut bobot.
        $sisa    = max(0, $jumlah - count($wajib));
        $tambahan = [];

        foreach ($bobot as $keadaan => $porsi) {
            $n = (int) round($sisa * $porsi);
            $tambahan = array_merge($tambahan, array_fill(0, max(0, $n), $keadaan));
        }

        /*
         * Pemotongan HANYA mengenai bagian tambahan.
         *
         * Versi pertama meng-shuffle seluruh rencana lalu memotongnya — dan
         * itu bisa membuang satu-satunya entri sebuah keadaan. Nyata terjadi:
         * status toko `pending` hilang sama sekali pada volume kecil,
         * sehingga antrian verifikasi toko kosong. Ditemukan
         * tools/dev/run-seeders.php.
         */
        $tambahan = array_slice($tambahan, 0, $sisa);

        $rencana = array_merge($wajib, $tambahan);
        shuffle($rencana);

        return $rencana;
    }

    /** Skala volume dari env; 1.0 = penuh. */
    private function hitungVolume(): void
    {
        $skala = max(0.01, (float) env('SEEKITAR_DEMO_SCALE', 1.0));

        foreach (self::VOLUME as $kunci => $jumlah) {
            $this->volume[$kunci] = max(1, (int) round($jumlah * $skala));
        }
    }

    // ───────────────────────────────────────────────────────── Pengguna

    /**
     * Penjual: semuanya terverifikasi (verified_at terisi).
     *
     * Bukan pilihan gaya — `StorePolicy::create()` menuntut
     * `canOpenStore()`, yang sejak kolom level dihapus berarti stempel
     * KTP (PRD §5.3.2). "Pro" TIDAK disetel di sini: lencana itu turunan
     * dari toko tervalidasi, dan 80% toko demo memang terverifikasi —
     * persis seperti alur produksi.
     */
    private function buatPenjual(): \Illuminate\Support\Collection
    {
        $jumlah = $this->volume['penjual'];

        return User::factory()
            ->count($jumlah)
            ->verified()
            ->create()
            ->each(function (User $u): void {
                [$lat, $lng] = Wilayah::acak();
                $u->setLocation($lat, $lng)->save();
            });
    }

    /**
     * Pembeli dengan sebaran keadaan yang nyata.
     *
     * Termasuk yang menunggu verifikasi KTP dan yang diblokir — tanpa
     * keduanya, antrian verifikasi dan filter "Diblokir" di panel admin
     * selalu kosong dan tidak bisa diuji.
     */
    private function buatPembeli(): \Illuminate\Support\Collection
    {
        $jumlah   = $this->volume['pembeli'];
        $antrian  = max(1, (int) round($jumlah * 0.12));   // menunggu KTP
        $blokir   = max(1, (int) round($jumlah * 0.05));   // diblokir
        $biasa    = max(1, $jumlah - $antrian - $blokir);

        $pembeli = collect()
            ->merge(User::factory()->count($biasa)->create())
            ->merge(User::factory()->count($antrian)->menungguKtp()->create())
            ->merge(User::factory()->count($blokir)->diblokir()->create());

        // Lokasi wajib bagi pembeli: middleware EnsureProfileComplete
        // menolak aksi transaksional tanpa titik.
        return $pembeli->each(function (User $u): void {
            [$lat, $lng] = Wilayah::dekatPusat(12);
            $u->setLocation($lat, $lng)->save();
        });
    }

    // ───────────────────────────────────────────────────────── Toko

    private function buatToko(
        \Illuminate\Support\Collection $penjual,
        \Illuminate\Support\Collection $kategori,
    ): \Illuminate\Support\Collection {
        $jumlah = $this->volume['toko'];
        $toko   = collect();

        // Mayoritas terverifikasi agar katalog berisi, tetapi antrian
        // verifikasi, daftar penolakan, dan filter "Diblokir" dijamin
        // tidak kosong — tanpa jatah wajib, ketiganya tak bisa diuji.
        $rencana = $this->alokasiStatus($jumlah, [
            'terverifikasi' => 0.77,
            'menunggu'      => 0.13,
            'ditolak'       => 0.07,
            'diblokir'      => 0.03,
        ]);

        foreach ($rencana as $i => $status) {
            $factory = match ($status) {
                'menunggu' => Store::factory()->menunggu(),
                'ditolak'  => Store::factory()->ditolak(),
                'diblokir' => Store::factory()->diblokir(),
                default    => Store::factory()->terverifikasi(),
            };

            // Tipe toko menentukan listing apa yang boleh dipasang
            // (StoreType::allowsListingType), jadi disimpan konsisten.
            $tipe = fake()->randomElement([
                [StoreType::Goods],
                [StoreType::Services],
                [StoreType::Rental],
                [StoreType::Goods, StoreType::Services],
            ]);

            $atribut = [
                'category_ids' => $kategori->random(fake()->numberBetween(1, 3))
                    ->pluck('id')->values()->all(),
            ];

            // Toko diblokir adalah PASANGAN pemilik yang diblokir (blokir
            // pemilik menyeret tokonya): factory ->diblokir() sudah membuat
            // pemiliknya sendiri. Menggantinya dengan penjual acak yang
            // sehat menghasilkan data mustahil.
            if ($status !== 'diblokir') {
                $atribut['user_id'] = $penjual->random()->id;
            }

            $toko->push($factory->tipe($tipe)->create($atribut));
        }

        // Beberapa toko dinonaktifkan — filter "tidak aktif" perlu isi.
        $toko->random(max(1, (int) round($jumlah * 0.08)))
            ->each(fn (Store $s) => $s->forceFill(['is_active' => false])->save());

        // Satu toko pending dengan pemilik yang BELUM lulus KTP: antrian
        // verifikasi toko mensyaratkan pemilik verified, jadi toko ini
        // sengaja ada untuk membuktikan dirinya TIDAK terhitung antrian.
        Store::factory()->menunggu()->create([
            'user_id'      => User::factory()->menungguKtp(),
            'category_ids' => $kategori->random(1)->pluck('id')->all(),
        ]);

        return $toko;
    }

    // ───────────────────────────────────────────────────────── Listing

    private function buatListing(\Illuminate\Support\Collection $toko): \Illuminate\Support\Collection
    {
        $aktif   = $toko->where('is_active', true)
            ->where('status', \App\Enums\StoreStatus::Verified);

        // Toko yang belum terverifikasi tidak boleh punya listing
        // (ListingPolicy::createFor) — kalau dipaksakan, data contoh
        // menggambarkan keadaan yang mustahil di produksi.
        if ($aktif->isEmpty()) {
            $aktif = $toko;
        }

        $listing = collect();
        $jumlah  = $this->volume['listing'];

        for ($i = 0; $i < $jumlah; $i++) {
            $store = $aktif->random();

            // Tipe listing HARUS diizinkan tipe tokonya.
            $tipeToko = $store->store_type;
            $diizinkan = collect(ListingType::cases())
                ->filter(fn (ListingType $t) => collect($tipeToko)
                    ->contains(fn (StoreType $st) => $st->allowsListingType($t)))
                ->values();

            if ($diizinkan->isEmpty()) {
                continue;
            }

            $tipe = $diizinkan->random();

            $factory = match ($tipe) {
                ListingType::Product => Listing::factory()->produk(),
                ListingType::Service => Listing::factory()->jasa(),
                ListingType::Rental  => Listing::factory()->sewa(),
            };

            $listing->push($factory->create(['store_id' => $store->id]));
        }

        return $listing;
    }

    // ───────────────────────────────────────────── Permintaan & penawaran

    private function buatPermintaan(
        \Illuminate\Support\Collection $pembeli,
        \Illuminate\Support\Collection $kategori,
    ): \Illuminate\Support\Collection {
        $jumlah = $this->volume['permintaan'];
        $aktif  = $pembeli->filter(fn (User $u) => ! $u->isBlocked());
        $daftar = collect();

        /*
         * Sebaran status dialokasikan PROPORSIONAL, bukan lewat `$i % 11`.
         *
         * Pola modulo terlihat rapi tetapi rapuh: pada volume kecil,
         * pembaginya bisa tidak pernah tercapai. Dengan 11 permintaan,
         * `$i % 11 === 0` hanya benar di i=0 — dan karena i=0 sudah diambil
         * cabang `$i % 7`, status `closed` TIDAK PERNAH lahir. Filter
         * "Selesai" di panel admin lalu selalu kosong.
         *
         * Ditemukan oleh tools/dev/run-seeders.php, bukan oleh pembacaan kode.
         *
         * `max(1, …)` menjamin tiap status tetap ada meski volumenya kecil.
         */
        $kedaluwarsa  = max(1, (int) round($jumlah * 0.15));
        $ditutup      = max(1, (int) round($jumlah * 0.12));
        $diperpanjang = max(1, (int) round($jumlah * 0.08));
        $terbuka      = max(1, $jumlah - $kedaluwarsa - $ditutup - $diperpanjang);

        $rencana = array_merge(
            array_fill(0, $terbuka, 'terbuka'),
            array_fill(0, $kedaluwarsa, 'kedaluwarsa'),
            array_fill(0, $ditutup, 'ditutup'),
            array_fill(0, $diperpanjang, 'diperpanjang'),
        );
        shuffle($rencana);

        foreach ($rencana as $status) {
            $factory = match ($status) {
                'kedaluwarsa'  => CustomerRequest::factory()->kedaluwarsa(),
                'ditutup'      => CustomerRequest::factory()->ditutup(),
                'diperpanjang' => CustomerRequest::factory()->diperpanjang(fake()->numberBetween(1, 2)),
                default        => CustomerRequest::factory()->terbuka(),
            };

            $daftar->push(
                $factory->untukKategori($kategori->random())
                    ->create(['user_id' => $aktif->random()->id])
            );
        }

        return $daftar;
    }

    /**
     * Penawaran untuk permintaan yang masih terbuka.
     *
     * UNIQUE (request_id, store_id): satu toko hanya boleh satu penawaran per
     * permintaan. Karena itu toko diambil dengan `unique()`, bukan diacak
     * berulang — pengulangan pasti menabrak constraint di tengah seeding.
     */
    private function buatPenawaran(
        \Illuminate\Support\Collection $requests,
        \Illuminate\Support\Collection $toko,
    ): void {
        $terbuka = $requests->where('status', \App\Enums\RequestStatus::Open);
        $penyedia = $toko->where('status', \App\Enums\StoreStatus::Verified);

        if ($penyedia->isEmpty()) {
            return;
        }

        foreach ($terbuka as $request) {
            $banyak = fake()->numberBetween(0, 5);

            if ($banyak === 0) {
                continue;   // permintaan tanpa penawaran juga harus terwakili
            }

            // Penyedia TIDAK boleh menawar permintaannya sendiri
            // (OfferPolicy::createFor) — itu jalur mengerek reputasi sendiri.
            $kandidat = $penyedia
                ->where('user_id', '!=', $request->user_id)
                ->shuffle()
                ->take($banyak);

            foreach ($kandidat as $store) {
                $factory = fake()->boolean(20)
                    ? Offer::factory()->lewatWaktu()
                    : Offer::factory()->menunggu();

                // Harga diselaraskan dengan anggaran pembeli bila ada, supaya
                // `CustomerRequest::acceptsPrice()` tidak selalu menolak.
                $min = (int) ($request->budget_min ?? 50000);
                $max = (int) ($request->budget_max ?? 800000);

                $factory->denganHarga(
                    round(fake()->numberBetween($min, max($min + 1000, $max)) / 500) * 500
                )->create([
                    'request_id' => $request->id,
                    'store_id'   => $store->id,
                ]);
            }
        }

        $this->tandaiPenawaranDiterima($requests);
    }

    /**
     * Permintaan yang ditutup punya satu penawaran diterima.
     *
     * Tanpa ini `accepted_offer_id` selalu NULL dan halaman detail permintaan
     * tidak pernah menampilkan jalur "sudah memilih penyedia".
     */
    private function tandaiPenawaranDiterima(\Illuminate\Support\Collection $requests): void
    {
        foreach ($requests->where('status', \App\Enums\RequestStatus::Closed) as $request) {
            $offer = Offer::where('request_id', $request->id)->first();

            if ($offer === null) {
                continue;
            }

            $offer->forceFill(['status' => \App\Enums\OfferStatus::Accepted])->save();
            $request->forceFill(['accepted_offer_id' => $offer->id])->save();

            // Penawaran lain pada permintaan yang sama otomatis kalah.
            Offer::where('request_id', $request->id)
                ->whereKeyNot($offer->id)
                ->update(['status' => \App\Enums\OfferStatus::Rejected->value]);
        }
    }

    // ───────────────────────────────────────────────────────── Pesanan

    /**
     * Pesanan dengan sebaran status yang mencakup SEMUA nilai ENUM.
     *
     * Status disetel saat PEMBUATAN, bukan diubah setelahnya:
     * `OrderObserver::updating()` memvalidasi tiap transisi lewat
     * OrderStateMachine, sehingga lompatan seperti
     * menunggu_konfirmasi → selesai akan ditolak.
     */
    private function buatPesanan(
        \Illuminate\Support\Collection $pembeli,
        \Illuminate\Support\Collection $toko,
        \Illuminate\Support\Collection $listing,
    ): \Illuminate\Support\Collection {
        $jumlah  = $this->volume['pesanan'];
        $aktif   = $pembeli->filter(fn (User $u) => ! $u->isBlocked());
        $pesanan = collect();

        /*
         * Sebaran status dialokasikan proporsional — alasannya sama dengan
         * buatPermintaan(): pola `$i % 11` tidak pernah tercapai pada volume
         * kecil, sehingga status `dispute` bisa nol dan halaman Laporan
         * kosong sama sekali.
         *
         * Setiap nilai ENUM orders.status dijamin muncul minimal sekali.
         */
        $rencana = $this->alokasiStatus($jumlah, [
            'menunggu' => 0.18,
            'diproses' => 0.20,
            'dikirim'  => 0.14,
            'selesai_baru' => 0.16,   // masih dalam jendela ulasan 7 hari
            'selesai_lama' => 0.14,
            'dibatalkan'   => 0.10,
            'sengketa'     => 0.08,
        ]);

        foreach ($rencana as $i => $status) {
            $item  = $listing->random();
            $store = $toko->firstWhere('id', $item->store_id) ?? $toko->random();
            $buyer = $aktif->random();

            // Pembeli tidak memesan dari tokonya sendiri.
            if ($buyer->id === $store->user_id) {
                continue;
            }

            $factory = match ($status) {
                'dibatalkan'   => Order::factory()->dibatalkan(),
                'sengketa'     => Order::factory()->sengketa(),
                'selesai_baru' => Order::factory()->selesaiBaru(),
                'selesai_lama' => Order::factory()->selesai(fake()->numberBetween(10, 60)),
                'dikirim'      => Order::factory()->dikirim(),
                'diproses'     => Order::factory()->diproses(),
                default        => Order::factory()->menungguKonfirmasi(),
            };

            // Metode antar harus sejalan dengan kemampuan toko: memesan
            // "diantar" ke toko yang tidak melayani antar tidak masuk akal.
            $factory = $store->offers_delivery && fake()->boolean(50)
                ? $factory->diantar()
                : $factory->diambil();

            $factory = fake()->boolean(55)
                ? $factory->cod()
                : $factory->transfer();

            $harga = (int) ($item->price ?? 50000);
            $qty   = $item->listing_type === ListingType::Product ? fake()->numberBetween(1, 4) : 1;

            $pesanan->push($factory->create([
                'buyer_id'     => $buyer->id,
                'store_id'     => $store->id,
                'listing_id'   => $item->id,
                'order_type'   => \App\Enums\OrderType::fromListingType($item->listing_type),
                'quantity'     => $qty,
                'total_amount' => $harga * $qty,
            ]));
        }

        return $pesanan;
    }

    // ───────────────────────────────────────────────────────── Ulasan

    /**
     * Ulasan DUA ARAH untuk pesanan yang selesai (PRD §5.5).
     *
     * UNIQUE (order_id, direction) menjamin maksimal satu ulasan per arah,
     * jadi tiap pesanan diproses sekali saja.
     */
    private function buatUlasan(\Illuminate\Support\Collection $pesanan): void
    {
        $selesai = $pesanan->where('status', OrderStatus::Selesai);

        foreach ($selesai as $order) {
            // Tidak semua pesanan selesai diulas — itu keadaan yang nyata.
            if (fake()->boolean(30)) {
                continue;
            }

            $store   = Store::find($order->store_id);
            $pembeli = User::find($order->buyer_id);
            $pemilik = $store ? User::find($store->user_id) : null;

            if (! $store || ! $pembeli || ! $pemilik) {
                continue;
            }

            // Arah pembeli → toko: SATU-SATUNYA yang memengaruhi rating_avg.
            // ReviewObserver menghitung ulang otomatis setiap baris dibuat.
            Review::factory()
                ->keToko($store, $pembeli, $pemilik)
                ->create(['order_id' => $order->id]);

            // Sebagian penjual balas menilai pembeli.
            if (fake()->boolean(45)) {
                Review::factory()
                    ->kePembeli($pemilik, $pembeli)
                    ->create(['order_id' => $order->id]);
            }
        }
    }

    // ───────────────────────────────────────────────────────── Laporan

    /**
     * Laporan masalah, termasuk yang MELEWATI SLA.
     *
     * Tanpa baris lewat-SLA, lencana merah di sidebar dan kartu "Laporan
     * Lewat SLA" di dasbor selalu nol — dan jalur paling penting di panel
     * admin tidak pernah terlihat.
     */
    private function buatLaporan(\Illuminate\Support\Collection $pesanan): void
    {
        $sengketa = $pesanan->where('status', OrderStatus::Dispute);
        $admin    = User::role(['admin', 'super-admin'])->first();

        if ($sengketa->isEmpty()) {
            return;
        }

        // Proporsional, bukan `$i % 4` — lihat catatan di alokasiStatus().
        $rencana = $this->alokasiStatus($sengketa->count(), [
            'lewat_sla' => 0.30,
            'direspons' => 0.20,
            'selesai'   => 0.25,
            'terbuka'   => 0.25,
        ]);

        foreach ($sengketa->values() as $i => $order) {
            $factory = match ($rencana[$i] ?? 'terbuka') {
                'lewat_sla' => Dispute::factory()->lewatSla(),
                'direspons' => Dispute::factory()->direspons(),
                'selesai'   => Dispute::factory()->selesai($admin),
                default     => Dispute::factory()->terbuka(),
            };

            $factory->create([
                'order_id'    => $order->id,
                'reported_by' => $order->buyer_id,
            ]);
        }

        // Jaminan minimal: selalu ada laporan lewat SLA meski pembagian di
        // atas kebetulan tidak menghasilkannya.
        if (Dispute::whereNull('first_responded_at')
            ->where('response_deadline', '<', now())->doesntExist()
            && $sengketa->isNotEmpty()) {
            Dispute::factory()->lewatSla()->create([
                'order_id'    => $sengketa->first()->id,
                'reported_by' => $sengketa->first()->buyer_id,
            ]);
        }
    }

    // ─────────────────────────────────────────────── Perangkat & favorit

    private function buatPerangkat(\Illuminate\Support\Collection $pengguna): void
    {
        $jumlah = min($this->volume['perangkat'], $pengguna->count());

        // device_id UNIQUE pada kolomnya sendiri, jadi tiap pengguna diambil
        // sekali — bukan diacak berulang.
        $pengguna->shuffle()->take($jumlah)->each(function (User $u): void {
            UserDevice::factory()->create(['user_id' => $u->id]);
        });
    }

    /**
     * Favorit dengan pasangan (user, listing) yang dijamin unik.
     *
     * UNIQUE (user_id, listing_id) akan gagal bila keduanya diacak bebas pada
     * ratusan baris, jadi pasangannya dilacak eksplisit.
     */
    private function buatFavorit(
        \Illuminate\Support\Collection $pembeli,
        \Illuminate\Support\Collection $listing,
    ): void {
        if ($listing->isEmpty()) {
            return;
        }

        $target  = $this->volume['favorit'];
        $dipakai = [];
        $dibuat  = 0;
        $percobaan = 0;

        // Batas percobaan mencegah loop tak berujung bila kombinasi uniknya
        // lebih sedikit dari target.
        $maksPercobaan = $target * 5;

        while ($dibuat < $target && $percobaan < $maksPercobaan) {
            $percobaan++;

            $u = $pembeli->random();
            $l = $listing->random();
            $kunci = $u->id.'|'.$l->id;

            if (isset($dipakai[$kunci])) {
                continue;
            }

            $dipakai[$kunci] = true;
            Favorite::factory()->create(['user_id' => $u->id, 'listing_id' => $l->id]);
            $dibuat++;
        }
    }

    // ───────────────────────────────────────────────────────── Ringkasan

    private function ringkasan(): void
    {
        $this->command?->newLine();
        $this->command?->info('Data demo siap:');

        $this->command?->table(
            ['Tabel', 'Baris'],
            collect([
                'users'             => User::count(),
                'stores'            => Store::count(),
                'listings'          => Listing::count(),
                'customer_requests' => CustomerRequest::count(),
                'offers'            => Offer::count(),
                'orders'            => Order::count(),
                'reviews'           => Review::count(),
                'disputes'          => Dispute::count(),
                'user_devices'      => UserDevice::count(),
                'favorites'         => Favorite::count(),
                'categories'        => Category::count(),
                'settings'          => DB::table('settings')->count(),
            ])->map(fn ($jumlah, $tabel) => [$tabel, number_format($jumlah, 0, ',', '.')])
              ->values()->all(),
        );
    }
}
