<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataRetentionCommand extends Command
{
    protected $signature = 'privacy:retention';
    protected $description = 'Bersihkan data pribadi sesuai kebijakan retensi (UU PDP).';

    public function handle(): int
    {
        $this->info('Memulai pembersihan retensi data...');

        $anonymized = $this->anonymizeDeletedUsers();
        $consentsPurged = $this->purgeExpiredConsentLogs();
        $activitiesPurged = $this->anonymizeOldActivityLogs();

        $this->info("Selesai. {$anonymized} akun dianonimkan, {$consentsPurged} log consent dihapus, {$activitiesPurged} log aktivitas dianonimkan.");

        return self::SUCCESS;
    }

    /**
     * Anonimisasi akun yang dihapus (soft-delete) > 90 hari.
     */
    private function anonymizeDeletedUsers(): int
    {
        $cutoff = now()->subDays(90);
        $users = User::onlyTrashed()->where('deleted_at', '<', $cutoff)->get();
        $count = 0;

        foreach ($users as $user) {
            DB::transaction(function () use ($user): void {
                $user->forceFill([
                    'name'             => 'Pengguna Dihapus',
                    'email'            => 'deleted-'.$user->id.'@anonymized.local',
                    'phone'            => '000000000000',
                    'address'          => null,
                    'avatar_url'       => null,
                    'nik'              => null,
                    'nik_hash'         => null,
                    'ktp_image'        => null,
                    'selfie_image'     => null,
                    'ktp_submitted_at' => null,
                    'password'         => null,
                    'remember_token'   => null,
                    'location'         => null,
                ])->save();

                $user->tokens()->delete();
            });

            Log::channel('privacy')->info('User anonymized by retention', [
                'user_id' => $user->id,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Hapus consent logs > 2 tahun.
     */
    private function purgeExpiredConsentLogs(): int
    {
        $cutoff = now()->subYears(2);
        $deleted = DB::table('consent_logs')
            ->where('created_at', '<', $cutoff)
            ->delete();

        Log::channel('privacy')->info('Expired consent logs purged', [
            'count' => $deleted,
            'cutoff' => $cutoff->toDateTimeString(),
        ]);

        return $deleted;
    }

    /**
     * Anonimisasi activity logs > 3 tahun.
     * Hapus data deskriptif tapi pertahankan barisnya untuk audit count.
     */
    private function anonymizeOldActivityLogs(): int
    {
        $cutoff = now()->subYears(3);
        $updated = DB::table('activity_logs')
            ->where('created_at', '<', $cutoff)
            ->update([
                'description' => DB::raw("CONCAT(LEFT(description, 1), '***')"),
                // Kolom aktual tabel: old_values/new_values/ip_address/user_agent.
                // (Bukan `properties` — kolom itu tidak ada dan akan membuat
                // query ini melempar Unknown column.)
                'old_values'  => null,
                'new_values'  => null,
                'ip_address'  => null,
                'user_agent'  => null,
            ]);

        Log::channel('privacy')->info('Old activity logs anonymized', [
            'count'  => $updated,
            'cutoff' => $cutoff->toDateTimeString(),
        ]);

        return $updated;
    }
}
