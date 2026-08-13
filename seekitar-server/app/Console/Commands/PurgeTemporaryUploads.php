<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeTemporaryUploads extends Command
{
    protected $signature = 'uploads:purge-temporary {--hours=24 : Umur minimum berkas dalam jam}';
    protected $description = 'Hapus unggahan sementara yang tidak pernah dipakai entitas.';

    public function handle(): int
    {
        $cutoff = now()->subHours(max(1, (int) $this->option('hours')))->getTimestamp();
        $deleted = 0;

        foreach (['public', 'local'] as $diskName) {
            $disk = Storage::disk($diskName);
            foreach ($disk->allFiles('tmp') as $path) {
                if ($disk->lastModified($path) < $cutoff) {
                    $disk->delete($path);
                    $deleted++;
                }
            }
        }

        $this->info("{$deleted} unggahan sementara dihapus.");

        return self::SUCCESS;
    }
}
