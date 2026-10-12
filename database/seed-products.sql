-- =====================================================================
-- TECHZONE - Seed Data Produk (60 Produk IoT)
-- File ini di-generate dari database/_gen/gen_products.py
-- Jalankan SETELAH schema.sql dan seed.sql berhasil di-import.
-- =====================================================================

USE techzone_db;

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-ESP32-001', 'ESP32 DevKit V1', 'esp32-devkit-v1', 'Espressif',
  'Development board WiFi + Bluetooth dual-core untuk berbagai proyek IoT.', 'ESP32 DevKit V1 merupakan development board berbasis chip ESP32 yang dirancang untuk kebutuhan pengembangan Internet of Things. Board ini memiliki konektivitas WiFi dan Bluetooth bawaan sehingga cocok digunakan untuk sistem monitoring, smart home, automation, sensor network, dan berbagai proyek IoT lainnya. Prosesor dual-core Tensilica LX6 membuatnya mampu menjalankan tugas multitasking seperti membaca sensor sekaligus mengirim data ke server secara bersamaan.', '{"Chip": "ESP32-WROOM-32", "CPU": "Dual-core Tensilica LX6 240MHz", "Konektivitas": "WiFi 802.11 b/g/n, Bluetooth 4.2 BLE", "GPIO": "30 pin", "Flash": "4MB", "Tegangan Input": "5V (USB) / 3.3V-6V (VIN)", "Interface": "UART, SPI, I2C, PWM, ADC, DAC"}', '["WiFi", "Bluetooth", "Dual-core 240MHz", "GPIO 30 pin", "PWM", "ADC 12-bit", "DAC", "UART", "SPI", "I2C", "Deep sleep mode"]',
  '["Smart Home", "Monitoring IoT", "Automation", "Robotika", "Prototyping", "Edukasi elektronika"]', '["Arduino IDE", "PlatformIO", "MicroPython", "Breadboard standar"]', '["1x ESP32 DevKit V1", "Dokumentasi singkat"]',
  68000, 59000, 120, 40, '55 x 28 x 13 mm',
  'assets/images/products/esp32-devkit-v1.svg', 'active', 1, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-ESP32-001');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-ESP32-002', 'ESP32-WROOM-32', 'esp32-wroom-32', 'Espressif',
  'Modul chip ESP32 mentah untuk diintegrasikan ke PCB custom Anda.', 'ESP32-WROOM-32 adalah modul chip inti dari keluarga ESP32 yang banyak dipakai sebagai basis pembuatan development board maupun produk IoT custom. Berbeda dengan DevKit V1 yang sudah lengkap dengan header pin dan regulator, modul ini ditujukan bagi yang ingin merancang PCB sendiri dengan komponen minimum, cocok untuk produksi perangkat IoT dalam skala kecil hingga menengah.', '{"Chip": "ESP32-D0WDQ6", "CPU": "Dual-core 240MHz", "RAM": "520KB SRAM", "Flash": "4MB", "Antena": "PCB trace antenna", "Konektivitas": "WiFi + Bluetooth 4.2", "Suhu Operasi": "-40°C hingga 85°C"}', '["WiFi", "Bluetooth", "Ukuran sangat kompak", "Cocok untuk custom PCB", "Konsumsi daya rendah", "Shielded module"]',
  '["Produk IoT custom", "Perangkat wearable", "Sensor node industri", "Prototyping PCB"]', '["KiCad/Eagle footprint tersedia online", "Solder reflow standar"]', '["1x Modul ESP32-WROOM-32"]',
  55000, NULL, 90, 10, '18 x 25.5 x 3.1 mm',
  'assets/images/products/esp32-wroom-32.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-ESP32-002');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-ESP32-003', 'ESP32-C3 Development Board', 'esp32-c3-development-board', 'Espressif',
  'Varian ESP32 RISC-V single-core, lebih hemat daya dan ringkas.', 'ESP32-C3 Development Board menggunakan arsitektur RISC-V single-core yang lebih ringkas dibanding ESP32 klasik, namun tetap mendukung WiFi dan Bluetooth Low Energy (BLE) 5.0. Cocok untuk proyek IoT yang mengutamakan efisiensi daya dan ukuran board yang lebih kecil, seperti sensor node bertenaga baterai.', '{"Chip": "ESP32-C3", "CPU": "RISC-V single-core 160MHz", "Konektivitas": "WiFi 802.11 b/g/n, Bluetooth 5.0 (LE)", "GPIO": "13 pin", "Flash": "4MB", "USB": "USB-C native"}', '["WiFi", "BLE 5.0", "RISC-V core", "USB-C", "Ukuran kompak", "Hemat daya"]',
  '["Sensor node baterai", "Wearable IoT", "Smart tag", "Proyek edukasi RISC-V"]', '["Arduino IDE", "ESP-IDF", "MicroPython"]', '["1x ESP32-C3 Dev Board", "Kabel USB-C (opsional)"]',
  62000, NULL, 75, 15, '25.5 x 20 x 5 mm',
  'assets/images/products/esp32-c3-development-board.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-ESP32-003');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-ESP8266-001', 'ESP8266 NodeMCU', 'esp8266-nodemcu', 'Espressif',
  'Board WiFi ekonomis, pilihan populer untuk proyek IoT skala kecil.', 'ESP8266 NodeMCU adalah salah satu development board WiFi paling populer di kalangan pemula karena harganya yang terjangkau dan komunitas yang besar. Cocok digunakan untuk proyek IoT sederhana seperti kontrol lampu jarak jauh, monitoring suhu ruangan, atau sebagai web server mini untuk kendali perangkat rumah.', '{"Chip": "ESP8266EX", "CPU": "Single-core 80/160MHz", "GPIO": "11 pin (fungsional)", "Flash": "4MB", "Konektivitas": "WiFi 802.11 b/g/n", "Tegangan": "5V via micro USB"}', '["WiFi", "Harga ekonomis", "Komunitas besar", "Kompatibel Arduino IDE", "Built-in USB to Serial"]',
  '["Smart plug/lampu", "Web server mini", "Monitoring suhu ruangan", "Edukasi IoT pemula"]', '["Arduino IDE", "NodeMCU firmware (Lua)", "MicroPython"]', '["1x ESP8266 NodeMCU"]',
  38000, 32000, 150, 25, '58 x 31 x 13 mm',
  'assets/images/products/esp8266-nodemcu.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-ESP8266-001');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-ARD-UNO', 'Arduino Uno R3', 'arduino-uno-r3', 'Arduino',
  'Board mikrokontroler paling ikonik untuk belajar dan prototyping elektronika.', 'Arduino Uno R3 adalah board mikrokontroler berbasis ATmega328P yang menjadi standar de-facto dunia elektronika hobi maupun edukasi. Meski tidak memiliki WiFi/Bluetooth bawaan, Uno R3 tetap relevan untuk proyek yang membutuhkan kontrol I/O sederhana dan dapat dipadukan dengan modul komunikasi tambahan untuk konektivitas IoT.', '{"Chip": "ATmega328P", "Clock": "16MHz", "GPIO Digital": "14 pin (6 PWM)", "GPIO Analog": "6 pin", "Memori Flash": "32KB", "Tegangan Operasi": "5V"}', '["14 digital I/O", "6 analog input", "6 PWM output", "USB Type-B", "Header standar shield Arduino"]',
  '["Kontrol motor sederhana", "Robotika dasar", "Edukasi elektronika", "Prototyping sirkuit digital"]', '["Arduino IDE", "Ribuan shield Arduino di pasaran"]', '["1x Arduino Uno R3", "Kabel USB Type-B (opsional)"]',
  95000, NULL, 100, 25, '68.6 x 53.4 x 12 mm',
  'assets/images/products/arduino-uno-r3.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-ARD-UNO');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-ARD-NANO', 'Arduino Nano', 'arduino-nano', 'Arduino',
  'Versi mini Arduino Uno, cocok untuk proyek dengan ruang terbatas.', 'Arduino Nano memiliki chip dan fitur yang serupa dengan Arduino Uno namun dalam ukuran yang jauh lebih kecil, membuatnya ideal untuk proyek yang membutuhkan efisiensi ruang seperti wearable device sederhana atau instalasi di dalam enclosure kecil.', '{"Chip": "ATmega328P", "Clock": "16MHz", "GPIO Digital": "14 pin", "GPIO Analog": "8 pin", "USB": "Mini/Micro USB"}', '["Ukuran mini", "Kompatibel breadboard", "8 analog input", "Solder header sendiri"]',
  '["Wearable sederhana", "Instalasi ruang terbatas", "Robotika mini", "Edukasi"]', '["Arduino IDE", "Breadboard"]', '["1x Arduino Nano"]',
  65000, NULL, 110, 7, '43.2 x 18.5 x 5 mm',
  'assets/images/products/arduino-nano.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-ARD-NANO');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-ARD-MEGA', 'Arduino Mega 2560', 'arduino-mega-2560', 'Arduino',
  'Board Arduino dengan jumlah pin I/O terbanyak untuk proyek kompleks.', 'Arduino Mega 2560 dirancang untuk proyek yang membutuhkan jumlah pin input/output lebih banyak dibanding Uno, seperti printer 3D, CNC controller, atau sistem robotika dengan banyak sensor dan aktuator sekaligus.', '{"Chip": "ATmega2560", "Clock": "16MHz", "GPIO Digital": "54 pin (15 PWM)", "GPIO Analog": "16 pin", "Memori Flash": "256KB", "UART": "4 buah"}', '["54 digital I/O", "16 analog input", "4 hardware UART", "Memori besar", "Kompatibel shield Arduino"]',
  '["3D printer controller", "CNC controller", "Robotika multi-sensor", "Sistem automation kompleks"]', '["Arduino IDE", "Shield Arduino Mega"]', '["1x Arduino Mega 2560"]',
  175000, 159000, 55, 37, '101.5 x 53.3 x 15 mm',
  'assets/images/products/arduino-mega-2560.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-ARD-MEGA');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-RPI-PICO', 'Raspberry Pi Pico', 'raspberry-pi-pico', 'Raspberry Pi',
  'Board mikrokontroler mungil bertenaga chip RP2040 dual-core.', 'Raspberry Pi Pico menggunakan chip RP2040 buatan Raspberry Pi Foundation sendiri dengan dua core ARM Cortex-M0+. Board ini menawarkan performa tinggi dengan harga terjangkau, mendukung pemrograman dengan MicroPython maupun C/C++, cocok untuk proyek kontrol presisi dan pemrosesan sinyal ringan.', '{"Chip": "RP2040", "CPU": "Dual-core ARM Cortex-M0+ 133MHz", "GPIO": "26 pin", "RAM": "264KB", "Flash": "2MB"}', '["Dual-core Cortex-M0+", "PIO (Programmable I/O)", "Harga terjangkau", "MicroPython & C/C++"]',
  '["Kontrol presisi motor", "Pemrosesan sinyal ringan", "Edukasi pemrograman embedded"]', '["MicroPython", "C/C++ SDK", "Arduino IDE (dengan board package)"]', '["1x Raspberry Pi Pico"]',
  58000, NULL, 80, 3, '51 x 21 x 3.9 mm',
  'assets/images/products/raspberry-pi-pico.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-RPI-PICO');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-RPI-PICOW', 'Raspberry Pi Pico W', 'raspberry-pi-pico-w', 'Raspberry Pi',
  'Raspberry Pi Pico dengan tambahan konektivitas WiFi bawaan.', 'Raspberry Pi Pico W adalah versi Pico dengan tambahan modul WiFi terintegrasi, membuka kemungkinan proyek IoT yang membutuhkan performa RP2040 sekaligus konektivitas nirkabel, seperti dashboard monitoring cuaca yang mengirim data ke internet.', '{"Chip": "RP2040 + Infineon CYW43439 WiFi", "CPU": "Dual-core Cortex-M0+ 133MHz", "Konektivitas": "WiFi 802.11 b/g/n", "GPIO": "26 pin", "RAM": "264KB"}', '["WiFi bawaan", "Dual-core Cortex-M0+", "PIO", "MicroPython & C/C++"]',
  '["Dashboard monitoring cuaca IoT", "Smart sensor WiFi", "Edukasi IoT"]', '["MicroPython", "C/C++ SDK", "Arduino IDE"]', '["1x Raspberry Pi Pico W"]',
  78000, NULL, 70, 3, '51 x 21 x 3.9 mm',
  'assets/images/products/raspberry-pi-pico-w.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-RPI-PICOW');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'mikrokontroler'),
  'MCU-STM32-BP', 'STM32 Blue Pill', 'stm32-blue-pill', 'STMicroelectronics',
  'Board development STM32F103 dengan performa ARM Cortex-M3.', 'STM32 Blue Pill adalah board development ekonomis berbasis chip STM32F103C8T6 dengan arsitektur ARM Cortex-M3. Populer di kalangan penghobi yang ingin bereksperimen dengan ekosistem STM32 tanpa biaya tinggi, cocok untuk proyek yang membutuhkan presisi timing dan kecepatan pemrosesan lebih tinggi dari Arduino Uno.', '{"Chip": "STM32F103C8T6", "CPU": "ARM Cortex-M3 72MHz", "Flash": "64-128KB", "GPIO": "37 pin", "ADC": "2x 12-bit"}', '["ARM Cortex-M3", "Clock 72MHz", "Banyak timer hardware", "Harga ekonomis"]',
  '["Kontrol motor presisi", "Pemrosesan sinyal cepat", "Edukasi ARM Cortex-M"]', '["STM32CubeIDE", "Arduino IDE (STM32duino)", "PlatformIO"]', '["1x STM32 Blue Pill"]',
  45000, NULL, 60, 8, '53 x 22 x 10 mm',
  'assets/images/products/stm32-blue-pill.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MCU-STM32-BP');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-DHT11', 'DHT11', 'dht11', 'Generic',
  'Sensor suhu dan kelembaban dasar untuk proyek pemantauan ruangan.', 'DHT11 adalah sensor suhu dan kelembaban paling umum digunakan untuk proyek pemula karena harganya murah dan mudah diprogram. Meski akurasinya lebih rendah dibanding DHT22, sensor ini cukup memadai untuk kebutuhan monitoring ruangan sehari-hari seperti weather station sederhana atau sistem kontrol kipas otomatis.', '{"Rentang Suhu": "0-50°C (±2°C)", "Rentang Kelembaban": "20-90% RH (±5%)", "Interface": "Single-wire digital", "Tegangan": "3-5.5V", "Sampling Rate": "1 Hz"}', '["Sensor suhu", "Sensor kelembaban", "Digital output", "Harga ekonomis"]',
  '["Weather station sederhana", "Kontrol kipas otomatis", "Monitoring ruangan", "Edukasi sensor"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Sensor DHT11", "Header pin (tipe modul)"]',
  15000, NULL, 200, 5, '15.5 x 12 x 5.5 mm',
  'assets/images/products/dht11.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-DHT11');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-DHT22', 'DHT22', 'dht22', 'Generic',
  'Sensor suhu dan kelembaban dengan akurasi lebih tinggi dari DHT11.', 'DHT22 (juga dikenal sebagai AM2302) menawarkan akurasi dan rentang pengukuran yang lebih baik dibanding DHT11, menjadikannya pilihan tepat untuk aplikasi yang membutuhkan data lebih presisi seperti greenhouse monitoring atau inkubator.', '{"Rentang Suhu": "-40 - 80°C (±0.5°C)", "Rentang Kelembaban": "0-100% RH (±2-5%)", "Interface": "Single-wire digital", "Tegangan": "3.3-6V", "Sampling Rate": "0.5 Hz"}', '["Akurasi tinggi", "Rentang suhu lebar", "Digital output", "Cocok outdoor ringan"]',
  '["Greenhouse monitoring", "Inkubator telur", "Weather station presisi", "Sistem HVAC sederhana"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Sensor DHT22", "Resistor pull-up 10K (tipe bare)"]',
  35000, NULL, 150, 5, '15.5 x 12 x 5.5 mm',
  'assets/images/products/dht22.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-DHT22');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-BME280', 'BME280', 'bme280', 'Bosch',
  'Sensor 3-in-1: suhu, kelembaban, dan tekanan udara dengan interface I2C/SPI.', 'BME280 adalah sensor lingkungan dari Bosch yang mengukur tiga parameter sekaligus: suhu, kelembaban, dan tekanan udara/altitude. Sensor ini banyak dipakai pada proyek weather station modern maupun drone karena akurasi tinggi dan konsumsi daya yang sangat rendah.', '{"Suhu": "-40-85°C (±1°C)", "Kelembaban": "0-100% RH (±3%)", "Tekanan": "300-1100 hPa", "Interface": "I2C/SPI", "Tegangan": "1.8-3.6V", "Konsumsi Daya": "3.6µA @1Hz"}', '["3-in-1 sensor", "I2C & SPI", "Konsumsi daya sangat rendah", "Akurasi tinggi"]',
  '["Weather station modern", "Estimasi ketinggian drone", "Monitoring indoor air quality dasar"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Modul BME280", "Header pin"]',
  42000, NULL, 130, 3, '11.5 x 15 x 4 mm',
  'assets/images/products/bme280.svg', 'active', 1, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-BME280');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-BMP280', 'BMP280', 'bmp280', 'Bosch',
  'Sensor tekanan udara dan suhu, versi lebih ekonomis dari BME280.', 'BMP280 mengukur tekanan udara dan suhu tanpa sensor kelembaban, menjadikannya pilihan ekonomis untuk proyek yang hanya membutuhkan data altitude/tekanan seperti altimeter DIY atau sistem navigasi drone tingkat dasar.', '{"Tekanan": "300-1100 hPa (±1 hPa)", "Suhu": "-40-85°C", "Interface": "I2C/SPI", "Tegangan": "1.8-3.6V"}', '["Sensor tekanan presisi", "I2C & SPI", "Estimasi altitude", "Harga ekonomis"]',
  '["Altimeter DIY", "Navigasi drone dasar", "Prediksi cuaca sederhana"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Modul BMP280", "Header pin"]',
  28000, NULL, 140, 3, '11.5 x 15 x 4 mm',
  'assets/images/products/bmp280.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-BMP280');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-HCSR04', 'HC-SR04', 'hc-sr04', 'Generic',
  'Sensor jarak ultrasonik dengan jangkauan hingga 4 meter.', 'HC-SR04 mengukur jarak menggunakan gelombang ultrasonik, memancarkan sinyal dan menghitung waktu pantulnya untuk menentukan jarak ke objek. Sangat populer untuk proyek robotika penghindar rintangan dan sistem parkir otomatis.', '{"Jangkauan": "2cm - 400cm", "Akurasi": "±3mm", "Sudut Deteksi": "15 derajat", "Tegangan": "5V", "Trigger Input": "Pulsa TTL 10µs"}', '["Jangkauan hingga 4 meter", "Akurasi milimeter", "Trigger/Echo pin", "Harga terjangkau"]',
  '["Robot penghindar rintangan", "Sistem parkir otomatis", "Level ketinggian air non-kontak"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Sensor HC-SR04"]',
  18000, NULL, 220, 10, '45 x 20 x 15 mm',
  'assets/images/products/hc-sr04.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-HCSR04');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-PIR', 'PIR Motion Sensor', 'pir-motion-sensor', 'Generic',
  'Sensor gerak inframerah pasif untuk deteksi keberadaan manusia.', 'PIR Motion Sensor mendeteksi perubahan radiasi inframerah dari tubuh manusia atau hewan yang bergerak dalam jangkauannya. Modul ini menjadi komponen inti pada sistem lampu otomatis, alarm keamanan, dan smart home berbasis deteksi kehadiran.', '{"Jangkauan Deteksi": "3-7 meter (adjustable)", "Sudut Deteksi": "120 derajat", "Tegangan": "5-20V DC", "Delay Output": "Adjustable via trimpot"}', '["Deteksi gerak inframerah pasif", "Sensitivitas dapat diatur", "Delay dapat diatur", "Low power"]',
  '["Lampu otomatis", "Alarm keamanan", "Smart home occupancy sensor"]', '["Arduino", "ESP32", "ESP8266", "Relay module"]', '["1x Sensor PIR HC-SR501", "Lensa fresnel terpasang"]',
  16000, NULL, 190, 8, '32 x 24 x 25 mm',
  'assets/images/products/pir-motion-sensor.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-PIR');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-MQ2', 'MQ-2', 'mq-2', 'Generic',
  'Sensor gas untuk mendeteksi asap, LPG, propana, dan metana.', 'MQ-2 adalah sensor gas semikonduktor yang sensitif terhadap asap dan berbagai gas mudah terbakar seperti LPG, propana, metana, dan hidrogen. Banyak dipakai pada sistem peringatan dini kebocoran gas dan deteksi kebakaran.', '{"Gas Terdeteksi": "LPG, Propana, Metana, Asap, Hidrogen", "Rentang Deteksi": "300-10000 ppm", "Tegangan": "5V", "Output": "Analog & Digital (adjustable threshold)"}', '["Multi-gas sensor", "Output analog & digital", "Preheat time singkat", "Sensitivitas dapat diatur"]',
  '["Alarm kebocoran gas dapur", "Deteksi asap kebakaran", "Sistem keamanan pabrik"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Sensor MQ-2"]',
  17000, NULL, 160, 8, '32 x 20 x 22 mm',
  'assets/images/products/mq-2.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-MQ2');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-MQ135', 'MQ-135', 'mq-135', 'Generic',
  'Sensor kualitas udara untuk mendeteksi CO2, amonia, dan benzena.', 'MQ-135 dirancang khusus untuk memonitor kualitas udara, sensitif terhadap gas seperti amonia, benzena, alkohol, asap, dan CO2. Cocok digunakan pada proyek air quality monitor untuk ruangan tertutup maupun aplikasi industri.', '{"Gas Terdeteksi": "NH3, Benzena, Alkohol, CO2, Asap", "Rentang Deteksi": "10-1000 ppm", "Tegangan": "5V", "Output": "Analog & Digital"}', '["Sensor kualitas udara", "Multi-gas", "Output analog & digital"]',
  '["Air quality monitor indoor", "Deteksi polusi ruangan", "Monitoring gudang bahan kimia"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Sensor MQ-135"]',
  19000, NULL, 140, 8, '32 x 20 x 22 mm',
  'assets/images/products/mq-135.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-MQ135');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-LDR', 'LDR Sensor', 'ldr-sensor', 'Generic',
  'Modul sensor cahaya (Light Dependent Resistor) untuk deteksi intensitas cahaya.', 'Modul LDR mengukur intensitas cahaya di sekitarnya dengan memanfaatkan perubahan resistansi komponen LDR. Sensor ini umum dipakai pada proyek lampu jalan otomatis yang menyala saat gelap dan mati saat terang.', '{"Komponen": "Photoresistor (LDR)", "Output": "Analog & Digital (dengan trimpot)", "Tegangan": "3.3-5V"}', '["Deteksi intensitas cahaya", "Output analog & digital", "Trimpot sensitivitas", "Harga sangat murah"]',
  '["Lampu jalan otomatis", "Tirai otomatis", "Robot line-avoiding sederhana"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Modul LDR"]',
  8000, NULL, 250, 5, '32 x 15 x 15 mm',
  'assets/images/products/ldr-sensor.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-LDR');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-SOIL', 'Soil Moisture Sensor', 'soil-moisture-sensor', 'Generic',
  'Sensor kelembaban tanah untuk sistem penyiraman tanaman otomatis.', 'Soil Moisture Sensor mengukur tingkat kelembaban tanah dengan mendeteksi konduktivitas antara dua probe logam. Menjadi komponen kunci pada proyek smart gardening yang menyiram tanaman secara otomatis berdasarkan kondisi tanah aktual.', '{"Output": "Analog & Digital", "Tegangan": "3.3-5V", "Bahan Probe": "Nikel/Tembaga berlapis"}', '["Deteksi kelembaban tanah", "Output analog & digital", "Cocok pot maupun lahan kecil"]',
  '["Smart gardening", "Penyiraman otomatis", "Monitoring pertanian skala kecil"]', '["Arduino", "ESP32", "ESP8266", "Relay + pompa air mini"]', '["1x Sensor probe", "1x Modul komparator"]',
  12000, NULL, 180, 10, '60 x 20 x 5 mm (probe)',
  'assets/images/products/soil-moisture-sensor.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-SOIL');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-RAIN', 'Rain Sensor', 'rain-sensor', 'Generic',
  'Modul sensor hujan untuk deteksi tetesan air pada permukaan papan sensor.', 'Rain Sensor terdiri dari papan sensor dengan jalur konduktif yang berubah resistansinya saat terkena air hujan. Digunakan pada sistem otomatisasi seperti penutup jemuran otomatis atau notifikasi cuaca hujan pada sistem smart home.', '{"Output": "Analog & Digital", "Tegangan": "5V", "Sensitivitas": "Adjustable via trimpot"}', '["Deteksi tetesan air", "Output analog & digital", "Trimpot sensitivitas"]',
  '["Jemuran otomatis", "Notifikasi cuaca smart home", "Sistem irigasi cerdas"]', '["Arduino", "ESP32", "ESP8266", "Relay + motor penutup"]', '["1x Papan sensor hujan", "1x Modul komparator"]',
  14000, NULL, 130, 12, '54 x 40 x 6 mm (papan)',
  'assets/images/products/rain-sensor.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-RAIN');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-FLAME', 'Flame Sensor', 'flame-sensor', 'Generic',
  'Sensor inframerah untuk mendeteksi keberadaan api dalam jangkauan tertentu.', 'Flame Sensor mendeteksi cahaya inframerah pada panjang gelombang 760-1100nm yang dipancarkan oleh api, memungkinkan deteksi dini kebakaran. Umum dipakai bersamaan dengan sensor asap (MQ-2) untuk sistem alarm kebakaran yang lebih andal.', '{"Jangkauan Deteksi": "Hingga 100cm (tergantung intensitas api)", "Sudut Deteksi": "60 derajat", "Output": "Analog & Digital", "Tegangan": "3.3-5V"}', '["Deteksi api inframerah", "Output analog & digital", "Sudut deteksi lebar"]',
  '["Alarm kebakaran dini", "Robot pemadam api mini", "Sistem keamanan pabrik"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Sensor Flame"]',
  13000, NULL, 140, 8, '32 x 14 x 20 mm',
  'assets/images/products/flame-sensor.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-FLAME');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-SOUND', 'Sound Sensor', 'sound-sensor', 'Generic',
  'Modul sensor suara berbasis mikrofon kondensor untuk deteksi tepukan atau kebisingan.', 'Sound Sensor menggunakan mikrofon kondensor untuk mendeteksi gelombang suara di sekitarnya. Cocok untuk proyek kendali berbasis suara sederhana seperti lampu yang menyala dengan tepuk tangan atau monitoring level kebisingan ruangan.', '{"Komponen": "Mikrofon kondensor + LM393 comparator", "Output": "Analog & Digital", "Tegangan": "3.3-5V"}', '["Deteksi suara/tepukan", "Output analog & digital", "Sensitivitas adjustable"]',
  '["Lampu tepuk tangan (clap switch)", "Monitoring kebisingan", "Proyek interaktif suara"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Modul Sound Sensor"]',
  12000, NULL, 160, 8, '32 x 16 x 12 mm',
  'assets/images/products/sound-sensor.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-SOUND');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-IROBS', 'IR Obstacle Sensor', 'ir-obstacle-sensor', 'Generic',
  'Sensor inframerah untuk deteksi rintangan pada jarak dekat, umum dipakai di robot line follower.', 'IR Obstacle Sensor memancarkan sinar inframerah dan mendeteksi pantulannya untuk mengetahui keberadaan objek di depannya. Sensor ini menjadi komponen dasar pada robot line follower dan sistem penghindar rintangan jarak dekat.', '{"Jangkauan": "2-30cm (adjustable)", "Output": "Digital TTL", "Tegangan": "3.3-5V"}', '["Deteksi rintangan/garis", "Output digital cepat", "Sensitivitas adjustable", "Indikator LED bawaan"]',
  '["Robot line follower", "Robot penghindar rintangan", "Penghitung objek sederhana"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Sensor IR Obstacle"]',
  11000, NULL, 200, 8, '32 x 14 x 20 mm',
  'assets/images/products/ir-obstacle-sensor.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-IROBS');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'sensor'),
  'SNS-DS18B20', 'DS18B20', 'ds18b20', 'Maxim Integrated',
  'Sensor suhu digital presisi tinggi dengan probe tahan air, mendukung protokol 1-Wire.', 'DS18B20 adalah sensor suhu digital yang menggunakan protokol 1-Wire, memungkinkan banyak sensor terhubung ke satu pin GPIO saja. Tersedia dalam versi probe tahan air (waterproof) sehingga ideal untuk mengukur suhu cairan seperti akuarium atau tangki air.', '{"Rentang Suhu": "-55 hingga 125°C (±0.5°C)", "Interface": "1-Wire digital", "Tegangan": "3-5.5V", "Resolusi": "9-12 bit configurable"}', '["Protokol 1-Wire (multi-sensor 1 pin)", "Probe waterproof", "Akurasi tinggi", "Resolusi dapat diatur"]',
  '["Monitoring suhu akuarium", "Sistem tangki air panas", "Monitoring suhu multi-titik industri"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Sensor DS18B20 waterproof", "Resistor pull-up 4.7K"]',
  22000, NULL, 170, 6, '5 x 43 mm (probe waterproof)',
  'assets/images/products/ds18b20.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'SNS-DS18B20');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-HC05', 'HC-05 Bluetooth', 'hc-05-bluetooth', 'Generic',
  'Modul Bluetooth serial dua arah (master/slave) untuk komunikasi jarak dekat.', 'HC-05 adalah modul Bluetooth klasik yang dapat dikonfigurasi sebagai master maupun slave, memungkinkan komunikasi serial dua arah antara mikrokontroler dan perangkat lain seperti smartphone. Populer untuk kontrol robot via aplikasi Android atau transfer data sensor jarak dekat.', '{"Protokol": "Bluetooth 2.0 + EDR", "Jangkauan": "Hingga 10 meter", "Mode": "Master & Slave (configurable)", "Interface": "UART TTL", "Tegangan": "3.6-6V"}', '["Master & Slave mode", "AT Command configurable", "Jangkauan 10 meter", "Interface UART sederhana"]',
  '["Kontrol robot via Bluetooth", "Transfer data sensor ke HP", "Remote control jarak dekat"]', '["Arduino", "ESP32 (via UART)", "Aplikasi Android Bluetooth Serial"]', '["1x Modul HC-05", "Header pin"]',
  32000, NULL, 130, 8, '43 x 16 x 7 mm',
  'assets/images/products/hc-05-bluetooth.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-HC05');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-HC06', 'HC-06 Bluetooth', 'hc-06-bluetooth', 'Generic',
  'Modul Bluetooth serial mode slave-only, lebih sederhana dari HC-05.', 'HC-06 berfungsi serupa dengan HC-05 namun hanya bekerja sebagai slave, tanpa mode master. Cocok untuk proyek yang hanya perlu menerima perintah dari smartphone tanpa perlu mengendalikan modul Bluetooth lain, dengan proses setup yang lebih sederhana.', '{"Protokol": "Bluetooth 2.0", "Jangkauan": "Hingga 10 meter", "Mode": "Slave only", "Interface": "UART TTL", "Tegangan": "3.6-6V"}', '["Slave mode", "Setup lebih sederhana dari HC-05", "Jangkauan 10 meter"]',
  '["Kontrol perangkat via aplikasi Android", "Monitoring sensor sederhana via HP"]', '["Arduino", "ESP32 (via UART)"]', '["1x Modul HC-06", "Header pin"]',
  28000, NULL, 120, 8, '28 x 15 x 2.5 mm',
  'assets/images/products/hc-06-bluetooth.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-HC06');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-SIM800L', 'SIM800L', 'sim800l', 'SIMCom',
  'Modul GSM/GPRS quad-band untuk mengirim SMS, telepon, dan koneksi internet 2G.', 'SIM800L memungkinkan mikrokontroler terhubung ke jaringan seluler untuk mengirim SMS, melakukan panggilan, maupun koneksi data GPRS. Sangat berguna untuk proyek IoT di lokasi tanpa jangkauan WiFi, seperti sistem monitoring lahan pertanian di daerah terpencil.', '{"Jaringan": "Quad-band GSM 850/900/1800/1900MHz", "Data": "GPRS Class 12", "Interface": "UART TTL", "Tegangan": "3.7-4.2V (butuh regulator arus tinggi)"}', '["Kirim/terima SMS", "Panggilan suara", "Koneksi GPRS", "Quad-band global"]',
  '["Notifikasi SMS jarak jauh", "Monitoring lahan pertanian terpencil", "Sistem alarm via SMS"]', '["Arduino (via UART + regulator arus)", "ESP32", "Kartu SIM 2G aktif"]', '["1x Modul SIM800L", "Antena eksternal"]',
  55000, 48000, 90, 6, '25 x 23 x 3 mm',
  'assets/images/products/sim800l.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-SIM800L');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-SIM900', 'SIM900', 'sim900', 'SIMCom',
  'Shield GSM/GPRS lengkap dengan slot SIM card dan konektor antena.', 'SIM900 hadir dalam bentuk shield yang lebih mudah dipasang langsung di atas Arduino, dilengkapi regulator daya bawaan sehingga lebih stabil dibanding modul SIM800L bare. Cocok untuk proyek yang membutuhkan koneksi seluler dengan instalasi lebih praktis.', '{"Jaringan": "Quad-band GSM 850/900/1800/1900MHz", "Bentuk": "Shield Arduino", "Interface": "UART", "Power": "Regulator bawaan, input via adaptor eksternal"}', '["Bentuk shield siap pasang", "Regulator daya bawaan", "Slot SIM card", "Konektor antena eksternal"]',
  '["Sistem alarm via SMS/telepon", "Data logger GPRS", "IoT tanpa WiFi"]', '["Arduino Uno/Mega (stackable shield)", "Kartu SIM 2G aktif"]', '["1x Shield SIM900", "Antena eksternal"]',
  145000, NULL, 40, 25, '24 x 24 mm (shield)',
  'assets/images/products/sim900.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-SIM900');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-NEO6M', 'NEO-6M GPS', 'neo-6m-gps', 'u-blox',
  'Modul GPS receiver untuk mendapatkan koordinat lokasi secara real-time.', 'NEO-6M GPS Module menerima sinyal satelit GPS untuk menentukan posisi (latitude, longitude), kecepatan, dan waktu secara akurat. Modul ini banyak digunakan pada proyek pelacak kendaraan (vehicle tracker) dan sistem navigasi drone.', '{"Chip": "u-blox NEO-6M", "Akurasi Posisi": "2.5m CEP", "Update Rate": "5Hz (default 1Hz)", "Interface": "UART TTL (protokol NMEA)", "Tegangan": "3.3-5V"}', '["Akurasi posisi tinggi", "Antena keramik bawaan", "Protokol NMEA standar", "LED indikator fix"]',
  '["Vehicle/pet tracker", "Navigasi drone", "Data logger perjalanan"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Modul NEO-6M", "Antena GPS eksternal"]',
  65000, NULL, 100, 15, '25 x 35 mm',
  'assets/images/products/neo-6m-gps.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-NEO6M');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-LORA-SX1278', 'LoRa SX1278', 'lora-sx1278', 'Semtech',
  'Modul komunikasi radio jarak jauh (LoRa) frekuensi 433MHz, hemat daya.', 'LoRa SX1278 memungkinkan komunikasi data jarak jauh (hingga beberapa kilometer di area terbuka) dengan konsumsi daya sangat rendah, cocok untuk jaringan sensor IoT di area luas seperti perkebunan atau pemantauan lingkungan tanpa infrastruktur WiFi.', '{"Frekuensi": "433MHz", "Jangkauan": "Hingga beberapa km (line of sight)", "Interface": "SPI", "Tegangan": "3.3V", "Konsumsi Daya TX": "Hingga 100mA (peak)"}', '["Jangkauan sangat jauh", "Konsumsi daya rendah", "Interface SPI", "Tahan interferensi"]',
  '["Jaringan sensor perkebunan luas", "Pemantauan lingkungan remote", "Telemetri jarak jauh"]', '["Arduino (via SPI)", "ESP32", "Library LoRa (Sandeep Mistry)"]', '["1x Modul LoRa SX1278", "Antena spring 433MHz"]',
  85000, NULL, 70, 10, '17 x 32 mm',
  'assets/images/products/lora-sx1278.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-LORA-SX1278');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-NRF24L01', 'NRF24L01', 'nrf24l01', 'Nordic Semiconductor',
  'Modul RF 2.4GHz untuk komunikasi wireless antar mikrokontroler jarak dekat-menengah.', 'NRF24L01 memungkinkan dua atau lebih mikrokontroler saling berkomunikasi secara nirkabel pada frekuensi 2.4GHz tanpa perlu router WiFi. Sangat cocok untuk proyek remote control, jaringan sensor mesh sederhana, atau komunikasi antar-robot.', '{"Frekuensi": "2.4GHz ISM band", "Jangkauan": "Hingga 100m (line of sight, modul PA+LNA)", "Interface": "SPI", "Data Rate": "250kbps - 2Mbps", "Tegangan": "1.9-3.6V"}', '["Komunikasi 2.4GHz tanpa WiFi", "Multi-channel", "Data rate tinggi", "Konsumsi daya rendah"]',
  '["Remote control RC", "Sensor mesh network", "Komunikasi antar-robot"]', '["Arduino (via SPI)", "ESP32", "Library RF24"]', '["1x Modul NRF24L01"]',
  22000, NULL, 160, 2, '15 x 29 mm',
  'assets/images/products/nrf24l01.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-NRF24L01');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-ENC28J60', 'ENC28J60 Ethernet', 'enc28j60-ethernet', 'Microchip',
  'Modul Ethernet untuk menghubungkan mikrokontroler ke jaringan LAN kabel.', 'ENC28J60 memberikan konektivitas jaringan kabel (Ethernet) pada mikrokontroler yang tidak memiliki WiFi bawaan seperti Arduino Uno. Koneksi kabel lebih stabil dan tidak terpengaruh interferensi WiFi, cocok untuk instalasi industri.', '{"Standar": "IEEE 802.3 10BASE-T", "Interface": "SPI", "Tegangan": "3.3V", "Konektor": "RJ45 bawaan"}', '["Koneksi Ethernet stabil", "Interface SPI", "Konektor RJ45 built-in", "Tidak terpengaruh interferensi WiFi"]',
  '["IoT industri via LAN", "Web server Arduino via kabel", "Sistem monitoring pabrik"]', '["Arduino (via SPI + library UIPEthernet/EtherCard)"]', '["1x Modul ENC28J60"]',
  48000, NULL, 65, 12, '35 x 32 mm',
  'assets/images/products/enc28j60-ethernet.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-ENC28J60');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-4GLTE', '4G LTE IoT Module', '4g-lte-iot-module', 'Quectel',
  'Modul komunikasi seluler 4G LTE untuk IoT dengan kecepatan data tinggi.', 'Modul 4G LTE IoT ini memberikan konektivitas internet berkecepatan tinggi melalui jaringan seluler 4G, cocok untuk aplikasi IoT yang membutuhkan transfer data besar dan latency rendah seperti kamera pemantau jarak jauh atau sistem pelacakan armada.', '{"Jaringan": "4G LTE Cat 1 / fallback 3G/2G", "Interface": "UART, USB", "Tegangan": "3.3-4.4V", "Data Rate": "Downlink hingga 10Mbps"}', '["4G LTE cepat", "Fallback ke 3G/2G", "Cocok data besar", "Mendukung GPS (varian tertentu)"]',
  '["Kamera pemantau jarak jauh", "Pelacakan armada kendaraan", "IoT industri dengan data besar"]', '["Arduino (via UART)", "ESP32", "Kartu SIM 4G aktif"]', '["1x Modul 4G LTE", "Antena eksternal", "Kabel USB (tipe tertentu)"]',
  285000, 259000, 30, 20, '30 x 30 mm',
  'assets/images/products/4g-lte-iot-module.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-4GLTE');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'modul-komunikasi'),
  'COM-RC522', 'RFID RC522', 'rfid-rc522', 'NXP',
  'Modul RFID reader/writer 13.56MHz lengkap dengan kartu dan tag.', 'RFID RC522 dapat membaca dan menulis data pada kartu/tag RFID pasif frekuensi 13.56MHz. Umum digunakan untuk sistem akses pintu otomatis, absensi karyawan, maupun sistem pembayaran sederhana berbasis kartu.', '{"Frekuensi": "13.56MHz (ISO14443A)", "Jangkauan Baca": "0-6cm", "Interface": "SPI", "Tegangan": "3.3V"}', '["Baca & tulis kartu RFID", "Jangkauan hingga 6cm", "Interface SPI", "Termasuk kartu & keyfob"]',
  '["Akses pintu otomatis", "Sistem absensi karyawan", "Pembayaran kartu sederhana"]', '["Arduino (via SPI)", "ESP32", "Library MFRC522"]', '["1x Modul RC522", "1x Kartu RFID", "1x Keyfob RFID"]',
  25000, NULL, 150, 10, '40 x 60 mm (kit)',
  'assets/images/products/rfid-rc522.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'COM-RC522');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'relay-automation'),
  'REL-1CH', '1 Channel Relay', '1-channel-relay', 'Songle',
  'Modul relay 1 channel untuk mengendalikan 1 perangkat listrik AC/DC dari mikrokontroler.', 'Modul relay 1 channel memungkinkan mikrokontroler bertegangan rendah (5V) untuk mengendalikan perangkat listrik bertegangan tinggi seperti lampu AC 220V atau pompa air, dengan isolasi optocoupler untuk keamanan sirkuit kontrol.', '{"Jumlah Channel": "1", "Kapasitas Kontak": "10A 250VAC / 10A 30VDC", "Trigger": "Low level (aktif LOW)", "Isolasi": "Optocoupler", "Tegangan Kontrol": "5V"}', '["1 channel", "Optocoupler isolation", "LED indikator status", "Kapasitas 10A"]',
  '["Kontrol lampu rumah", "Kontrol pompa air", "Automation perangkat tunggal"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Modul relay 1 channel"]',
  12000, NULL, 200, 25, '43.5 x 17 x 17 mm',
  'assets/images/products/1-channel-relay.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'REL-1CH');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'relay-automation'),
  'REL-2CH', '2 Channel Relay', '2-channel-relay', 'Songle',
  'Modul relay 2 channel untuk mengendalikan 2 perangkat listrik secara independen.', 'Modul relay 2 channel memberikan kemampuan untuk mengontrol dua perangkat listrik terpisah secara independen dari satu board mikrokontroler, ideal untuk proyek automation seperti kontrol lampu dan kipas sekaligus.', '{"Jumlah Channel": "2", "Kapasitas Kontak": "10A 250VAC / 10A 30VDC", "Trigger": "Low level (aktif LOW)", "Isolasi": "Optocoupler", "Tegangan Kontrol": "5V"}', '["2 channel independen", "Optocoupler isolation", "LED indikator per channel"]',
  '["Kontrol lampu & kipas", "Automation ruangan sederhana", "Sistem irigasi 2 zona"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Modul relay 2 channel"]',
  20000, NULL, 170, 40, '51 x 38 x 18 mm',
  'assets/images/products/2-channel-relay.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'REL-2CH');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'relay-automation'),
  'REL-4CH', '4 Channel Relay', '4-channel-relay', 'Songle',
  'Modul relay 4 channel untuk automation dengan lebih banyak titik kontrol.', 'Modul relay 4 channel memungkinkan pengendalian empat perangkat listrik berbeda secara terpisah, cocok untuk sistem smart home skala kecil yang mengontrol beberapa titik lampu dan perangkat elektronik sekaligus dari satu board.', '{"Jumlah Channel": "4", "Kapasitas Kontak": "10A 250VAC / 10A 30VDC", "Trigger": "Low level (aktif LOW)", "Isolasi": "Optocoupler", "Tegangan Kontrol": "5V"}', '["4 channel independen", "Optocoupler isolation", "Konektor screw terminal", "LED indikator per channel"]',
  '["Smart home 4 titik kontrol", "Automation panel listrik kecil", "Kontrol multi-perangkat"]', '["Arduino Mega/Uno", "ESP32", "Raspberry Pi"]', '["1x Modul relay 4 channel"]',
  35000, NULL, 130, 70, '72 x 55 x 19 mm',
  'assets/images/products/4-channel-relay.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'REL-4CH');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'relay-automation'),
  'REL-8CH', '8 Channel Relay', '8-channel-relay', 'Songle',
  'Modul relay 8 channel untuk sistem automation skala menengah dengan banyak titik kontrol.', 'Modul relay 8 channel dirancang untuk proyek automation yang membutuhkan kontrol banyak perangkat sekaligus, seperti sistem smart home berskala rumah penuh atau panel kontrol lampu pertunjukan sederhana.', '{"Jumlah Channel": "8", "Kapasitas Kontak": "10A 250VAC / 10A 30VDC", "Trigger": "Low level (aktif LOW)", "Isolasi": "Optocoupler", "Tegangan Kontrol": "5V"}', '["8 channel independen", "Optocoupler isolation", "Konektor screw terminal", "Cocok panel kontrol besar"]',
  '["Smart home skala rumah penuh", "Panel kontrol lampu pertunjukan", "Automation gedung kecil"]', '["Arduino Mega", "ESP32", "Raspberry Pi"]', '["1x Modul relay 8 channel"]',
  65000, 58000, 90, 130, '138 x 55 x 19 mm',
  'assets/images/products/8-channel-relay.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'REL-8CH');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'relay-automation'),
  'REL-SSR', 'Solid State Relay', 'solid-state-relay', 'Fotek',
  'Relay solid-state tanpa kontak mekanik, cocok untuk switching frekuensi tinggi dan beban induktif.', 'Solid State Relay (SSR) menggunakan komponen semikonduktor untuk switching, tanpa bagian mekanik yang bergerak seperti relay elektromekanik biasa. Ini membuatnya lebih tahan lama, senyap, dan cocok untuk switching berfrekuensi tinggi seperti kontrol elemen pemanas.', '{"Kapasitas Kontak": "25A-40A 480VAC (tergantung varian)", "Trigger": "3-32VDC", "Isolasi": "Optical", "Umur Switching": "Jauh lebih tahan lama dari relay mekanik"}', '["Tanpa kontak mekanik", "Switching senyap", "Tahan lama", "Cocok beban induktif"]',
  '["Kontrol elemen pemanas", "Kontrol motor industri", "Automation frekuensi switching tinggi"]', '["Arduino", "ESP32", "PLC"]', '["1x Modul Solid State Relay", "Heatsink (tipe tertentu)"]',
  45000, NULL, 100, 110, '62 x 45 x 33 mm',
  'assets/images/products/solid-state-relay.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'REL-SSR');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'motor-aktuator'),
  'MOT-L298N', 'L298N Motor Driver', 'l298n-motor-driver', 'STMicroelectronics',
  'Modul driver motor DC dual H-bridge untuk mengendalikan 2 motor DC atau 1 motor stepper.', 'L298N Motor Driver memungkinkan mikrokontroler mengendalikan motor DC yang membutuhkan arus lebih besar dari yang bisa disediakan langsung oleh pin GPIO. Modul ini mendukung kontrol arah dan kecepatan (PWM) untuk dua motor DC sekaligus.', '{"IC": "L298N Dual H-Bridge", "Tegangan Motor": "5-35V", "Arus Maks": "2A per channel", "Kontrol": "Arah + PWM speed", "Kanal": "2 motor DC / 1 stepper"}', '["Dual H-bridge", "Kontrol arah & kecepatan", "Heatsink bawaan", "Mendukung motor stepper"]',
  '["Robot beroda", "Kontrol motor konveyor mini", "Proyek automation motor DC"]', '["Arduino", "ESP32", "Raspberry Pi"]', '["1x Modul L298N"]',
  28000, NULL, 140, 55, '43 x 43 x 27 mm',
  'assets/images/products/l298n-motor-driver.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MOT-L298N');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'motor-aktuator'),
  'MOT-TB6612', 'TB6612FNG Motor Driver', 'tb6612fng-motor-driver', 'Toshiba',
  'Driver motor DC dual H-bridge yang lebih efisien dan kompak dibanding L298N.', 'TB6612FNG menawarkan efisiensi yang lebih baik dibanding L298N dengan disipasi panas lebih rendah dan ukuran yang jauh lebih kompak, cocok untuk proyek robotika mobile yang mengutamakan efisiensi baterai.', '{"Tegangan Motor": "2.5-13.5V", "Arus Maks": "1.2A per channel (peak 3.2A)", "Kontrol": "Arah + PWM speed", "Kanal": "2 motor DC"}', '["Lebih efisien dari L298N", "Ukuran kompak", "Panas rendah", "Cocok robot bertenaga baterai"]',
  '["Robot mobile efisien baterai", "Mini rover", "Proyek robotika edukasi"]', '["Arduino", "ESP32", "Raspberry Pi"]', '["1x Modul TB6612FNG"]',
  32000, NULL, 110, 8, '20 x 22 x 5 mm',
  'assets/images/products/tb6612fng-motor-driver.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MOT-TB6612');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'motor-aktuator'),
  'MOT-SG90', 'Servo SG90', 'servo-sg90', 'TowerPro',
  'Motor servo mikro 9g untuk kontrol posisi sudut presisi hingga 180 derajat.', 'Servo SG90 adalah motor servo mikro ringan yang mampu berputar hingga sudut 180 derajat dengan presisi tinggi, dikendalikan melalui sinyal PWM. Sangat populer untuk proyek robotika ringan seperti lengan robot mini dan mekanisme buka-tutup otomatis.', '{"Torsi": "1.8 kg/cm", "Sudut Putar": "0-180 derajat", "Tegangan": "4.8-6V", "Kontrol": "PWM 50Hz"}', '["Ringan (9g)", "Presisi sudut tinggi", "Kontrol PWM sederhana", "Harga terjangkau"]',
  '["Lengan robot mini", "Mekanisme buka-tutup otomatis", "Kamera pan-tilt mini"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Servo SG90", "Horn servo & sekrup"]',
  22000, NULL, 210, 9, '23 x 12.2 x 29 mm',
  'assets/images/products/servo-sg90.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MOT-SG90');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'motor-aktuator'),
  'MOT-MG996R', 'Servo MG996R', 'servo-mg996r', 'TowerPro',
  'Motor servo torsi tinggi dengan gear metal untuk aplikasi yang membutuhkan tenaga lebih besar.', 'Servo MG996R menggunakan gear metal yang lebih kuat dibanding SG90, mampu menghasilkan torsi jauh lebih besar. Cocok untuk proyek robotika yang membutuhkan tenaga lebih besar seperti lengan robot beban berat atau kaki robot hexapod.', '{"Torsi": "9.4-11 kg/cm", "Sudut Putar": "0-180 derajat", "Tegangan": "4.8-7.2V", "Gear": "Metal gear"}', '["Torsi tinggi", "Gear metal tahan lama", "Cocok beban berat", "Ball bearing bawaan"]',
  '["Lengan robot beban berat", "Robot hexapod", "RC car/boat kontrol kemudi"]', '["Arduino", "ESP32", "Raspberry Pi"]', '["1x Servo MG996R", "Horn servo & sekrup"]',
  55000, 48000, 100, 55, '40.7 x 19.7 x 42.9 mm',
  'assets/images/products/servo-mg996r.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MOT-MG996R');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'motor-aktuator'),
  'MOT-28BYJ48', 'Stepper Motor 28BYJ-48', 'stepper-motor-28byj-48', 'Generic',
  'Motor stepper unipolar dengan driver ULN2003, cocok untuk gerakan presisi bertahap.', 'Stepper Motor 28BYJ-48 bergerak dalam langkah-langkah kecil yang presisi, memungkinkan kontrol posisi yang akurat tanpa perlu sensor umpan balik (feedback). Paket ini sudah termasuk driver board ULN2003 sehingga siap dipakai langsung dengan mikrokontroler.', '{"Tegangan": "5V DC", "Sudut per Step": "5.625° (1/64 gear reduction)", "Driver": "ULN2003 (disertakan)", "Torsi Holding": "Rendah-menengah"}', '["Gerakan presisi bertahap", "Driver ULN2003 disertakan", "Tanpa perlu sensor feedback"]',
  '["Kontrol posisi presisi (kamera slider mini)", "Mekanisme pembuka gorden otomatis", "Proyek edukasi stepper"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Motor stepper 28BYJ-48", "1x Driver board ULN2003"]',
  35000, NULL, 95, 65, '35 x 35 x 19 mm',
  'assets/images/products/stepper-motor-28byj-48.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'MOT-28BYJ48');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'display'),
  'DSP-OLED096', 'OLED 0.96" I2C', 'oled-0-96-i2c', 'Generic',
  'Layar OLED monokrom 128x64 piksel dengan interface I2C hemat pin.', 'OLED 0.96 inci menampilkan teks dan grafik dengan kontras tinggi dan sudut pandang lebar karena setiap piksel memancarkan cahayanya sendiri (self-emissive), tanpa perlu backlight. Interface I2C membuatnya hanya membutuhkan 2 pin data untuk kontrol.', '{"Resolusi": "128x64 piksel", "Ukuran Layar": "0.96 inci", "Interface": "I2C", "Driver": "SSD1306", "Tegangan": "3.3-5V"}', '["Kontras tinggi", "Hemat daya", "Interface I2C (2 pin)", "Sudut pandang lebar"]',
  '["Dashboard data sensor mini", "Menu interaktif proyek IoT", "Indikator status perangkat"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Modul OLED 0.96\\""]',
  32000, NULL, 160, 8, '27.3 x 27.8 mm',
  'assets/images/products/oled-0-96-i2c.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'DSP-OLED096');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'display'),
  'DSP-OLED13', 'OLED 1.3" I2C', 'oled-1-3-i2c', 'Generic',
  'Layar OLED monokrom 128x64 piksel dengan ukuran lebih besar dari varian 0.96 inci.', 'OLED 1.3 inci menawarkan area tampilan yang lebih luas dibanding varian 0.96 inci dengan resolusi yang sama, sehingga teks dan grafik tampak lebih besar dan mudah dibaca dari jarak yang lebih jauh.', '{"Resolusi": "128x64 piksel", "Ukuran Layar": "1.3 inci", "Interface": "I2C", "Driver": "SH1106", "Tegangan": "3.3-5V"}', '["Layar lebih besar & jelas", "Kontras tinggi", "Interface I2C", "Hemat daya"]',
  '["Dashboard monitoring lebih detail", "Display menu proyek IoT", "Panel indikator jarak baca lebih jauh"]', '["Arduino", "ESP32", "ESP8266", "Raspberry Pi"]', '["1x Modul OLED 1.3\\""]',
  48000, NULL, 120, 10, '35.5 x 31.4 mm',
  'assets/images/products/oled-1-3-i2c.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'DSP-OLED13');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'display'),
  'DSP-LCD1602', 'LCD 16x2 I2C', 'lcd-16x2-i2c', 'Generic',
  'Layar LCD karakter 16x2 dengan modul I2C backpack untuk menghemat penggunaan pin.', 'LCD 16x2 I2C mampu menampilkan 2 baris teks dengan 16 karakter per baris, dilengkapi modul backpack I2C sehingga hanya membutuhkan 2 pin data (dibanding 6+ pin pada LCD parallel biasa). Cocok untuk menampilkan status sensor atau menu sederhana.', '{"Ukuran": "16 karakter x 2 baris", "Interface": "I2C (via PCF8574 backpack)", "Backlight": "Biru/Hijau (adjustable)", "Tegangan": "5V"}', '["Hemat pin (I2C)", "Backlight adjustable", "Kontras dapat diatur trimpot", "Mudah dibaca"]',
  '["Display status sensor", "Menu navigasi proyek embedded", "Panel info mesin sederhana"]', '["Arduino", "ESP32", "ESP8266"]', '["1x LCD 16x2 dengan I2C backpack"]',
  38000, NULL, 150, 55, '80 x 36 x 12 mm',
  'assets/images/products/lcd-16x2-i2c.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'DSP-LCD1602');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'display'),
  'DSP-LCD2004', 'LCD 20x4 I2C', 'lcd-20x4-i2c', 'Generic',
  'Layar LCD karakter 20x4 dengan modul I2C backpack, menampilkan lebih banyak informasi sekaligus.', 'LCD 20x4 I2C menampilkan 4 baris teks dengan 20 karakter per baris, memberikan ruang tampilan lebih luas dibanding LCD 16x2 untuk proyek yang perlu menampilkan beberapa data sensor sekaligus dalam satu layar.', '{"Ukuran": "20 karakter x 4 baris", "Interface": "I2C (via PCF8574 backpack)", "Backlight": "Biru/Hijau (adjustable)", "Tegangan": "5V"}', '["Kapasitas tampilan lebih besar", "Hemat pin (I2C)", "Backlight adjustable"]',
  '["Dashboard multi-sensor", "Panel kontrol mesin", "Sistem informasi antrian sederhana"]', '["Arduino", "ESP32", "ESP8266"]', '["1x LCD 20x4 dengan I2C backpack"]',
  62000, NULL, 100, 75, '98 x 60 x 12 mm',
  'assets/images/products/lcd-20x4-i2c.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'DSP-LCD2004');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'display'),
  'DSP-7SEG', '7 Segment Display', '7-segment-display', 'Generic',
  'Modul display 7-segmen 4 digit untuk menampilkan angka seperti jam atau counter.', '7 Segment Display 4 digit menampilkan data numerik dengan jelas dari kejauhan, umum digunakan pada proyek jam digital, timer countdown, atau penghitung (counter) sederhana yang tidak memerlukan tampilan huruf/grafik kompleks.', '{"Jumlah Digit": "4 digit", "Driver": "TM1637", "Interface": "2-wire (CLK, DIO)", "Tegangan": "3.3-5V"}', '["4 digit angka", "Interface hanya 2 pin", "Kecerahan dapat diatur", "Mudah dibaca dari jarak jauh"]',
  '["Jam digital DIY", "Timer countdown", "Penghitung pengunjung sederhana"]', '["Arduino", "ESP32", "ESP8266"]', '["1x Modul 7 Segment 4 digit TM1637"]',
  15000, NULL, 180, 15, '38 x 20 x 8 mm (per modul)',
  'assets/images/products/7-segment-display.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'DSP-7SEG');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'power-module'),
  'PWR-TP4056', 'TP4056 Charging Module', 'tp4056-charging-module', 'Generic',
  'Modul charger baterai lithium 18650 dengan proteksi over-charge dan konektor USB Micro/Type-C.', 'TP4056 adalah modul pengisi daya baterai lithium-ion/polymer 3.7V yang dilengkapi sirkuit proteksi dasar (over-charge, over-discharge). Modul ini menjadi komponen wajib pada hampir semua proyek IoT bertenaga baterai portable.', '{"Baterai": "Li-ion/LiPo 3.7V", "Arus Charging": "1A (default, adjustable via resistor)", "Input": "Micro USB / USB-C (tergantung varian)", "Proteksi": "Over-charge, Over-discharge"}', '["Charging otomatis", "Proteksi baterai bawaan", "LED indikator status charging", "Ukuran mungil"]',
  '["Powerbank DIY", "Proyek IoT baterai portable", "Wearable device"]', '["Baterai 18650/LiPo", "Semua board mikrokontroler bertenaga baterai"]', '["1x Modul TP4056"]',
  8000, NULL, 300, 3, '26 x 17 x 4 mm',
  'assets/images/products/tp4056-charging-module.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PWR-TP4056');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'power-module'),
  'PWR-LM2596', 'LM2596 Buck Converter', 'lm2596-buck-converter', 'Texas Instruments',
  'Modul step-down (buck) converter untuk menurunkan tegangan DC secara efisien.', 'LM2596 Buck Converter menurunkan tegangan DC input yang lebih tinggi menjadi tegangan output yang lebih rendah dan stabil, dengan efisiensi tinggi dibanding regulator linear biasa. Umum dipakai untuk menurunkan tegangan baterai 12V ke 5V bagi mikrokontroler.', '{"Tegangan Input": "3-40V DC", "Tegangan Output": "1.5-35V DC (adjustable)", "Arus Maks": "3A", "Efisiensi": "Hingga 92%"}', '["Adjustable output (trimpot)", "Efisiensi tinggi", "Arus hingga 3A", "Heatsink bawaan"]',
  '["Menurunkan tegangan baterai 12V ke 5V", "Power supply proyek automation", "Charging circuit custom"]', '["Semua board mikrokontroler", "Sistem panel surya kecil"]', '["1x Modul LM2596"]',
  12000, NULL, 220, 12, '43 x 21 x 14 mm',
  'assets/images/products/lm2596-buck-converter.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PWR-LM2596');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'power-module'),
  'PWR-MT3608', 'MT3608 Boost Converter', 'mt3608-boost-converter', 'Generic',
  'Modul step-up (boost) converter untuk menaikkan tegangan DC yang lebih rendah.', 'MT3608 Boost Converter menaikkan tegangan DC input yang rendah (misalnya dari baterai 3.7V) menjadi tegangan output yang lebih tinggi dan stabil, berguna untuk menyalakan komponen yang membutuhkan tegangan lebih tinggi dari sumber baterai tunggal.', '{"Tegangan Input": "2-24V DC", "Tegangan Output": "5-28V DC (adjustable)", "Arus Maks": "2A (input tergantung beban)", "Efisiensi": "Hingga 93%"}', '["Adjustable output (trimpot)", "Efisiensi tinggi", "Ukuran kompak"]',
  '["Menaikkan tegangan baterai 3.7V ke 5V/9V", "Power supply modul yang butuh tegangan lebih tinggi"]', '["Baterai LiPo/18650", "Proyek portable IoT"]', '["1x Modul MT3608"]',
  10000, NULL, 200, 5, '22 x 17 x 4 mm',
  'assets/images/products/mt3608-boost-converter.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PWR-MT3608');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'power-module'),
  'PWR-5VPSM', '5V Power Supply Module', '5v-power-supply-module', 'Generic',
  'Modul catu daya AC ke DC 5V, mengubah listrik rumah langsung menjadi tegangan mikrokontroler.', '5V Power Supply Module mengonversi tegangan AC 220V dari listrik rumah langsung menjadi DC 5V yang stabil, cocok untuk proyek IoT yang terpasang permanen di rumah dan membutuhkan sumber daya yang selalu menyala tanpa baterai.', '{"Input": "AC 100-240V", "Output": "DC 5V", "Arus Maks": "600mA-1A (tergantung varian)", "Isolasi": "Terisolasi galvanis"}', '["Konversi langsung dari listrik AC", "Terisolasi galvanis (aman)", "Ukuran kompak"]',
  '["Smart home device permanen", "Stasiun IoT tanpa baterai", "Panel kontrol dinding"]', '["Semua board mikrokontroler 5V"]', '["1x Modul Power Supply 5V"]',
  15000, NULL, 180, 20, '50 x 25 x 20 mm',
  'assets/images/products/5v-power-supply-module.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PWR-5VPSM');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'power-module'),
  'PWR-USBC5V', 'USB-C 5V Power Module', 'usb-c-5v-power-module', 'Generic',
  'Modul breakout USB-C untuk mendapatkan input daya 5V yang praktis dan modern.', 'USB-C 5V Power Module menyediakan breakout board dengan konektor USB-C, memudahkan pemberian daya ke proyek elektronik menggunakan kabel dan charger USB-C yang sudah umum digunakan saat ini, menggantikan konektor micro USB yang lebih lama.', '{"Konektor": "USB-C", "Output": "5V DC", "Arus Maks": "Hingga 2A (tergantung sumber daya)"}', '["Konektor USB-C modern", "Praktis & portable", "Kompatibel charger umum"]',
  '["Sumber daya proyek portable", "Breakout board custom project", "Pengganti jack DC lama"]', '["Semua board mikrokontroler 5V"]', '["1x Modul breakout USB-C"]',
  9000, NULL, 210, 6, '20 x 15 x 6 mm',
  'assets/images/products/usb-c-5v-power-module.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'PWR-USBC5V');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'iot-kit'),
  'KIT-ESP32-START', 'ESP32 IoT Starter Kit', 'esp32-iot-starter-kit', 'TechZone',
  'Paket lengkap untuk memulai belajar IoT berbasis ESP32, cocok untuk pemula.', 'ESP32 IoT Starter Kit dirancang khusus bagi pemula yang ingin memulai perjalanan belajar Internet of Things. Paket ini berisi board ESP32, beberapa sensor dasar, breadboard, dan kabel jumper sehingga Anda bisa langsung praktik tanpa perlu membeli komponen satu per satu.', '{"Isi Utama": "ESP32 DevKit V1 + sensor dasar", "Level": "Pemula", "Dokumentasi": "Panduan proyek dasar (PDF)"}', '["Cocok pemula", "Hemat dibanding beli satuan", "Dilengkapi panduan proyek", "Semua kabel & breadboard termasuk"]',
  '["Belajar dasar IoT", "Proyek sekolah/kuliah", "Eksperimen sensor dasar"]', '["Arduino IDE", "MicroPython"]', '["1x ESP32 DevKit V1", "1x Sensor DHT11", "1x Sensor LDR", "1x Breadboard 830 titik", "20x Kabel jumper male-male", "Panduan proyek dasar (PDF)"]',
  285000, 249000, 45, 650, 'Box 25 x 18 x 6 cm',
  'assets/images/products/esp32-iot-starter-kit.svg', 'active', 1, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'KIT-ESP32-START');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'iot-kit'),
  'KIT-SMARTHOME', 'Smart Home IoT Kit', 'smart-home-iot-kit', 'TechZone',
  'Paket komponen untuk membangun proyek smart home sederhana: kontrol lampu, sensor gerak, dan monitoring suhu.', 'Smart Home IoT Kit berisi kombinasi modul relay, sensor PIR, dan sensor suhu yang dirancang khusus untuk proyek smart home seperti kontrol lampu otomatis berdasarkan gerakan dan suhu ruangan, lengkap dengan panduan wiring dan contoh kode program.', '{"Isi Utama": "ESP32 + Relay + PIR + DHT22", "Level": "Menengah", "Dokumentasi": "Panduan wiring & contoh kode"}', '["Fokus proyek smart home", "Kombinasi modul teruji", "Panduan wiring lengkap", "Contoh kode program disertakan"]',
  '["Kontrol lampu otomatis", "Monitoring suhu ruangan", "Proyek smart home DIY"]', '["Arduino IDE", "ESP32"]', '["1x ESP32 DevKit V1", "1x Modul Relay 2 Channel", "1x Sensor PIR", "1x Sensor DHT22", "Kabel jumper", "Panduan wiring & kode program (PDF)"]',
  385000, 349000, 35, 850, 'Box 28 x 20 x 8 cm',
  'assets/images/products/smart-home-iot-kit.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'KIT-SMARTHOME');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'iot-kit'),
  'KIT-SENSORLEARN', 'Sensor Learning Kit', 'sensor-learning-kit', 'TechZone',
  'Kumpulan berbagai jenis sensor populer untuk eksplorasi dan pembelajaran mendalam tentang sensor elektronik.', 'Sensor Learning Kit ditujukan bagi yang ingin memahami berbagai jenis sensor secara mendalam, berisi kombinasi sensor ultrasonik, PIR, cahaya, suara, dan suhu sekaligus, cocok untuk tugas akhir maupun eksplorasi pribadi.', '{"Isi Utama": "5 jenis sensor populer + Arduino Uno", "Level": "Pemula-Menengah", "Dokumentasi": "Contoh kode tiap sensor (PDF)"}', '["5 jenis sensor sekaligus", "Cocok tugas akhir", "Contoh kode tiap sensor", "Hemat dibanding beli satuan"]',
  '["Eksplorasi jenis-jenis sensor", "Tugas akhir/skripsi elektronika", "Riset kecil sensor"]', '["Arduino IDE"]', '["1x Arduino Uno R3", "1x HC-SR04", "1x PIR Motion Sensor", "1x LDR Sensor", "1x Sound Sensor", "1x DHT11", "Kabel jumper & breadboard", "Panduan contoh kode (PDF)"]',
  245000, NULL, 50, 700, 'Box 25 x 18 x 6 cm',
  'assets/images/products/sensor-learning-kit.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'KIT-SENSORLEARN');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'iot-kit'),
  'KIT-AUTOMATION', 'IoT Automation Kit', 'iot-automation-kit', 'TechZone',
  'Paket lengkap untuk proyek automation: kontrol motor, relay, dan aktuator berbasis ESP32.', 'IoT Automation Kit difokuskan pada proyek kendali perangkat mekanis, berisi driver motor, beberapa servo, dan modul relay yang dapat dikombinasikan untuk membangun sistem automation seperti pintu geser otomatis atau lengan robot sederhana.', '{"Isi Utama": "ESP32 + L298N + Servo + Relay", "Level": "Menengah-Lanjut", "Dokumentasi": "Panduan proyek automation (PDF)"}', '["Fokus kontrol motor & aktuator", "Kombinasi driver & servo", "Panduan proyek automation"]',
  '["Pintu geser otomatis", "Lengan robot sederhana", "Sistem automation mekanis DIY"]', '["Arduino IDE", "ESP32"]', '["1x ESP32 DevKit V1", "1x Driver L298N", "2x Servo SG90", "1x Modul Relay 2 Channel", "Kabel jumper", "Panduan proyek automation (PDF)"]',
  425000, 389000, 30, 900, 'Box 30 x 22 x 9 cm',
  'assets/images/products/iot-automation-kit.svg', 'active', 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'KIT-AUTOMATION');

