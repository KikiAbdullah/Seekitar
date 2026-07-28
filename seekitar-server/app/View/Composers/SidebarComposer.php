<?php

namespace App\View\Composers;

use App\Enums\DisputeStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use App\Models\Dispute;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\View\View;

/**
 * Angka lencana pada sidebar admin.
 *
 * KENAPA VIEW COMPOSER, BUKAN DIKIRIM DARI CONTROLLER
 * ---------------------------------------------------
 * Sidebar tampil di SEMUA halaman admin. Kalau angkanya dikirim tiap
 * controller, 12 controller harus mengulang query yang sama dan satu saja yang
 * lupa membuat lencana menghilang di halaman itu — tampak seperti antrian
 * mendadak kosong.
 *
 * KENAPA ADA PEMBATASAN IZIN DI SINI JUGA
 * ---------------------------------------
 * Angka antrian adalah data. "7 KTP menunggu" tetap memberi tahu volume
 * pengajuan kepada admin yang tidak berhak memverifikasi. Query-nya pun tidak
 * dijalankan bila tidak berhak — bukan sekadar disembunyikan di Blade.
 *
 * CACHE: hasilnya disimpan 60 detik. Tanpa itu, tiap muat halaman menambah
 * tiga COUNT hanya untuk angka kecil di menu; dengan itu, lencana paling
 * lambat satu menit tertinggal — jeda yang tidak berarti untuk SLA 1×24 jam.
 */
class SidebarComposer
{
    /** Detik. Sengaja pendek: ini indikator kerja, bukan laporan. */
    private const TTL = 60;

    public function __construct(private readonly Gate $gate) {}

    public function compose(View $view): void
    {
        $view->with([
            'pendingVerifikasi' => $this->pendingVerifikasi(),
            'laporanLewatSla'   => $this->laporanLewatSla(),
        ]);
    }

    /**
     * Total antrian verifikasi = KTP + toko.
     *
     * Digabung karena keduanya berada di bawah satu induk menu "Verifikasi".
     * Admin yang hanya punya salah satu izin hanya melihat porsinya sendiri —
     * kalau tidak, angkanya tidak cocok dengan isi halaman yang bisa dibukanya.
     */
    private function pendingVerifikasi(): int
    {
        $total = 0;

        if ($this->gate->allows('verify-users')) {
            $total += $this->remember('sidebar.pending_ktp', fn (): int => User::query()
                ->whereNotNull('ktp_submitted_at')
                ->where('verification_level', VerificationLevel::Basic)
                ->count());
        }

        if ($this->gate->allows('verify-stores')) {
            $total += $this->remember('sidebar.pending_stores', fn (): int => Store::query()
                ->where('verification_status', VerificationStatus::Pending)
                ->count());
        }

        return $total;
    }

    /**
     * Laporan yang melewati SLA.
     *
     * Definisi identik dengan `Dispute::isOverdue()`: terbuka, belum pernah
     * direspons, tenggat lewat. Menghitung semua laporan terbuka akan membuat
     * lencana ini selalu menyala dan kehilangan makna.
     */
    private function laporanLewatSla(): int
    {
        if (! $this->gate->allows('manage-disputes')) {
            return 0;
        }

        return $this->remember('sidebar.overdue_disputes', fn (): int => Dispute::query()
            ->where('status', DisputeStatus::Open)
            ->whereNull('first_responded_at')
            ->where('response_deadline', '<', now())
            ->count());
    }

    /**
     * Cache pendek yang TIDAK menjatuhkan halaman bila backend cache mati.
     *
     * Lencana adalah hiasan; kegagalan Redis tidak boleh membuat seluruh panel
     * admin menampilkan layar error.
     */
    private function remember(string $key, \Closure $callback): int
    {
        try {
            return (int) cache()->remember($key, self::TTL, $callback);
        } catch (\Throwable) {
            return (int) $callback();
        }
    }
}
