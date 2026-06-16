# Growpet Market Database Schema

Schema ini mengikuti alur front-end `growpet-market`:

- Market menampilkan pet dan token dari `products`.
- Detail pet memakai `product_variants` untuk harga final berdasarkan mutasi dan berat kg.
- Token memakai `token_rates`, karena jumlah token dihitung dari nominal input user.
- Checkout membuat `orders` dan `order_items`.
- Payment dicatat di `payments`.
- Cek transaksi membaca `orders.code`, `orders.status`, dan histori status.
- How-to-buy tidak dibuat table database; tetap cocok disimpan sebagai raw/static data di front-end.

## Catalog

### products

Menyimpan semua produk yang tampil di katalog.

- `type = pet` untuk produk pet.
- `type = token` untuk produk token.
- Harga dan stok pet disimpan di `product_variants`, bukan di `products`.
- `sales_count` terisi otomatis saat payment dikonfirmasi.

### mutations

Master mutasi pet, misalnya:

- Nightmare
- Venom
- Rainbow

`price_modifier` hanya fallback/helper. Harga final tetap disimpan eksplisit di `product_variants.price`.

### product_variants

Tabel wajib untuk pet karena setiap kombinasi pet + mutasi + berat kg bisa punya harga dan stok berbeda.

Unique key:

```text
product_id + mutation_id + weight_kg
```

Contoh:

```text
Unicorn + Nightmare + 1.40 kg = 145000
Unicorn + Venom + 1.40 kg = 160000
Unicorn + Rainbow + 1.40 kg = 180000
```

### token_rates

Khusus produk token. Front-end saat ini menghitung token dengan rumus:

```text
floor(nominal / price_per_thousand * 1000)
```

Contoh:

```text
price_per_thousand = 15000
nominal = 30000
token_amount = 2000
```

## Order Flow

### orders

Menyimpan data checkout:

- Roblox username
- WhatsApp
- notes
- subtotal
- total
- total items
- status transaksi
- kode transaksi
- waktu bayar

Kode transaksi di front-end lama dibuat seperti `GPM-20260614-ABCDE`. Di backend nanti sebaiknya dibuat server-side.

### order_items

Menyimpan item order, baik pet maupun token.

Untuk pet:

- `product_variant_id` terisi.
- `mutation_name_snapshot` terisi.
- `weight_kg_snapshot` terisi.
- `token_*` kosong.

Untuk token:

- `token_rate_id` terisi.
- `token_amount_snapshot` terisi.
- `token_rate_snapshot` terisi.
- `product_variant_id` kosong.

Snapshot dipakai agar histori transaksi tidak berubah saat katalog/harga diubah.

### payments

Menyimpan status payment:

- pending
- confirmed
- failed
- refunded

Saat user upload bukti payment dari frontend, backend menyimpan `proof_url` dan order tetap berada di status `pending_payment`.
Saat order dibuat, backend langsung mengunci stok dan mengisi `payment_expires_at` selama 10 menit.
Jika buyer membatalkan atau timer habis, backend mengembalikan stok lalu menghapus order beserta item, payment, dan histori statusnya.
Admin kemudian melakukan confirm payment. Pada tahap confirm, backend mengisi `orders.paid_at`, mengubah status order, dan menaikkan sales count tanpa mengurangi stok lagi.

### order_status_histories

Histori perubahan status order. Ini berguna untuk halaman cek transaksi dan admin panel nanti.
