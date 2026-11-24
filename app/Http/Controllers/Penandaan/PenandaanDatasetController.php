<?php

namespace App\Http\Controllers\Penandaan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenandaanDatasetController extends Controller
{
    /**
     * Tampilkan laman daftar dataset pengawasan penandaan.
     */
    public function index(Request $request): View
    {
        return view('penandaan.dataset.index');
    }
}
