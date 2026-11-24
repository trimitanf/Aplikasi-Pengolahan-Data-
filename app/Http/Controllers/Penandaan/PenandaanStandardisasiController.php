<?php

namespace App\Http\Controllers\Penandaan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenandaanStandardisasiController extends Controller
{
    /**
     * Tampilkan laman data terstandarisasi penandaan.
     */
    public function index(Request $request): View
    {
        return view('penandaan.standarisasi.index');
    }
}
