# -*- coding: utf-8 -*-
"""
TechZone - Builder SQL & Gambar Placeholder
Mengubah data di gen_products.py menjadi:
  1. database/seed-products.sql  (INSERT categories update + products + product_images)
  2. assets/images/products/*.svg (gambar placeholder lokal, tanpa API/CDN eksternal)
"""
import json
import os
import re

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
IMG_DIR = os.path.join(ROOT, 'assets', 'images', 'products')
SQL_OUT = os.path.join(ROOT, 'database', 'seed-products.sql')


def slugify(text):
    text = text.strip().lower()
    text = re.sub(r'[^a-z0-9]+', '-', text)
    return text.strip('-')


def sql_escape(value):
    if value is None:
        return 'NULL'
    if isinstance(value, (int, float)):
        return str(value)
    text = str(value).replace('\\', '\\\\').replace("'", "\\'")
    return "'" + text + "'"


def make_svg(text, sku, color, out_path):
    """Membuat gambar placeholder SVG sederhana secara lokal (tanpa API eksternal)."""
    # Pecah nama produk jadi maksimal 3 baris agar muat di kotak gambar.
    words = text.split(' ')
    lines, current = [], ''
    for w in words:
        trial = (current + ' ' + w).strip()
        if len(trial) > 16:
            if current:
                lines.append(current)
            current = w
        else:
            current = trial
    if current:
        lines.append(current)
    lines = lines[:3]

    svg_lines = ''
    start_y = 200 - (len(lines) - 1) * 16
    for i, line in enumerate(lines):
        svg_lines += (
            f'<text x="200" y="{start_y + i * 32}" font-family="Arial, sans-serif" '
            f'font-size="24" font-weight="700" fill="#ffffff" text-anchor="middle">{line}</text>\n'
        )

    svg = f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{color}"/>
      <stop offset="100%" stop-color="#0a1128"/>
    </linearGradient>
  </defs>
  <rect width="400" height="400" fill="url(#g)"/>
  <circle cx="340" cy="60" r="70" fill="#ffffff" opacity="0.05"/>
  <circle cx="40" cy="360" r="90" fill="#ffffff" opacity="0.05"/>
  <rect x="150" y="120" width="100" height="70" rx="10" fill="#ffffff" opacity="0.12"/>
  <circle cx="170" cy="140" r="4" fill="#22d3ee"/>
  <circle cx="230" cy="140" r="4" fill="#22d3ee"/>
  <circle cx="170" cy="170" r="4" fill="#22d3ee"/>
  <circle cx="230" cy="170" r="4" fill="#22d3ee"/>
  {svg_lines}
  <text x="200" y="290" font-family="Arial, sans-serif" font-size="13" fill="#22d3ee" text-anchor="middle" letter-spacing="1">{sku}</text>
  <text x="200" y="370" font-family="Arial, sans-serif" font-size="12" fill="#94a3b8" text-anchor="middle">TechZone</text>
</svg>'''

    with open(out_path, 'w', encoding='utf-8') as f:
        f.write(svg)


def run(categories, products):
    os.makedirs(IMG_DIR, exist_ok=True)
    seen_sku = set()
    seen_slug = set()

    lines = []
    lines.append('-- =====================================================================')
    lines.append('-- TECHZONE - Seed Data Produk (60 Produk IoT)')
    lines.append('-- File ini di-generate dari database/_gen/gen_products.py')
    lines.append('-- Jalankan SETELAH schema.sql dan seed.sql berhasil di-import.')
    lines.append('-- =====================================================================')
    lines.append('')
    lines.append('USE techzone_db;')
    lines.append('')

    for p in products:
        slug = slugify(p['name'])
        if slug in seen_slug:
            slug = slug + '-' + p['sku'].lower()
        seen_slug.add(slug)

        if p['sku'] in seen_sku:
            raise ValueError('Duplicate SKU: ' + p['sku'])
        seen_sku.add(p['sku'])

        color = categories[p['cat']]['color']
        image_filename = slug + '.svg'
        make_svg(p['name'], p['sku'], color, os.path.join(IMG_DIR, image_filename))
        main_image = 'assets/images/products/' + image_filename

        specs_json = json.dumps(p['specs'], ensure_ascii=False)
        features_json = json.dumps(p['features'], ensure_ascii=False)
        applications_json = json.dumps(p['applications'], ensure_ascii=False)
        compat_json = json.dumps(p['compat'], ensure_ascii=False)
        package_json = json.dumps(p['package'], ensure_ascii=False)

        discount_sql = p['discount'] if p['discount'] is not None else 'NULL'

        insert = f"""INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = {sql_escape(p['cat'])}),
  {sql_escape(p['sku'])}, {sql_escape(p['name'])}, {sql_escape(slug)}, {sql_escape(p['brand'])},
  {sql_escape(p['short'])}, {sql_escape(p['desc'])}, {sql_escape(specs_json)}, {sql_escape(features_json)},
  {sql_escape(applications_json)}, {sql_escape(compat_json)}, {sql_escape(package_json)},
  {p['price']}, {discount_sql}, {p['stock']}, {p['weight']}, {sql_escape(p['dims'])},
  {sql_escape(main_image)}, 'active', {1 if p['featured'] else 0}, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = {sql_escape(p['sku'])});"""
        lines.append(insert)
        lines.append('')

    with open(SQL_OUT, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lines))

    print(f"Selesai: {len(products)} produk -> {SQL_OUT}")
    print(f"Gambar SVG placeholder dibuat di: {IMG_DIR}")
