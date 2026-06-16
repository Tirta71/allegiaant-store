# Growpet Market Routes

## Admin Pages

Login admin default dari seeder:

```text
email: allegiaant@mail.store
password: hero1234
```

| Method | Route | Name | Fungsi |
| --- | --- | --- | --- |
| GET | `/admin/login` | `admin.login` | Form login admin |
| POST | `/admin/login` | `admin.login.store` | Proses login |
| POST | `/admin/logout` | `admin.logout` | Logout |
| GET | `/admin` | `admin.dashboard` | Dashboard |
| GET | `/admin/products` | `admin.products.index` | List produk pet/token |
| GET | `/admin/products/create` | `admin.products.create` | Form tambah produk |
| POST | `/admin/products` | `admin.products.store` | Simpan produk |
| GET | `/admin/products/{product}/edit` | `admin.products.edit` | Form edit produk |
| PUT/PATCH | `/admin/products/{product}` | `admin.products.update` | Update produk |
| DELETE | `/admin/products/{product}` | `admin.products.destroy` | Nonaktifkan produk |
| GET | `/admin/mutations` | `admin.mutations.index` | List dan form mutasi |
| POST | `/admin/mutations` | `admin.mutations.store` | Simpan mutasi |
| PUT/PATCH | `/admin/mutations/{mutation}` | `admin.mutations.update` | Update mutasi |
| DELETE | `/admin/mutations/{mutation}` | `admin.mutations.destroy` | Nonaktifkan mutasi |
| GET | `/admin/product-variants` | `admin.product-variants.index` | List dan form varian pet |
| POST | `/admin/product-variants` | `admin.product-variants.store` | Simpan varian pet |
| PUT/PATCH | `/admin/product-variants/{product_variant}` | `admin.product-variants.update` | Update varian pet |
| DELETE | `/admin/product-variants/{product_variant}` | `admin.product-variants.destroy` | Nonaktifkan varian |
| GET | `/admin/token-rates` | `admin.token-rates.index` | List dan form rate token |
| POST | `/admin/token-rates` | `admin.token-rates.store` | Simpan rate token |
| PUT/PATCH | `/admin/token-rates/{token_rate}` | `admin.token-rates.update` | Update rate token |
| DELETE | `/admin/token-rates/{token_rate}` | `admin.token-rates.destroy` | Nonaktifkan rate token |
| GET | `/admin/orders` | `admin.orders.index` | List order |
| GET | `/admin/orders/{order}` | `admin.orders.show` | Detail order |
| PATCH | `/admin/orders/{order}/status` | `admin.orders.update-status` | Update status order |
| PATCH | `/admin/payments/{payment}/confirm` | `admin.payments.confirm` | Confirm payment |

## Front-End API

Base URL:

```text
http://localhost:8000/api
```

| Method | Endpoint | Fungsi |
| --- | --- | --- |
| GET | `/api/products` | List produk aktif, pet dan token |
| GET | `/api/products?type=pet` | List pet saja |
| GET | `/api/products?type=token` | List token saja |
| GET | `/api/products?rarity=Legendary` | Filter rarity |
| GET | `/api/products?search=unicorn` | Search produk |
| GET | `/api/products?sort=name` | Sort nama |
| GET | `/api/products/{slug}` | Detail produk |
| GET | `/api/products/{slug}/variants` | List varian pet |
| GET | `/api/token-products/{slug}/rate` | Rate token aktif |
| POST | `/api/orders` | Buat order checkout |
| GET | `/api/orders/{code}` | Cek transaksi |
| POST | `/api/orders/{code}/cancel` | Batalkan order pending dan kembalikan stok |
| GET | `/api/orders/{code}/status-history` | Riwayat status |
| POST | `/api/orders/{code}/payment-proof` | Upload bukti payment manual |

### Create Order Payload

```json
{
  "buyer": {
    "roblox_username": "GardenBuyer01",
    "whatsapp": "08123456789",
    "notes": "Catatan delivery"
  },
  "items": [
    {
      "type": "pet",
      "product_variant_id": 1,
      "quantity": 2
    },
    {
      "type": "token",
      "product_id": 11,
      "token_rate_id": 1,
      "nominal": 30000
    }
  ]
}
```

### Upload Bukti Payment

```http
POST /api/orders/GPM-20260615-ABCDE/payment-proof
Content-Type: multipart/form-data

proof=@bukti-payment.jpg
```

Response:

```json
{
  "data": {
    "code": "GPM-20260615-ABCDE",
    "status": "pending_payment",
    "status_note": "Bukti payment sudah diupload. Menunggu konfirmasi admin.",
    "payments": [
      {
        "method": "qris",
        "amount": 100000,
        "status": "pending",
        "proof_url": "/storage/payment-proofs/GPM-20260615-ABCDE/bukti-payment.jpg"
      }
    ]
  }
}
```

### Cancel Order Pending

```http
POST /api/orders/GPM-20260615-ABCDE/cancel
Content-Type: application/json

{
  "reason": "Buyer membatalkan pesanan."
}
```

Order hanya bisa dibatalkan saat masih `pending_payment` dan belum upload bukti payment. Stok yang dikunci saat checkout akan dikembalikan, lalu data order dihapus.
