<?php

namespace App\Http\Controllers;

use App\Models\TargetIku;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $targetIku2026 = TargetIku::where('tahun', 2026)->get()->keyBy('kelompok');

        return view('dashboard', [
            'targetIku' => $targetIku2026,
        ]);
    }
}
