<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'groups' => Setting::orderBy('group')->orderBy('key')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $known = Setting::pluck('key')->all();

        $data = $request->validate([
            'settings'   => ['required', 'array', 'min:1'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ]);

        // Kunci tak dikenal ditolak: salah ketik akan diam-diam membuat baris
        // yang tidak pernah dibaca kode mana pun.
        $unknown = array_diff(array_keys($data['settings']), $known);

        if ($unknown !== []) {
            return back()->with('error', 'Kunci tidak dikenal: '.implode(', ', $unknown));
        }

        $this->settings->set($data['settings']);

        return redirect()->route('admin.settings')->with('success', 'Pengaturan disimpan.');
    }
}
