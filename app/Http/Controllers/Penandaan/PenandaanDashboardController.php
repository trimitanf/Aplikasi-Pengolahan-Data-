<?php

namespace App\Http\Controllers\Penandaan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenandaanDashboardController extends Controller
{
    /**
     * Tampilkan laman dashboard pengawasan penandaan.
     */
    public function index(Request $request): View
    {
        return view('penandaan.dashboard');
    }
}