INSERT INTO products
  (category_id, sku, name, slug, brand, short_description, description, specifications, features,
   applications, compatibility, package_contents, price, discount_price, stock, weight, dimensions,
   main_image, status, featured, rating, review_count, created_at, updated_at)
SELECT
  (SELECT id FROM categories WHERE slug = 'iot-kit'),
  'KIT-ESP32-COMPLETE', 'ESP32 Complete Project Kit', 'esp32-complete-project-kit', 'TechZone',
  'Paket paling lengkap TechZone: gabungan sensor, aktuator, display, dan komunikasi untuk proyek IoT tingkat lanjut.', 'ESP32 Complete Project Kit adalah paket paling komprehensif yang menggabungkan sensor, display, aktuator, dan modul komunikasi sekaligus, dirancang untuk pengguna yang ingin membangun proyek IoT tingkat lanjut end-to-end tanpa perlu membeli komponen tambahan dari luar.', '{"Isi Utama": "ESP32 + OLED + berbagai sensor + relay + servo", "Level": "Lanjut", "Dokumentasi": "Panduan proyek lengkap end-to-end (PDF)"}', '["Paling lengkap", "Cocok proyek akhir kompleks", "Kombinasi sensor, display, aktuator", "Hemat signifikan dibanding satuan"]',
  '["Proyek IoT tingkat lanjut", "Purwarupa produk startup", "Tugas akhir kompleks"]', '["Arduino IDE", "ESP32", "MicroPython"]', '["1x ESP32 DevKit V1", "1x OLED 0.96\\"", "1x DHT22", "1x HC-SR04", "1x PIR Motion Sensor", "1x Modul Relay 2 Channel", "1x Servo SG90", "Breadboard & kabel jumper lengkap", "Panduan proyek end-to-end (PDF)"]',
  550000, 499000, 25, 1100, 'Box 32 x 24 x 10 cm',
  'assets/images/products/esp32-complete-project-kit.svg', 'active', 1, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE sku = 'KIT-ESP32-COMPLETE');
