# Generator Data Produk (Development Tool)

Folder ini **bukan bagian dari aplikasi web** yang berjalan di runtime — folder ini adalah
tooling development yang dipakai satu kali untuk menghasilkan `database/seed-products.sql`
beserta 60 gambar placeholder SVG di `assets/images/products/`.

- `gen_products.py` — berisi data 60 produk IoT (deskripsi, spesifikasi, fitur, dsb).
- `build_sql.py` — mengubah data tersebut menjadi SQL INSERT + membuat gambar SVG lokal.

Jalankan dengan:
```
cd database/_gen
python3 gen_products.py
```

File ini aman dibiarkan di server (tidak bisa dieksekusi oleh Apache/PHP karena berekstensi
`.py`, bukan `.php`), namun boleh juga dihapus sebelum deploy ke hosting jika Anda ingin
struktur folder yang lebih ringkas — datanya sudah "dibekukan" di `database/seed-products.sql`.
