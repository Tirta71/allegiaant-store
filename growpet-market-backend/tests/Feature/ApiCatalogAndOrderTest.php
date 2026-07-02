<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TokenRate;
use App\Services\OrderPaymentConfirmationService;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiCatalogAndOrderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_catalog_endpoints_return_the_sample_product_and_variants(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $this->getJson('/api/products?search=Sample%20Pet')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['slug' => 'sample-pet'])
            ->assertJsonFragment(['type' => 'pet'])
            ->assertJsonPath('data.0.image_url', 'https://example.com/sample-pet.png')
            ->assertJsonPath('data.0.starting_price', 100000)
            ->assertJsonPath('data.0.total_stock', 30)
            ->assertJsonMissingPath('data.0.category')
            ->assertJsonMissingPath('data.0.base_price')
            ->assertJsonMissingPath('data.0.stock')
            ->assertJsonMissingPath('data.0.description')
            ->assertJsonMissingPath('data.0.perks')
            ->assertJsonMissingPath('data.0.accent_color')
            ->assertJsonMissingPath('data.0.soft_color');

        $this->getJson('/api/products/sample-pet')
            ->assertOk()
            ->assertJsonPath('data.slug', 'sample-pet')
            ->assertJsonPath('data.image_url', 'https://example.com/sample-pet.png')
            ->assertJsonPath('data.starting_price', 100000)
            ->assertJsonPath('data.total_stock', 30)
            ->assertJsonCount(6, 'data.variants')
            ->assertJsonMissingPath('data.category')
            ->assertJsonMissingPath('data.base_price')
            ->assertJsonMissingPath('data.stock')
            ->assertJsonMissingPath('data.description')
            ->assertJsonMissingPath('data.perks')
            ->assertJsonMissingPath('data.accent_color')
            ->assertJsonMissingPath('data.soft_color');

        $this->getJson('/api/products/sample-pet/variants')
            ->assertOk()
            ->assertJsonCount(6, 'data');
    }

    public function test_catalog_only_returns_pet_variants_with_stock(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $product = Product::query()->where('slug', 'sample-pet')->firstOrFail();
        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereHas('mutation', fn ($query) => $query->where('name', 'Venom'))
            ->where('weight_kg', 2)
            ->firstOrFail();

        $variant->update(['stock' => 0]);

        $this->getJson('/api/products/sample-pet')
            ->assertOk()
            ->assertJsonCount(5, 'data.variants')
            ->assertJsonMissing(['id' => $variant->id]);

        $this->getJson('/api/products/sample-pet/variants')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonMissing(['id' => $variant->id]);

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->update(['stock' => 0]);

        $this->getJson('/api/products?search=Sample%20Pet')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/products/sample-pet')
            ->assertNotFound();
    }

    public function test_frontend_can_create_order_and_receive_pakasir_webhook(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $variant = $this->sampleVariant();
        $product = $variant->product;

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'GardenBuyer01',
                'whatsapp' => '08123456789',
                'notes' => 'Test order',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $orderResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_payment')
            ->assertJsonPath('data.payment_instructions.method', 'pakasir')
            ->assertJsonPath('data.payment_instructions.order_amount', 100000)
            ->assertJsonPath('data.payment_instructions.fee', 1003)
            ->assertJsonPath('data.payment_instructions.total_payment', 101003)
            ->assertJsonPath('data.payment_instructions.amount', 101003)
            ->assertJsonPath('data.can_cancel', true)
            ->assertJsonCount(1, 'data.items');

        $this->assertStringStartsWith(
            '000201',
            $orderResponse->json('data.payment_instructions.qris_payload')
        );

        $variant->refresh();
        $product->refresh();

        $this->assertSame(4, $variant->stock);
        $this->assertSame(0, $variant->sales_count);
        $this->assertSame(0, $product->sales_count);

        $code = $orderResponse->json('data.code');

        $this->getJson("/api/orders/{$code}")
            ->assertOk()
            ->assertJsonPath('data.code', $code);

        config(['testing.pakasir_status' => 'completed']);

        $this->postJson('/api/pakasir/webhook', [
            'amount' => 100000,
            'order_id' => $code,
            'project' => 'test-project',
            'status' => 'completed',
            'payment_method' => 'qris',
            'completed_at' => now()->toISOString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'payment_confirmed');

        $variant->refresh();
        $product->refresh();

        $this->assertSame(4, $variant->stock);
        $this->assertSame(1, $variant->sales_count);
        $this->assertSame(1, $product->sales_count);

        $this->getJson("/api/orders/{$code}/status-history")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_buyer_can_cancel_pending_order_and_restore_reserved_stock(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $variant = $this->sampleVariant();

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'CancelBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();

        $variant->refresh();
        $this->assertSame(4, $variant->stock);

        $this->postJson("/api/orders/{$orderResponse->json('data.code')}/cancel", [
            'reason' => 'Buyer cancel dari test.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.can_cancel', false);

        $variant->refresh();
        $this->assertSame(5, $variant->stock);
        $this->assertDatabaseMissing('orders', [
            'code' => $orderResponse->json('data.code'),
        ]);
    }

    public function test_token_rate_omits_step_nominal_and_enforces_min_nominal(): void
    {
        $product = Product::query()->create([
            'slug' => 'test-token',
            'type' => Product::TYPE_TOKEN,
            'name' => 'Test Token',
            'active' => true,
        ]);

        $rate = TokenRate::query()->create([
            'product_id' => $product->id,
            'price_per_thousand' => 15000,
            'min_nominal' => 5000,
            'stock_token' => 50000,
            'active' => true,
        ]);

        $this->getJson('/api/products/test-token')
            ->assertOk()
            ->assertJsonPath('data.token_rate.id', $rate->id)
            ->assertJsonPath('data.token_rate.min_nominal', 5000)
            ->assertJsonPath('data.token_rate.stock_token', 50000)
            ->assertJsonMissingPath('data.token_rate.max_nominal')
            ->assertJsonMissingPath('data.token_rate.step_nominal');

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'TokenBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'token',
                    'product_id' => $product->id,
                    'token_rate_id' => $rate->id,
                    'nominal' => 4999,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'TokenBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'token',
                    'product_id' => $product->id,
                    'token_rate_id' => $rate->id,
                    'nominal' => 15000,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.items.0.token_amount', 1000);

        $rate->refresh();
        $this->assertSame(49000, $rate->stock_token);

        $order = Order::query()
            ->byCode($orderResponse->json('data.code'))
            ->firstOrFail();

        app(OrderPaymentConfirmationService::class)->confirm($order);

        $rate->refresh();

        $this->assertSame(49000, $rate->stock_token);

        $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'TokenBuyer02',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'token',
                    'product_id' => $product->id,
                    'token_rate_id' => $rate->id,
                    'nominal' => 750000,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_testimonials_return_delivered_orders_with_trade_proof(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $variant = $this->sampleVariant();

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'ProofBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();

        $order = Order::query()
            ->byCode($orderResponse->json('data.code'))
            ->firstOrFail();

        $order->update([
            'status' => Order::STATUS_DELIVERED,
            'delivery_proof_url' => '/storage/delivery-proofs/proof-buyer-01.png',
            'delivery_proof_uploaded_at' => now(),
        ]);

        $this->getJson('/api/testimonials')
            ->assertOk()
            ->assertJsonFragment(['roblox_username' => 'ProofBuyer01'])
            ->assertJsonFragment(['url' => '/storage/delivery-proofs/proof-buyer-01.png'])
            ->assertJsonFragment(['type' => 'pet'])
            ->assertJsonFragment(['name' => $variant->product->name])
            ->assertJsonFragment(['quantity' => 1]);
    }

    private function sampleVariant(): ProductVariant
    {
        return ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('slug', 'sample-pet'))
            ->available()
            ->firstOrFail();
    }
}
