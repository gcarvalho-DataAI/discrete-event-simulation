<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\AdsService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(AdsService $ads)
    {
        $items = $ads->listActive();
        return view('home', [
            'ads' => $items,
        ]);
    }

    public function show(Ad $ad)
    {
        if (!$ad->active) {
            abort(404);
        }
        return view('ad', ['ad' => $ad]);
    }
}
