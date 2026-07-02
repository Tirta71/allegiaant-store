<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TokenRate;
use App\Models\User;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_login_and_open_dashboard(): void
    {
        User::query()->create([
            'name' => 'Test Admin',
            'email' => 'admin-test@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Login Admin');

        $this->post('/admin/login', [
            'email' => 'admin-test@growpet.test',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_admin_product_pages_use_variant_pricing_fields(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Catalog Admin',
            'email' => 'catalog-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        $this->get('/admin/products')
            ->assertOk()
            ->assertSee('sample-pet.png')
            ->assertSee('Hapus')
            ->assertDontSee('Nonaktifkan')
            ->assertSee('Total stok')
            ->assertSee('Terjual')
            ->assertDontSee('Harga dasar')
            ->assertDontSee('Stok summary');

        $this->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Status katalog')
            ->assertSee('Image URL')
            ->assertDontSee('Category')
            ->assertDontSee('Harga dasar')
            ->assertDontSee('Stok summary');

        $this->get('/admin/product-variants')
            ->assertOk()
            ->assertSee('Harga final')
            ->assertSee('Stok varian')
            ->assertSee('Hapus')
            ->assertDontSee('Aktif dan bisa dibeli')
            ->assertDontSee('Nonaktifkan')
            ->assertDontSee('name="sales_count"', false);

        $this->get('/admin/token-rates')
            ->assertOk()
            ->assertSee('Stok token')
            ->assertDontSee('Max nominal')
            ->assertDontSee('Step nominal');
    }

    public function test_admin_orders_show_and_filter_order_type(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Order Admin',
            'email' => 'order-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
        $tokenProduct = Product::query()->create([
            'slug' => 'order-filter-token',
            'type' => Product::TYPE_TOKEN,
            'name' => 'Order Filter Token',
            'active' => true,
        ]);
        $tokenRate = TokenRate::query()->create([
            'product_id' => $tokenProduct->id,
            'price_per_thousand' => 15000,
            'min_nominal' => 5000,
            'stock_token' => 50000,
            'active' => true,
        ]);
        $variant = $this->sampleVariant();

        $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'PetBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'TokenBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'token',
                    'product_id' => $tokenProduct->id,
                    'token_rate_id' => $tokenRate->id,
                    'nominal' => 15000,
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Jenis order')
            ->assertSee('Pet')
            ->assertSee('Token');

        $this->actingAs($admin)
            ->get('/admin/orders?type=token')
            ->assertOk()
            ->assertSee('TokenBuyer01')
            ->assertDontSee('PetBuyer01');
    }

    public function test_admin_order_index_realtime_payload_returns_history_markup(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Realtime Admin',
            'email' => 'realtime-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
        $variant = $this->sampleVariant();

        $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'RealtimeBuyer01',
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

        $response = $this->actingAs($admin)
            ->getJson(route('admin.orders.index', ['realtime' => 1]))
            ->assertOk()
            ->assertJsonStructure(['html', 'latest_order_id', 'total', 'refreshed_at']);

        $html = $response->json('html');

        $this->assertIsString($html);
        $this->assertStringContainsString('order-history-card', $html);
        $this->assertStringContainsString('RealtimeBuyer01', $html);
        $this->assertStringNotContainsString('admin-order-notifier', $html);
    }

    public function test_admin_order_stream_overlay_page_and_feed_show_new_orders(): void
    {
        config(['stream.order_overlay_token' => 'test-stream-token']);
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Overlay Admin',
            'email' => 'overlay-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
        $variant = $this->sampleVariant();

        $oldOrderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'OldOverlayBuyer01',
                'whatsapp' => '08111111111',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();
        $oldOrder = Order::query()
            ->byCode($oldOrderResponse->json('data.code'))
            ->with('payments')
            ->firstOrFail();
        $oldPayment = $oldOrder->payments->firstOrFail();
        $oldPayment->update([
            'status' => Payment::STATUS_CONFIRMED,
            'confirmed_at' => now()->subMinute(),
        ]);
        $oldOrder->update([
            'status' => Order::STATUS_PAYMENT_CONFIRMED,
            'paid_at' => $oldPayment->confirmed_at,
        ]);
        $oldPayment->refresh();

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'OverlayBuyer01',
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
            ->with('payments')
            ->firstOrFail();
        $payment = $order->payments->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.orders.stream-overlay'))
            ->assertOk()
            ->assertSee('Order Stream Overlay')
            ->assertSee('data-feed-url=', false)
            ->assertSee('data-latest-overlay-payment-id="' . $oldPayment->id . '"', false)
            ->assertSee('data-latest-overlay-payment-time=', false);

        $this->get(route('stream.order-overlay'))
            ->assertForbidden();

        $this->get(route('stream.order-overlay', ['token' => 'wrong-token']))
            ->assertForbidden();

        $this->get(route('stream.order-overlay', ['token' => 'test-stream-token']))
            ->assertOk()
            ->assertSee('Order Stream Overlay')
            ->assertSee('stream/order-overlay/feed?token=test-stream-token', false);

        $cursor = [
            'after_time' => $oldPayment->confirmed_at->toIso8601String(),
            'after_id' => $oldPayment->id,
        ];

        $this->actingAs($admin)
            ->getJson(route('admin.orders.stream-overlay.feed', $cursor))
            ->assertOk()
            ->assertJsonCount(0, 'orders');

        $payment->update([
            'status' => Payment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        $order->update([
            'status' => Order::STATUS_PAYMENT_CONFIRMED,
            'paid_at' => $payment->confirmed_at,
        ]);
        $payment->refresh();

        $newerOrderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'NewestOverlayBuyer01',
                'whatsapp' => '08999999999',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();
        $newerOrder = Order::query()
            ->byCode($newerOrderResponse->json('data.code'))
            ->with('payments')
            ->firstOrFail();
        $newerPayment = $newerOrder->payments->firstOrFail();
        $newerPayment->update([
            'status' => Payment::STATUS_CONFIRMED,
            'confirmed_at' => now()->addSecond(),
        ]);
        $newerOrder->update([
            'status' => Order::STATUS_PAYMENT_CONFIRMED,
            'paid_at' => $newerPayment->confirmed_at,
        ]);
        $newerPayment->refresh();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.orders.stream-overlay.feed', $cursor))
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $newerOrder->id)
            ->assertJsonPath('orders.0.payment_id', $newerPayment->id)
            ->assertJsonPath('orders.0.buyer', 'NewestOverlayBuyer01')
            ->assertJsonPath('orders.0.code', $newerOrder->code)
            ->assertJsonStructure(['orders', 'cursor', 'refreshed_at']);

        $this->getJson(route('stream.order-overlay.feed', [
            ...$cursor,
            'token' => 'test-stream-token',
        ]))
            ->assertOk()
            ->assertJsonPath('orders.0.id', $newerOrder->id)
            ->assertJsonPath('orders.0.payment_id', $newerPayment->id);

        $summary = $response->json('orders.0.item_summary');

        $this->assertMatchesRegularExpression('/^Sample Pet mutasi .+ \d+(,\d+)?kg$/', $summary);
        $this->assertStringNotContainsString('/', $summary);
        $this->assertStringNotContainsString('Qty', $summary);
        $this->assertStringNotContainsString('08123456789', $response->getContent());
        $this->assertStringNotContainsString($order->code, $response->getContent());

        $this->actingAs($admin)
            ->patch(route('admin.payments.confirm', $newerPayment))
            ->assertRedirect();

        $this->actingAs($admin)
            ->getJson(route('admin.orders.stream-overlay.feed', [
                'after_time' => $response->json('cursor.event_time'),
                'after_id' => $response->json('cursor.payment_id'),
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'orders');
    }

    public function test_admin_order_index_shows_payment_shortcuts_and_order_items(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Shortcut Admin',
            'email' => 'shortcut-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
        $variant = $this->sampleVariant();

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'ShortcutBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $order = Order::query()
            ->byCode($orderResponse->json('data.code'))
            ->with('payments')
            ->firstOrFail();
        $payment = $order->payments->firstOrFail();

        $payment->update([
            'proof_url' => 'https://example.com/proof.png',
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders?search=ShortcutBuyer01')
            ->assertOk()
            ->assertSee('Pesanan')
            ->assertSee('ShortcutBuyer01')
            ->assertSee('Sample Pet')
            ->assertSee('Lihat bukti upload')
            ->assertSee('data-proof-modal-trigger', false)
            ->assertSee('data-proof-url="https://example.com/proof.png"', false)
            ->assertDontSee('>Confirm payment</button>', false)
            ->assertDontSee('>Delivered</button>', false);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Menunggu gateway')
            ->assertDontSee('>Confirm payment</button>', false);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_PAYMENT_CONFIRMED,
                'status_note' => 'Payment dikonfirmasi dari shortcut admin.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Payment berhasil dikonfirmasi.');

        $order->refresh();

        $this->assertSame(Order::STATUS_PAYMENT_CONFIRMED, $order->status);

        $this->actingAs($admin)
            ->get('/admin/orders?search=ShortcutBuyer01')
            ->assertOk()
            ->assertSee('Payment confirmed')
            ->assertSee('Upload bukti trade')
            ->assertSee('Preview bukti trade')
            ->assertSee('>Upload trade</button>', false)
            ->assertSee('>Delivered</button>', false);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_DELIVERED,
                'status_note' => 'Order selesai delivery dari shortcut admin.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Status order berhasil diupdate.');

        $order->refresh();

        $this->assertSame(Order::STATUS_DELIVERED, $order->status);
    }

    public function test_admin_can_reset_invalid_payment_proof_for_reupload(): void
    {
        $this->seed(ProductCatalogSeeder::class);
        Storage::fake('public');

        $admin = User::query()->create([
            'name' => 'Reset Proof Admin',
            'email' => 'reset-proof-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
        $variant = $this->sampleVariant();

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'ResetProofBuyer01',
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
            ->with('payments')
            ->firstOrFail();
        $payment = $order->payments->firstOrFail();
        $payment->update(['method' => 'qris']);
        $proofPath = "payment-proofs/{$order->code}/invalid-proof.png";

        Storage::disk('public')->put($proofPath, 'invalid-payment-proof');

        $payment->update([
            'proof_url' => Storage::disk('public')->url($proofPath),
            'proof_uploaded_at' => now(),
        ]);
        $order->update([
            'status_note' => 'Bukti payment sudah diupload. Menunggu konfirmasi admin.',
            'payment_expires_at' => null,
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders?search=ResetProofBuyer01')
            ->assertOk()
            ->assertSee('Reset bukti');

        $this->actingAs($admin)
            ->patch(route('admin.payments.reset-proof', $payment))
            ->assertRedirect()
            ->assertSessionHas('status', 'Bukti payment direset. Buyer bisa upload ulang dalam 10 menit.');

        $payment->refresh();
        $order->refresh();

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertNull($payment->proof_url);
        $this->assertNotNull($payment->proof_uploaded_at);
        $this->assertNotNull($order->payment_expires_at);
        $this->assertTrue($order->payment_expires_at->isFuture());
        $this->assertSame('Bukti payment belum valid. Silakan upload ulang bukti payment.', $order->status_note);
        Storage::disk('public')->assertMissing($proofPath);

        $this->getJson('/api/orders/'.$order->code)
            ->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('data.status_note', 'Bukti payment belum valid. Silakan upload ulang bukti payment.')
            ->assertJsonPath('data.payments.0.proof_url', null);

        $this->actingAs($admin)
            ->get('/admin/orders?search=ResetProofBuyer01')
            ->assertOk()
            ->assertSee('Belum diupload')
            ->assertDontSee('Reset bukti');
    }

    public function test_payment_proof_reupload_does_not_trigger_stream_overlay(): void
    {
        config(['stream.order_overlay_token' => 'test-stream-token']);
        $this->seed(ProductCatalogSeeder::class);
        Storage::fake('public');

        $admin = User::query()->create([
            'name' => 'Overlay Reset Admin',
            'email' => 'overlay-reset-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
        $variant = $this->sampleVariant();

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'OverlayResetBuyer01',
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
            ->with('payments')
            ->firstOrFail();
        $payment = $order->payments->firstOrFail();
        $payment->update(['method' => 'qris']);
        $latestConfirmedPayment = Payment::query()
            ->where('status', Payment::STATUS_CONFIRMED)
            ->whereNotNull('confirmed_at')
            ->latest('confirmed_at')
            ->latest('id')
            ->first();
        $cursor = [
            'after_time' => $latestConfirmedPayment?->confirmed_at?->toIso8601String() ?? now()->subSecond()->toIso8601String(),
            'after_id' => $latestConfirmedPayment?->id ?? 0,
        ];

        $this->postJson("/api/orders/{$order->code}/payment-proof", [
            'proof' => UploadedFile::fake()->image('first-proof.png'),
        ])->assertOk();

        $payment->refresh();
        $firstProofUploadedAt = $payment->proof_uploaded_at;

        $this->actingAs($admin)
            ->getJson(route('admin.orders.stream-overlay.feed', $cursor))
            ->assertOk()
            ->assertJsonCount(0, 'orders');

        $this->actingAs($admin)
            ->patch(route('admin.payments.reset-proof', $payment))
            ->assertRedirect()
            ->assertSessionHas('status', 'Bukti payment direset. Buyer bisa upload ulang dalam 10 menit.');

        $this->postJson("/api/orders/{$order->code}/payment-proof", [
            'proof' => UploadedFile::fake()->image('second-proof.png'),
        ])->assertOk();

        $payment->refresh();

        $this->assertTrue($payment->proof_uploaded_at->equalTo($firstProofUploadedAt));

        $this->actingAs($admin)
            ->getJson(route('admin.orders.stream-overlay.feed', $cursor))
            ->assertOk()
            ->assertJsonCount(0, 'orders');
    }

    public function test_admin_can_upload_delivery_trade_proof_from_order_detail(): void
    {
        $this->seed(ProductCatalogSeeder::class);
        Storage::fake('public');

        $admin = User::query()->create([
            'name' => 'Delivery Proof Admin',
            'email' => 'delivery-proof-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);
        $variant = $this->sampleVariant();

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'TradeProofBuyer01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $order = Order::query()
            ->byCode($orderResponse->json('data.code'))
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_PAYMENT_CONFIRMED,
                'status_note' => 'Payment dikonfirmasi sebelum trade.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Payment berhasil dikonfirmasi.');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Bukti trade')
            ->assertSee('Paste screenshot atau pilih file');

        $this->actingAs($admin)
            ->post(route('admin.orders.delivery-proof', $order), [
                'delivery_proof' => UploadedFile::fake()->image('trade-proof.png'),
                'delivery_proof_note' => 'Trade selesai ke buyer.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Bukti trade berhasil diupload dan order ditandai delivered.');

        $order->refresh();

        $this->assertSame(Order::STATUS_DELIVERED, $order->status);
        $this->assertSame('Trade selesai ke buyer.', $order->delivery_proof_note);
        $this->assertNotNull($order->delivery_proof_uploaded_at);
        $this->assertNotNull($order->delivery_proof_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $order->delivery_proof_url));

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Bukti trade order', false)
            ->assertSee('Trade selesai ke buyer.')
            ->assertDontSee('Belum ada bukti trade')
            ->assertDontSee('Paste screenshot atau pilih file')
            ->assertDontSee('Upload bukti trade');

        $this->actingAs($admin)
            ->get('/admin/orders?search=TradeProofBuyer01')
            ->assertOk()
            ->assertSee('Lihat bukti trade')
            ->assertSee('data-proof-label="Bukti trade"', false)
            ->assertDontSee('Upload bukti trade')
            ->assertDontSee('Upload trade');

        $this->actingAs($admin)
            ->post(route('admin.orders.delivery-proof', $order), [
                'delivery_proof' => UploadedFile::fake()->image('trade-proof-again.png'),
                'delivery_proof_note' => 'Upload ulang.',
            ])
            ->assertSessionHasErrors('delivery_proof');
    }

    public function test_admin_deleting_product_removes_the_record(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Product Admin',
            'email' => 'product-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $product = Product::query()->where('slug', 'sample-pet')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Produk berhasil dihapus.');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_admin_product_form_cleans_wikia_revision_image_url(): void
    {
        $admin = User::query()->create([
            'name' => 'Image Admin',
            'email' => 'image-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $dirtyUrl = 'https://static.wikia.nocookie.net/growagarden/images/5/54/Raccon_Better_Quality.png/revision/latest?cb=20260121085435';
        $cleanUrl = 'https://static.wikia.nocookie.net/growagarden/images/5/54/Raccon_Better_Quality.png';

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'slug' => 'raccoon-clean-url',
                'type' => Product::TYPE_PET,
                'name' => 'Raccoon Clean URL',
                'image_url' => $dirtyUrl,
                'rarity' => 'Divine',
                'featured' => '0',
                'best_seller' => '0',
                'sort_order' => 1,
                'active' => '1',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status', 'Produk berhasil dibuat.');

        $this->assertDatabaseHas('products', [
            'slug' => 'raccoon-clean-url',
            'image_url' => $cleanUrl,
        ]);
    }

    public function test_admin_deleting_product_variant_removes_the_record(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Variant Admin',
            'email' => 'variant-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $variant = $this->sampleVariant();

        $this->actingAs($admin)
            ->delete(route('admin.product-variants.destroy', $variant))
            ->assertRedirect()
            ->assertSessionHas('status', 'Varian pet berhasil dihapus.');

        $this->assertDatabaseMissing('product_variants', [
            'id' => $variant->id,
        ]);
    }

    public function test_admin_payment_confirmation_updates_variant_stock_and_sales(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Payment Admin',
            'email' => 'payment-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $variant = $this->sampleVariant();
        $product = $variant->product;

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'AdminConfirm01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $order = Order::query()
            ->byCode($orderResponse->json('data.code'))
            ->with('payments')
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.payments.confirm', $order->payments->first()))
            ->assertRedirect()
            ->assertSessionHas('status', 'Payment berhasil dikonfirmasi.');

        $variant->refresh();
        $product->refresh();
        $order->refresh();

        $this->assertSame(Order::STATUS_PAYMENT_CONFIRMED, $order->status);
        $this->assertSame(4, $variant->stock);
        $this->assertSame(1, $variant->sales_count);
        $this->assertSame(1, $product->sales_count);
    }

    public function test_admin_status_update_to_payment_confirmed_updates_variant_stock_and_sales(): void
    {
        $this->seed(ProductCatalogSeeder::class);

        $admin = User::query()->create([
            'name' => 'Status Admin',
            'email' => 'status-admin@growpet.test',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $variant = $this->sampleVariant();
        $product = $variant->product;

        $orderResponse = $this->postJson('/api/orders', [
            'buyer' => [
                'roblox_username' => 'StatusConfirm01',
                'whatsapp' => '08123456789',
            ],
            'items' => [
                [
                    'type' => 'pet',
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $order = Order::query()
            ->byCode($orderResponse->json('data.code'))
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_PAYMENT_CONFIRMED,
                'status_note' => 'Payment dikonfirmasi lewat status.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Payment berhasil dikonfirmasi.');

        $variant->refresh();
        $product->refresh();
        $order->refresh();

        $this->assertSame(Order::STATUS_PAYMENT_CONFIRMED, $order->status);
        $this->assertSame(4, $variant->stock);
        $this->assertSame(1, $variant->sales_count);
        $this->assertSame(1, $product->sales_count);
    }

    private function sampleVariant(): ProductVariant
    {
        return ProductVariant::query()
            ->whereHas('product', fn ($query) => $query->where('slug', 'sample-pet'))
            ->available()
            ->firstOrFail();
    }
}
