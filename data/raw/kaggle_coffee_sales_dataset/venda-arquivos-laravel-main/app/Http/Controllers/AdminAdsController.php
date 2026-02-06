<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Services\AdsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAdsController extends Controller
{
    public function active(AdsService $ads)
    {
        return view('admin.ads-list', [
            'ads' => $ads->listAll()->where('active', true),
            'title' => 'Anuncios ativos',
        ]);
    }

    public function paused(AdsService $ads)
    {
        return view('admin.ads-list', [
            'ads' => $ads->listAll()->where('active', false),
            'title' => 'Anuncios pausados',
        ]);
    }

    public function create()
    {
        return view('admin.ads-form', [
            'ad' => null,
        ]);
    }

    public function store(Request $request, AdsService $ads)
    {
        $data = $this->validateAd($request, $ads);
        $ads->create($data);
        return redirect()->route('admin.ads.active');
    }

    public function edit(Ad $ad)
    {
        return view('admin.ads-form', [
            'ad' => $ad,
        ]);
    }

    public function update(Request $request, Ad $ad, AdsService $ads)
    {
        $data = $this->validateAd($request, $ads, $ad);
        $ads->update($ad, $data);
        return redirect()->route('admin.ads.active');
    }

    public function toggle(Ad $ad)
    {
        $ad->update(['active' => !$ad->active]);
        return back();
    }

    public function destroy(Ad $ad, AdsService $ads)
    {
        $ads->delete($ad);
        return redirect()->route('admin.ads.active');
    }

    private function validateAd(Request $request, AdsService $ads, ?Ad $ad = null): array
    {
        $data = $request->validate([
            'code' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_text' => 'nullable|string|max:255',
            'cta_label' => 'nullable|string|max:255',
            'cta_href' => 'nullable|string|max:255',
            'file_url' => 'nullable|string|max:255',
            'images' => 'nullable|string',
            'active' => 'nullable|string',
            'images_upload.*' => 'nullable|image|max:5120',
        ]);

        $images = [];
        if (!empty($data['images'])) {
            $images = array_values(array_filter(array_map('trim', preg_split('/\r?\n|,/', $data['images']))));
        }

        if ($request->hasFile('images_upload')) {
            foreach ($request->file('images_upload') as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }
                $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads'), $filename);
                $images[] = '/uploads/' . $filename;
            }
        }

        $priceText = $data['price_text'] ?? null;
        $data['price_cents'] = $ads->parsePriceToCents($priceText);
        $data['images'] = $images;
        $data['active'] = isset($data['active']) && $data['active'] === 'on';
        if ($ad && empty($data['code'])) {
            unset($data['code']);
        }

        return $data;
    }
}
