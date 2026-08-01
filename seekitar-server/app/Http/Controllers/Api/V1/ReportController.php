<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pelaporan konten/pengguna melanggar.
 */
class ReportController extends Controller
{
    use ApiResponse;

    private const ALLOWED_REASONS = [
        'inappropriate', 'fake', 'spam', 'illegal',
        'harassment', 'scam', 'copyright', 'other',
    ];

    private const ALLOWED_TYPES = [
        'listing' => Listing::class,
        'store'   => Store::class,
        'user'    => User::class,
    ];

    /** POST /reports */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_type' => ['required', 'string', 'in:listing,store,user'],
            'target_id'   => ['required', 'string'],
            'reason'      => ['required', 'string', 'in:'.implode(',', self::ALLOWED_REASONS)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $modelClass = self::ALLOWED_TYPES[$data['target_type']];
        $target = $modelClass::find($data['target_id']);

        if (! $target) {
            return $this->fail('Target laporan tidak ditemukan.', 404);
        }

        $reporterId = $request->user()->id;

        // Cegah spam: satu orang satu laporan per target
        $exists = Report::where('reporter_id', $reporterId)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $data['target_id'])
            ->unresolved()
            ->exists();

        if ($exists) {
            return $this->fail('Anda sudah melaporkan konten ini dan masih dalam peninjauan.', 422);
        }

        $report = Report::create([
            'reporter_id'     => $reporterId,
            'reportable_type' => $modelClass,
            'reportable_id'   => $data['target_id'],
            'reason'          => $data['reason'],
            'description'     => $data['description'] ?? null,
        ]);

        return $this->created(null, 'Laporan berhasil dikirim. Tim kami akan meninjaunya.');
    }
}
