<?php

namespace Tests\Feature;

use App\Models\Order;
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
            ->assertSee('>Confirm payment</button>', false)
            ->assertDontSee('>Delivered</button>', false);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update-status', $order), [
                'status' => Order::STATUS_PAYMENT_CONFIRMED,
                'status_note' => 'Payment dikonfirmasi dari shortcut admin.',
            ])
            ->assertRedirect();

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
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(Order::STATUS_DELIVERED, $order->status);
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
            ->assertRedirect();

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
            ->assertRedirect();

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
            ->assertRedirect(route('admin.products.index'));

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
            ->assertRedirect(route('admin.products.index'));

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
            ->assertRedirect();

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
            ->assertRedirect();

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
            ->assertRedirect();

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
