<?php

namespace Database\Seeders;

use App\Models\Mutation;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCatalogSeeder extends Seeder
{
    private array $mutationModifiers = [
        'Nightmare' => 0,
        'Venom' => 15000,
        'Rainbow' => 35000,
    ];

    public function run(): void
    {
        $mutations = $this->seedMutations();

        foreach ($this->pets() as $index => $petData) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $petData['slug']],
                [
                    'type' => Product::TYPE_PET,
                    'name' => $petData['name'],
                    'image_url' => $petData['image_url'] ?? null,
                    'rarity' => $petData['rarity'],
                    'featured' => $petData['featured'],
                    'best_seller' => $petData['best_seller'],
                    'sort_order' => $index + 1,
                    'active' => true,
                ],
            );

            $this->syncVariants($product, $petData, $mutations);
        }
    }

    private function seedMutations(): array
    {
        $mutations = [];

        foreach ($this->mutationModifiers as $name => $priceModifier) {
            $mutations[$name] = Mutation::query()->updateOrCreate(
                ['name' => $name],
                [
                    'price_modifier' => $priceModifier,
                    'active' => true,
                ],
            );
        }

        return $mutations;
    }

    private function syncVariants(Product $product, array $petData, array $mutations): void
    {
        $baseWeight = (float) $petData['weights'][0];

        foreach (array_keys($this->mutationModifiers) as $mutationName) {
            foreach ($petData['weights'] as $weightKg) {
                $price = $this->calculateVariantPrice(
                    $petData['price'],
                    $baseWeight,
                    $mutationName,
                    (float) $weightKg,
                );

                ProductVariant::query()->updateOrCreate(
                    ['sku' => $this->makeSku($product->slug, $mutationName, (float) $weightKg)],
                    [
                        'product_id' => $product->id,
                        'mutation_id' => $mutations[$mutationName]->id,
                        'weight_kg' => $weightKg,
                        'price' => $price,
                        'stock' => $petData['stock'],
                        'active' => true,
                    ],
                );
            }
        }
    }

    private function calculateVariantPrice(
        int $basePrice,
        float $baseWeight,
        string $mutationName,
        float $weightKg,
    ): int {
        $weightModifier = max(0, (int) round($weightKg - $baseWeight)) * 1000;
        $mutationModifier = $this->mutationModifiers[$mutationName] ?? 0;

        return $basePrice + $mutationModifier + $weightModifier;
    }

    private function makeSku(string $slug, string $mutationName, float $weightKg): string
    {
        $weightCode = str_replace('.', '-', number_format($weightKg, 2, '.', ''));

        return Str::upper("GPM-{$slug}-{$mutationName}-{$weightCode}");
    }

    private function pets(): array
    {
        return [
            [
                'slug' => 'sample-pet',
                'name' => 'Sample Pet',
                'image_url' => 'https://example.com/sample-pet.png',
                'rarity' => 'Legendary',
                'price' => 100000,
                'stock' => 5,
                'featured' => true,
                'best_seller' => false,
                'weights' => [1.0, 2.0],
            ],
        ];
    }
}
