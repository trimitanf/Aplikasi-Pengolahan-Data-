<?php

namespace App\Http\Controllers\Penandaan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenandaanRekapController extends Controller
{
    /**
     * Tampilkan laman rekapitulasi evaluasi pengawasan penandaan.
     */
    public function index(Request $request): View
    {
        return view('penandaan.rekap.index');
    }
}
