<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if ($email && $password) {
            $user = User::query()->where('email', strtolower($email))->first();
            if (!$user) {
                User::query()->create([
                    'name' => 'Admin',
                    'email' => strtolower($email),
                    'password' => Hash::make($password),
                    'role' => 'ADMIN',
                ]);
            } elseif ($user->role !== 'ADMIN') {
                $user->update(['role' => 'ADMIN']);
            }
        }

        $seedFile = base_path('database/seeders/data/ads.json');
        if (is_file($seedFile)) {
            $raw = file_get_contents($seedFile);
            $items = json_decode($raw ?: '[]', true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (!is_array($item) || empty($item['id'])) {
                        continue;
                    }

                    $priceText = $item['price'] ?? null;
                    $priceCents = $this->parsePriceToCents($priceText);
                    $images = $item['images'] ?? [];
                    if (empty($images) && !empty($item['image'])) {
                        $images = [$item['image']];
                    }

                    Ad::query()->updateOrCreate(
                        ['code' => (string) $item['id']],
                        [
                            'title' => (string) ($item['title'] ?? ''),
                            'description' => $item['description'] ?? null,
                            'price_text' => $priceText,
                            'price_cents' => $priceCents,
                            'images' => $images ?: [],
                            'cta_label' => $item['ctaLabel'] ?? null,
                            'cta_href' => $item['ctaHref'] ?? null,
                            'active' => (bool) ($item['active'] ?? true),
                            'file_url' => $item['fileUrl'] ?? null,
                            'created_at' => $item['createdAt'] ?? now(),
                            'updated_at' => $item['updatedAt'] ?? now(),
                        ]
                    );
                }
            }
        }
    }

    private function parsePriceToCents(?string $price): int
    {
        if (!$price) {
            return 0;
        }
        $normalized = preg_replace('/[^0-9,\\.]/', '', $price);
        if ($normalized === null || $normalized === '') {
            return 0;
        }
        $normalized = str_replace(['.', ','], ['','.' ], $normalized);
        $value = (float) $normalized;
        return (int) round($value * 100);
    }
}
