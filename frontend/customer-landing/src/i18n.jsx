import { createContext, useContext, useEffect, useMemo, useState } from 'react';

const languageStorageKey = 'glowhub_customer_language';
const supportedLanguages = ['en', 'id'];
const localizableAttributes = ['aria-label', 'placeholder', 'title'];

const CustomerLanguageContext = createContext(null);

const indonesianToEnglish = {
    'Service tidak tersedia untuk tanggal sebelum hari ini. Pilih hari ini atau tanggal setelahnya.': 'Service is not available before today. Choose today or a later date.',
    'lokasi saat ini': 'current location',
    'Lokasi saat ini': 'Current location',
    'Mencari lokasi...': 'Finding location...',
    'Bayar di salon': 'Pay at salon',
    'Bayar di Salon': 'Pay at Salon',
    'Alamat salon belum tersedia': 'Salon address is not available',
    'Gunakan akun customer.': 'Use a customer account.',
    'Login gagal.': 'Login failed.',
    'Registrasi gagal.': 'Registration failed.',
    'Booking gagal dibuat.': 'Booking could not be created.',
    Lanjut: 'Next',
    Kembali: 'Back',
    'Memuat detail salon': 'Loading salon details',
    'Mengambil layanan, staff, dan jadwal branch terbaru.': 'Loading the latest services, staff, and branch schedule.',
    'Salon tidak ditemukan': 'Salon not found',
    'Branch ini belum tersedia atau sedang tidak aktif.': 'This branch is unavailable or inactive.',
    'Status Booking': 'Booking Status',
    'Mengambil status booking terbaru kamu.': 'Loading your latest booking status.',
    'Selesaikan pembayaran dulu untuk membuat booking baru.': 'Complete payment first to create a new booking.',
    'Memuat status booking...': 'Loading booking status...',
    'Belum ada booking baru': 'No new booking yet',
    'Sebentar, data booking sedang disiapkan.': 'One moment, your booking data is being prepared.',
    'Selesaikan review dan pembayaran dulu untuk melihat status booking.': 'Complete review and payment first to see the booking status.',
    'Mulai Booking': 'Start Booking',
    Antrian: 'Queue',
    'Booking terjadwal': 'Scheduled booking',
    'Datang ke salon sesuai jadwal, pembayaran dilakukan langsung di tempat.': 'Visit the salon on schedule and pay directly on site.',
    'Pembayaran sudah diterima. Datang ke salon sesuai jadwal booking.': 'Payment has been received. Visit the salon at your scheduled booking time.',
    'Pembayaran sedang dicatat. Simpan kode booking untuk pengecekan di salon.': 'Payment is being recorded. Keep your booking code for salon check-in.',
    'Booking Berhasil': 'Booking Successful',
    'Booking kamu sudah dibuat. Simpan kode ini untuk check-in di salon.': 'Your booking has been created. Save this code for salon check-in.',
    'Kode Booking': 'Booking Code',
    'Lihat My Bookings': 'View My Bookings',
    'Booking Lagi': 'Book Again',
    'Detail Kunjungan': 'Visit Details',
    Tanggal: 'Date',
    'Hari ini': 'Today',
    Besok: 'Tomorrow',
    Layanan: 'Services',
    'Layanan Dipilih': 'Selected Services',
    'Layanan booking': 'Booked service',
    'Pilih Mode Booking': 'Choose Booking Mode',
    'Gunakan jam pasti untuk reservasi terjadwal, atau join antrian untuk layanan cepat.': 'Use a fixed time for scheduled reservations, or join the queue for quick services.',
    'Booking Jam Pasti': 'Fixed-Time Booking',
    'Pilih tanggal dan slot yang tersedia.': 'Choose an available date and slot.',
    'Join Antrian': 'Join Queue',
    'Lihat estimasi tunggu lalu masuk queue.': 'Check the wait estimate, then enter the queue.',
    'Pilih Staff': 'Choose Staff',
    'Any Available Staff akan memilih staff tercepat yang punya semua skill layanan.': 'Any Available Staff will pick the fastest staff member with all required service skills.',
    'Mengecek staff eligible...': 'Checking eligible staff...',
    'Belum ada staff yang punya semua skill layanan ini.': 'No staff member has all skills for this service yet.',
    'Sistem pilih staff tercepat': 'The system picks the fastest staff member',
    'Pilih Jadwal': 'Choose Schedule',
    'Estimasi Antrian': 'Queue Estimate',
    'Slot dihitung dari jadwal staff dan booking aktif.': 'Slots are calculated from staff schedules and active bookings.',
    'Estimasi berdasarkan antrian aktif branch hari ini.': 'Estimate is based on today active branch queue.',
    'Menghitung slot...': 'Calculating slots...',
    'Belum ada slot tersedia untuk pilihan ini.': 'No slots are available for this selection yet.',
    'Estimasi tunggu': 'Estimated wait',
    'Antrian hari ini': 'Today queue',
    'Area dekat jendela': 'Area near the window',
    'Datang sedikit terlambat': 'Arriving a little late',
    'Butuh konsultasi dulu': 'Need a consultation first',
    'Ruangan tenang': 'Quiet room',
    'Staff wanita': 'Female staff',
    'Tidak pakai produk wangi': 'No scented products',
    'Masukkan kode voucher dulu.': 'Enter a voucher code first.',
    'Voucher berhasil diterapkan.': 'Voucher applied successfully.',
    'Voucher tidak bisa digunakan.': 'Voucher cannot be used.',
    'Kode booking kamu:': 'Your booking code:',
    'Salon detail': 'Salon detail',
    'Salon Information': 'Salon Information',
    'Staff dan jadwal mengikuti pilihan booking kamu.': 'Staff and schedule follow your booking choices.',
    'Perubahan jadwal mengikuti kebijakan salon.': 'Schedule changes follow salon policy.',
    'Catatan untuk salon': 'Note for the salon',
    'Tulis catatan tambahan, opsional': 'Write an additional note, optional',
    'Voucher aktif. Kamu hemat': 'Voucher active. You saved',
    'untuk booking ini.': 'on this booking.',
    'Masukkan kode voucher untuk mengecek diskon yang tersedia.': 'Enter a voucher code to check available discounts.',
    Ringkasan: 'Summary',
    'Pilih lokasi dan salon untuk mulai.': 'Choose a location and salon to start.',
    Durasi: 'Duration',
    'Jam pasti': 'Fixed time',
    'Masuk dulu untuk melihat daftar dan detail booking kamu.': 'Sign in first to view your booking list and details.',
    'Login dibutuhkan': 'Login required',
    'Setelah login, semua booking customer akan tampil di halaman ini.': 'After login, all customer bookings will appear here.',
    'Kelola booking aktif, lihat detail kunjungan, dan cek status pembayaran kamu.': 'Manage active bookings, view visit details, and check your payment status.',
    Aktif: 'Active',
    Selesai: 'Completed',
    'Memuat...': 'Loading...',
    'Memuat booking...': 'Loading bookings...',
    'Daftar booking customer sedang disiapkan.': 'Customer bookings are being prepared.',
    'Belum ada booking': 'No bookings yet',
    'Booking yang berhasil dibuat akan muncul di sini.': 'Successful bookings will appear here.',
    'Booking tidak ditemukan atau tidak bisa dimuat.': 'Booking was not found or could not be loaded.',
    'Memuat detail booking...': 'Loading booking details...',
    'Sebentar, data booking sedang diambil.': 'One moment, booking data is being loaded.',
    'Booking tidak ditemukan': 'Booking not found',
    'Booking ini tidak ada di akun customer kamu.': 'This booking is not in your customer account.',
    'Kembali ke My Bookings': 'Back to My Bookings',
    'Detail Booking': 'Booking Detail',
    'Lihat informasi kunjungan, layanan, staff, dan status pembayaran booking ini.': 'View this booking visit information, services, staff, and payment status.',
    'Daftar Customer': 'Customer Registration',
    'Masuk untuk submit dan melihat booking.': 'Sign in to submit and view bookings.',
    'Buat akun customer untuk booking salon.': 'Create a customer account to book salons.',
    'Nama lengkap': 'Full name',
    'Nomor WhatsApp': 'WhatsApp number',
    'Pilih gender': 'Choose gender',
    'Konfirmasi Password': 'Confirm Password',
    'Membuat akun...': 'Creating account...',
    Daftar: 'Register',
    Semuanya: 'All',
    'semua lokasi': 'all locations',
    'Jenis Salon': 'Salon Type',
    'Rentang Harga': 'Price Range',
    'Layanan Populer': 'Popular Services',
    'Rating Customer': 'Customer Rating',
    'Rating Bintang': 'Star Rating',
    Fasilitas: 'Facilities',
    'Booking Terjadwal': 'Scheduled Booking',
    'Lihat lainnya': 'View more',
    'Gambar sebelumnya': 'Previous image',
    'Gambar berikutnya': 'Next image',
    'Aksi salon': 'Salon actions',
    'Simpan salon': 'Save salon',
    'Bagikan salon': 'Share salon',
    Mulai: 'From',
    'Harga detail': 'Price details',
    'Lihat Layanan': 'View Services',
    Lokasi: 'Location',
    'Pilih lokasi': 'Choose location',
    Mencari: 'Searching',
    'Saat ini': 'Current',
    'Cari salon': 'Search salons',
    'Mode tampilan': 'View mode',
    'Tampilan list': 'List view',
    'Tampilan grid': 'Grid view',
    aktif: 'active',
    Opsional: 'Optional',
    Bersihkan: 'Clear',
    Terapkan: 'Apply',
    'Memuat salon dari API...': 'Loading salons from API...',
    'Belum ada salon sesuai filter atau pencarian. Coba ubah pilihan filter.': 'No salons match your filters or search. Try changing the filters.',
    'Halaman sebelumnya': 'Previous page',
    'Halaman berikutnya': 'Next page',
    'Kembali ke atas': 'Back to top',
    'Hemat sampai 20% untuk layanan salon pilihan': 'Save up to 20% on selected salon services',
    'Paket hair spa, blow, dan treatment mulai hari ini': 'Hair spa, blowout, and treatment packages starting today',
    'Temukan slot cepat untuk manicure dan facial': 'Find quick slots for manicures and facials',
    'Booking haircut, hair spa, manicure, facial, dan treatment favorit dari salon terdekat dengan jadwal yang jelas.': 'Book haircuts, hair spas, manicures, facials, and favorite treatments from nearby salons with clear schedules.',
    'Cari Salon': 'Search Salons',
    'Lihat cara booking': 'See how booking works',
    'Pilih branch, layanan, staff, dan jadwal tanpa harus chat bolak-balik. Semua detail harga dan durasi terlihat sebelum booking.': 'Choose a branch, services, staff, and schedule without back-and-forth chat. All price and duration details are visible before booking.',
    'Branch dan layanan aktif dari provider yang sudah terdaftar.': 'Active branches and services from registered providers.',
    'Cek jadwal staff dan antrian sebelum membuat booking.': 'Check staff schedules and queues before creating a booking.',
    '/mulai dari': '/from',
    'Booking salon jadi jauh lebih gampang. Saya bisa pilih layanan, staff, dan jam kosong tanpa harus menunggu balasan chat.': 'Salon booking is much easier. I can choose services, staff, and open times without waiting for chat replies.',
    'Bantuan untuk mencari salon, mengubah jadwal, dan melihat status booking.': 'Help for finding salons, changing schedules, and viewing booking status.',
    'Pilih bayar di salon, DP, atau pembayaran penuh sesuai kebijakan layanan.': 'Choose pay at salon, down payment, or full payment based on the service policy.',
    'Kelola branch': 'Manage branches',
    'Atur staff dan jadwal': 'Set staff and schedules',
    'Promo salon untuk booking berikutnya': 'Salon promos for your next booking',
    'Temukan voucher dan penawaran salon yang bisa kamu pakai saat checkout. Pilih layanan, cek jadwal, lalu masukkan kode promo di halaman pembayaran.': 'Find vouchers and salon offers you can use at checkout. Choose services, check schedules, then enter the promo code on the payment page.',
    'Cari Layanan Promo': 'Find Promo Services',
    'Buat Akun': 'Create Account',
    'tax transparan': 'transparent tax',
    'dicek saat checkout': 'checked at checkout',
    'Cepat': 'Fast',
    'langsung booking': 'instant booking',
    'Promo customer baru untuk booking pertama. Masukkan kode saat review pembayaran.': 'New customer promo for the first booking. Enter the code during payment review.',
    'Penawaran hari kerja untuk slot salon yang tersedia di tanggal pilihan kamu.': 'Weekday offers for available salon slots on your chosen date.',
    'Reward ringan untuk customer yang sudah login dan booking dari akun yang sama.': 'A small reward for customers who are logged in and book from the same account.',
    'Voucher divalidasi otomatis': 'Voucher is validated automatically',
    'Sistem akan mengecek kode voucher, masa berlaku, limit pemakaian, dan layanan yang dipilih sebelum menghitung total bayar.': 'The system checks the voucher code, validity period, usage limit, and selected services before calculating the total payment.',
    'Artikel singkat sebelum booking salon': 'Short articles before booking a salon',
    'Baca panduan ringan tentang layanan, durasi, promo, dan cara memilih jadwal sebelum lanjut ke katalog salon.': 'Read quick guides about services, duration, promos, and how to choose a schedule before continuing to the salon catalog.',
    'Lihat Salon': 'View Salons',
    'Cek Promo': 'Check Promos',
    'panduan pilihan': 'selected guides',
    'rata-rata baca': 'average read',
    Update: 'Updated',
    'mengikuti booking': 'follows booking',
    'Cara memilih layanan salon yang tepat': 'How to choose the right salon service',
    'Kenali kebutuhan, durasi, dan hasil yang kamu harapkan sebelum menambahkan layanan ke booking.': 'Know your needs, duration, and expected results before adding services to your booking.',
    'Booking jam pasti atau ikut antrian?': 'Fixed-time booking or join the queue?',
    'Pilih mode booking yang cocok berdasarkan durasi layanan, ketersediaan staff, dan waktu datang.': 'Choose the booking mode that fits the service duration, staff availability, and arrival time.',
    'Cara memakai voucher saat checkout': 'How to use a voucher at checkout',
    'Masukkan kode promo di halaman pembayaran dan sistem akan menghitung diskon yang valid.': 'Enter the promo code on the payment page and the system will calculate a valid discount.',
    'Artikel terhubung ke alur booking': 'Articles connected to the booking flow',
    'Setelah membaca, customer bisa langsung lanjut mencari salon, memilih layanan, memakai voucher, dan menyelesaikan booking.': 'After reading, customers can continue finding salons, choosing services, using vouchers, and completing bookings.',
    'Kelola branch salon dan booking dari satu sistem': 'Manage salon branches and bookings from one system',
    'Halaman ini ditujukan untuk provider yang ingin menerima booking online, mengatur layanan, staff, jadwal, antrian, dan pembayaran.': 'This page is for providers who want to accept online bookings and manage services, staff, schedules, queues, and payments.',
    'multi lokasi': 'multiple locations',
    'skill & jadwal': 'skills & schedules',
    'queue & schedule': 'queue & schedule',
    'Manajemen branch': 'Branch management',
    'Atur lokasi, jam operasional, foto, dan informasi layanan untuk tiap cabang salon.': 'Set locations, operating hours, photos, and service information for each salon branch.',
    'Staff dan skill': 'Staff and skills',
    'Hubungkan staff dengan layanan supaya customer hanya mendapat pilihan yang tersedia.': 'Connect staff with services so customers only see available choices.',
    'Booking operasional': 'Booking operations',
    'Pantau jadwal, antrian, walk-in, pembayaran, dan status booking dari dashboard provider.': 'Monitor schedules, queues, walk-ins, payments, and booking statuses from the provider dashboard.',
    'Terhubung dengan customer landing': 'Connected with customer landing',
    'Data provider yang aktif akan muncul di katalog customer sehingga pencarian, detail layanan, dan checkout tetap konsisten.': 'Active provider data appears in the customer catalog so search, service details, and checkout stay consistent.',
    'Promo belum bisa dimuat dari database.': 'Promos could not be loaded from the database yet.',
    'Memuat coupon aktif dari database...': 'Loading active coupons from the database...',
    'Belum ada promo aktif': 'No active promos yet',
    'Coupon yang aktif dari dashboard admin akan otomatis tampil di sini.': 'Coupons activated from the admin dashboard will appear here automatically.',
    'Pakai promo': 'Use promo',
    Lanjutkan: 'Continue',
    'Tanpa batas': 'No limit',
    'voucher aktif': 'active vouchers',
    'hemat tersedia': 'savings available',
    'berakhir terdekat': 'nearest ending date',
    'Promo layanan': 'Service promo',
    'berlaku sampai': 'valid until',
    'Kuota tidak terbatas': 'Unlimited quota',
    'Layanan dipilih': 'Selected services',
    'Belum ada service untuk branch ini.': 'No services are available for this branch yet.',
    'DP mulai': 'DP starts at',
    'Bisa dipilih untuk jadwal tersedia': 'Can be selected for available schedules',
    Hapus: 'Remove',
    Sebelumnya: 'Previous',
    Berikutnya: 'Next',
    'Mengecek staff yang cocok...': 'Checking matching staff...',
    'Belum ada staff yang tersedia untuk layanan ini.': 'No staff are available for this service yet.',
    'Pilih layanan untuk melihat jadwal.': 'Choose services to see the schedule.',
    'customer sedang menunggu.': 'customers are waiting.',
    'Pelayanan rapi, staff responsif, dan jadwal mudah dipilih dari halaman booking.': 'Neat service, responsive staff, and easy schedule selection from the booking page.',
    'Pilih beberapa layanan dalam satu booking dan atur jadwal sekali jalan.': 'Choose multiple services in one booking and schedule them in one step.',
    'Pilih lokasi salon': 'Choose salon location',
    Staf: 'Staff',
    'Pilih staf favorit': 'Choose your favorite staff',
    Jadwal: 'Schedule',
    'Pilih tanggal & jam': 'Choose date & time',
    Pembayaran: 'Payment',
    'Selesaikan pembayaran': 'Complete payment',
};

const sourceEnglishToIndonesian = {
    Home: 'Beranda',
    'Find Services': 'Cari Layanan',
    Promo: 'Promo',
    Articles: 'Artikel',
    'For Business': 'Untuk Bisnis',
    'Sign In': 'Masuk',
    'Sign Up': 'Daftar',
    Notifications: 'Notifikasi',
    'My Bookings': 'Booking Saya',
    'My Wishlist': 'Wishlist Saya',
    Settings: 'Pengaturan',
    'Help Center': 'Pusat Bantuan',
    'Sign Out': 'Keluar',
    'Mode:': 'Mode:',
    'Language:': 'Bahasa:',
    English: 'English',
    Indonesia: 'Indonesia',
    'Booking home': 'Beranda booking',
    'JasaKu customer home': 'Beranda customer JasaKu',
    'Main navigation': 'Navigasi utama',
    'Light mode': 'Mode terang',
    'Dark mode': 'Mode gelap',
    'System mode': 'Mode sistem',
    'Could not connect to the Laravel API. Make sure the backend is running at http://127.0.0.1:8000.': 'Tidak bisa terhubung ke API Laravel. Pastikan backend berjalan di http://127.0.0.1:8000.',
    'Booking Support': 'Bantuan Booking',
    'Book trusted salons, compare services, choose staff, and manage every visit from one customer account.': 'Booking salon terpercaya, bandingkan layanan, pilih staff, dan kelola setiap kunjungan dari satu akun customer.',
    'Active salon branches': 'Branch salon aktif',
    Customer: 'Customer',
    Account: 'Akun',
    Promos: 'Promo',
    'Voucher & Deals': 'Voucher & Promo',
    'Popular Cities': 'Kota Populer',
    Jakarta: 'Jakarta',
    Bandung: 'Bandung',
    Surabaya: 'Surabaya',
    Yogyakarta: 'Yogyakarta',
    Bali: 'Bali',
    'Hair & Beauty': 'Rambut & Beauty',
    'Spa & Massage': 'Spa & Massage',
    'Nails & Care': 'Nail & Care',
    'Queue & Schedule': 'Antrian & Jadwal',
    'Quick Access': 'Akses Cepat',
    'Salon booking Haircut Hair spa Facial Manicure Pedicure Massage Promo vouchers Pay at salon Scheduled booking Queue booking Staff selection Branch details Customer support Provider portal': 'Booking salon Haircut Hair spa Facial Manicure Pedicure Massage Voucher promo Bayar di salon Booking terjadwal Booking antrian Pilih staff Detail branch Bantuan customer Portal provider',
    'Down Payment': 'DP',
    'Full Payment': 'Pembayaran Penuh',
    'Voucher Discount': 'Diskon Voucher',
    'Need Help?': 'Butuh Bantuan?',
    'Booking Status': 'Status Booking',
    'Payment Policy': 'Kebijakan Pembayaran',
    'Contact Support': 'Hubungi Support',
    'Copyright 2026 JasaKu. All rights reserved.': 'Copyright 2026 JasaKu. Semua hak dilindungi.',
    'Terms of Service': 'Syarat Layanan',
    'Refund Policy': 'Kebijakan Refund',
    'View Customer Flow': 'Lihat Alur Customer',
    'Your Best Beauty Day Starts Here!': 'Hari Cantik Terbaikmu Dimulai Di Sini!',
    'Featured Salons': 'Salon Pilihan',
    'Explore Salon Cities': 'Jelajahi Kota Salon',
    'Booking Help': 'Bantuan Booking',
    'Clear Payment': 'Pembayaran Jelas',
    'For salon business': 'Untuk bisnis salon',
    'Choose services': 'Pilih layanan',
    '120+ active salons': '120+ salon aktif',
    '80+ beauty services': '80+ layanan beauty',
    '90+ salon branches': '90+ branch salon',
    'spa and massage': 'spa dan massage',
    'barber and salon': 'barber dan salon',
    'View On Map': 'Lihat di Peta',
    'View all': 'Lihat semua',
    'About This Salon': 'Tentang Salon Ini',
    'Main Highlights': 'Highlight Utama',
    Advantages: 'Keunggulan',
    Amenities: 'Fasilitas',
    Services: 'Layanan',
    'Payment Method': 'Metode Pembayaran',
    'Online payment': 'Pembayaran online',
    'Some services require a down payment': 'Beberapa layanan membutuhkan DP',
    'No down payment for selected services': 'Tanpa DP untuk layanan tertentu',
    'Price follows the selected services': 'Harga mengikuti layanan yang dipilih',
    Schedule: 'Jadwal',
    'Choose a slot from the calendar': 'Pilih slot dari kalender',
    'Supports today queue': 'Mendukung antrian hari ini',
    'Choose an available slot': 'Pilih slot tersedia',
    'Salon gallery': 'Galeri salon',
    'This salon': 'Salon ini',
    'your selected area': 'area pilihan kamu',
    'Choose available services, select your favorite staff, then continue with an open time slot.': 'Pilih layanan yang tersedia, tentukan staff favorit, lalu lanjutkan dengan slot waktu yang masih tersedia.',
    'The booking summary appears on the card on the right so it is easy to review before payment.': 'Semua ringkasan booking akan tampil di kartu sebelah kanan supaya mudah dicek sebelum pembayaran.',
    'Choose staff and schedule slots directly': 'Pilih staff dan slot jadwal secara langsung',
    'Estimated price and duration appear before continuing': 'Estimasi harga dan durasi tampil sebelum lanjut booking',
    'Service Options': 'Pilihan Layanan',
    'Select Option': 'Pilih Opsi',
    'Select Service': 'Pilih Layanan',
    'Staff Options': 'Pilihan Staff',
    'Any Available Staff': 'Staff Tersedia Mana Saja',
    'Available Schedule': 'Jadwal Tersedia',
    'Customer Review': 'Ulasan Customer',
    'Based on branch rating': 'Berdasarkan rating branch',
    'Price Start at': 'Harga Mulai Dari',
    'per booking': 'per booking',
    'queue today': 'antrian hari ini',
    'Booking Summary': 'Ringkasan Booking',
    Staff: 'Staff',
    Duration: 'Durasi',
    Details: 'Detail',
    'Continue Booking': 'Lanjut Booking',
    "Today's Best Deal": 'Promo Terbaik Hari Ini',
    'Service Plan': 'Paket Layanan',
    'Payment Summary': 'Ringkasan Pembayaran',
    'Payment Type': 'Tipe Pembayaran',
    'Payment Status': 'Status Pembayaran',
    'Due Now': 'Tagihan Saat Ini',
    'Total Booking': 'Total Booking',
    'Review your Booking': 'Review Booking Kamu',
    'Guest Details': 'Detail Tamu',
    'Main Guest': 'Tamu Utama',
    Title: 'Sapaan',
    'First Name': 'Nama Depan',
    'Last Name': 'Nama Belakang',
    'Enter your first name': 'Masukkan nama depan',
    'Enter your last name': 'Masukkan nama belakang',
    '+ Add New Guest': '+ Tambah Tamu Baru',
    'Email id': 'Email',
    'Enter your email': 'Masukkan email',
    'Booking voucher will be sent to this email ID': 'Voucher booking akan dikirim ke email ini',
    'Mobile number': 'Nomor ponsel',
    'Enter your mobile number': 'Masukkan nomor ponsel',
    Login: 'Login',
    'to prefill all details and get access to secret deals': 'untuk mengisi detail otomatis dan mengakses promo rahasia',
    'Special request': 'Permintaan khusus',
    'Payment Options': 'Pilihan Pembayaran',
    'Get Additional Discount': 'Dapatkan Diskon Tambahan',
    'Login to access saved payments and discounts!': 'Login untuk mengakses pembayaran tersimpan dan diskon!',
    'Login now': 'Login sekarang',
    'Credit or Debit Card': 'Kartu Kredit atau Debit',
    'We Accept:': 'Kami Menerima:',
    'Card Number *': 'Nomor Kartu *',
    'Expiration date *': 'Tanggal kedaluwarsa *',
    Month: 'Bulan',
    Year: 'Tahun',
    'CVV / CVC *': 'CVV / CVC *',
    'Name on Card *': 'Nama di Kartu *',
    'Enter card holder name': 'Masukkan nama pemegang kartu',
    'Due now': 'Tagihan sekarang',
    'Processing...': 'Memproses...',
    'Book Now': 'Booking Sekarang',
    'Pay Now': 'Bayar Sekarang',
    'Pay with Bank Transfer': 'Bayar dengan Transfer Bank',
    'Pay at Salon': 'Bayar di Salon',
    'By processing, You accept Booking': 'Dengan memproses, kamu menerima Booking',
    'Terms of Services': 'Syarat Layanan',
    Policy: 'Kebijakan',
    'Price Summary': 'Ringkasan Harga',
    'Service Charges': 'Biaya Layanan',
    'Total Discount': 'Total Diskon',
    'Price after discount': 'Harga setelah diskon',
    'Fee & Tax': 'Biaya & Pajak',
    'Payable Now': 'Total Dibayar Sekarang',
    'Offer & Discount': 'Promo & Diskon',
    'Voucher active. You saved': 'Voucher aktif. Kamu hemat',
    'on this booking.': 'untuk booking ini.',
    Remove: 'Hapus',
    'Coupon code': 'Kode coupon',
    Checking: 'Mengecek',
    Apply: 'Terapkan',
    'Why Sign up or Log in': 'Kenapa Daftar atau Login',
    'Get Access to Secret Deal': 'Akses Promo Rahasia',
    'Book Faster': 'Booking Lebih Cepat',
    'Manage Your Booking': 'Kelola Booking Kamu',
    'Welcome back': 'Selamat datang kembali',
    'Create new account': 'Buat akun baru',
    'New here?': 'Baru di sini?',
    'Already a member?': 'Sudah punya akun?',
    'Create an account': 'Buat akun',
    'Log in': 'Login',
    'Enter email id': 'Masukkan email',
    'Enter password': 'Masukkan password',
    'Toggle password visibility': 'Tampilkan atau sembunyikan password',
    'Remember me?': 'Ingat saya?',
    'Forgot password?': 'Lupa password?',
    'Logging in...': 'Login...',
    'Enter full name': 'Masukkan nama lengkap',
    'Confirm password': 'Konfirmasi password',
    'Keep me signed in': 'Biarkan saya tetap login',
    'Creating account...': 'Membuat akun...',
    'Sign up': 'Daftar',
    'Or sign in with': 'Atau masuk dengan',
    'Continue with Google': 'Lanjut dengan Google',
    'Continue with Facebook': 'Lanjut dengan Facebook',
    'Secure account illustration': 'Ilustrasi akun aman',
    'Privacy Policy': 'Kebijakan Privasi',
    Terms: 'Syarat',
    Support: 'Bantuan',
    'Privacy policy': 'Kebijakan privasi',
    'Refund policy': 'Kebijakan refund',
    'Services are available in the details': 'Layanan tersedia di detail',
    'Staff follows the salon schedule': 'Staff mengikuti jadwal salon',
    'Operating hours follow the salon schedule': 'Jam operasional mengikuti salon',
    'Choose services from the salon catalog': 'Pilih layanan dari katalog salon',
    'Service details are available after choosing a salon': 'Detail layanan tersedia setelah memilih salon',
    'Choose staff during booking': 'Pilih staff saat booking',
    'Staff availability follows the schedule': 'Staff tersedia mengikuti jadwal',
};

const englishToIndonesian = {
    ...Object.fromEntries(Object.entries(indonesianToEnglish).map(([id, en]) => [en, id])),
    ...sourceEnglishToIndonesian,
};

const translationMaps = {
    en: indonesianToEnglish,
    id: englishToIndonesian,
};

const dynamicTranslations = {
    en: [
        [/^Lokasi saat ini - (.+)$/i, (match) => `Current location - ${match[1]}`],
        [/^(\d+) Salon di (.+)$/i, (match) => `${match[1]} salons in ${match[2]}`],
        [/^(\d+) Layanan$/i, (match) => `${match[1]} Services`],
        [/^(\d+) layanan aktif$/i, (match) => `${match[1]} active services`],
        [/^(\d+) layanan tersedia$/i, (match) => `${match[1]} services available`],
        [/^(\d+) layanan - total (.+)$/i, (match) => `${match[1]} services - total ${match[2]}`],
        [/^(\d+) layanan$/i, (match) => `${match[1]} services`],
        [/^(\d+) menit$/i, (match) => `${match[1]} minutes`],
        [/^(\d+\s*-\s*\d+) menit$/i, (match) => `${match[1]} minutes`],
        [/^\/(\d+) menit$/i, (match) => `/${match[1]} minutes`],
        [/^(\d+) customer sedang menunggu(?: di antrian aktif)?\.$/i, (match) => `${match[1]} customers are waiting${match[0].includes('antrian aktif') ? ' in the active queue' : ''}.`],
        [/^(\d+) aktif$/i, (match) => `${match[1]} active`],
        [/^(\d+) kuota tersisa$/i, (match) => `${match[1]} quotas left`],
        [/^(.+) km dari lokasi kamu$/i, (match) => `${match[1]} km from your location`],
        [/^(.+) - berlaku sampai (.+)\.$/i, (match) => `${match[1]} - valid until ${match[2]}.`],
        [/^Lihat opsi lain untuk (.+)$/i, (match) => `View more options for ${match[1]}`],
        [/^DP mulai (.+)$/i, (match) => `DP starts at ${match[1]}`],
        [/^Buka (.+)$/i, (match) => `Open ${match[1]}`],
        [/^Slot berikutnya (.+)$/i, (match) => `Next slot ${match[1]}`],
        [/^(.+) menyediakan layanan profesional dengan pilihan staff dan jadwal yang bisa kamu atur langsung sebelum booking\.$/i, (match) => `${match[1]} provides professional services with staff and schedule options you can set before booking.`],
        [/^Branch ini berlokasi di (.+) dengan jam operasional (.+)\.$/i, (match) => `This branch is located in ${match[1]} with operating hours ${match[2]}.`],
        [/^(.+) layanan aktif dari katalog salon$/i, (match) => `${match[1]} active services from the salon catalog`],
        [/^Voucher aktif\. Kamu hemat (.+) untuk booking ini\.$/i, (match) => `Voucher active. You saved ${match[1]} on this booking.`],
    ],
    id: [
        [/^Current location - (.+)$/i, (match) => `Lokasi saat ini - ${match[1]}`],
        [/^(\d+) active salon branches$/i, (match) => `${match[1]} branch salon aktif`],
        [/^(\d+) salons in (.+)$/i, (match) => `${match[1]} Salon di ${match[2]}`],
        [/^(\d+) Services$/i, (match) => `${match[1]} Layanan`],
        [/^(\d+) active services$/i, (match) => `${match[1]} layanan aktif`],
        [/^(\d+) services available$/i, (match) => `${match[1]} layanan tersedia`],
        [/^(\d+) services - total (.+)$/i, (match) => `${match[1]} layanan - total ${match[2]}`],
        [/^(\d+) services$/i, (match) => `${match[1]} layanan`],
        [/^(\d+) minutes$/i, (match) => `${match[1]} menit`],
        [/^(\d+\s*-\s*\d+) minutes$/i, (match) => `${match[1]} menit`],
        [/^\/(\d+) minutes$/i, (match) => `/${match[1]} menit`],
        [/^(\d+) customers are waiting(?: in the active queue)?\.$/i, (match) => `${match[1]} customer sedang menunggu${match[0].includes('active queue') ? ' di antrian aktif' : ''}.`],
        [/^(\d+) selected$/i, (match) => `${match[1]} dipilih`],
        [/^(\d+) active$/i, (match) => `${match[1]} aktif`],
        [/^(\d+) quotas left$/i, (match) => `${match[1]} kuota tersisa`],
        [/^(.+) km from your location$/i, (match) => `${match[1]} km dari lokasi kamu`],
        [/^(.+) - valid until (.+)\.$/i, (match) => `${match[1]} - berlaku sampai ${match[2]}.`],
        [/^View more options for (.+)$/i, (match) => `Lihat opsi lain untuk ${match[1]}`],
        [/^DP starts at (.+)$/i, (match) => `DP mulai ${match[1]}`],
        [/^Open (.+)$/i, (match) => `Buka ${match[1]}`],
        [/^Next slot (.+)$/i, (match) => `Slot berikutnya ${match[1]}`],
        [/^(.+) provides professional services with staff and schedule options you can set before booking\.$/i, (match) => `${match[1]} menyediakan layanan profesional dengan pilihan staff dan jadwal yang bisa kamu atur langsung sebelum booking.`],
        [/^This branch is located in (.+) with operating hours (.+)\.$/i, (match) => `Branch ini berlokasi di ${match[1]} dengan jam operasional ${match[2]}.`],
        [/^(.+) active services from the salon catalog$/i, (match) => `${match[1]} layanan aktif dari katalog salon`],
        [/^Voucher active\. You saved (.+) on this booking\.$/i, (match) => `Voucher aktif. Kamu hemat ${match[1]} untuk booking ini.`],
    ],
};

const textNodeState = new WeakMap();
const attributeState = new WeakMap();

function normalizeText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
}

function preserveOuterWhitespace(original, translated) {
    const leading = String(original).match(/^\s*/)?.[0] || '';
    const trailing = String(original).match(/\s*$/)?.[0] || '';

    return `${leading}${translated}${trailing}`;
}

function translateValue(value, language) {
    const normalized = normalizeText(value);

    if (!normalized) return value;

    const exact = translationMaps[language]?.[normalized];

    if (exact) {
        return preserveOuterWhitespace(value, exact);
    }

    for (const [pattern, translate] of dynamicTranslations[language] || []) {
        const match = normalized.match(pattern);

        if (match) {
            return preserveOuterWhitespace(value, translate(match));
        }
    }

    return value;
}

function readInitialLanguage() {
    if (typeof window === 'undefined') return 'en';

    const stored = window.localStorage.getItem(languageStorageKey);

    return supportedLanguages.includes(stored) ? stored : 'en';
}

export function customerDateLocale() {
    if (typeof window === 'undefined') return 'en-US';

    return window.localStorage.getItem(languageStorageKey) === 'id' ? 'id-ID' : 'en-US';
}

export function CustomerLanguageProvider({ children }) {
    const [language, setLanguageState] = useState(readInitialLanguage);

    function setLanguage(nextLanguage) {
        const normalized = supportedLanguages.includes(nextLanguage) ? nextLanguage : 'en';

        setLanguageState(normalized);
        window.localStorage.setItem(languageStorageKey, normalized);
    }

    useEffect(() => {
        document.documentElement.lang = language === 'id' ? 'id' : 'en';
    }, [language]);

    const value = useMemo(() => ({
        language,
        setLanguage,
        isEnglish: language === 'en',
    }), [language]);

    return (
        <CustomerLanguageContext.Provider value={value}>
            {children}
        </CustomerLanguageContext.Provider>
    );
}

export function useCustomerLanguage() {
    const context = useContext(CustomerLanguageContext);

    if (!context) {
        throw new Error('useCustomerLanguage must be used inside CustomerLanguageProvider');
    }

    return context;
}

function shouldSkipElement(element) {
    if (!element) return true;

    const tagName = element.tagName?.toLowerCase();

    return ['script', 'style', 'noscript', 'svg', 'path'].includes(tagName)
        || element.closest('[data-no-localize]');
}

function localizeTextNode(node, language) {
    if (shouldSkipElement(node.parentElement)) return;

    const state = textNodeState.get(node);
    const current = node.nodeValue || '';
    const previousTranslated = state?.translated;
    const previousOriginal = state?.original;
    const original = state && current === previousTranslated
        ? previousOriginal
        : current;
    const translated = translateValue(original, language);

    textNodeState.set(node, { original, translated });

    if (current !== translated) {
        node.nodeValue = translated;
    }
}

function localizeAttribute(element, attribute, language) {
    if (!element.hasAttribute(attribute) || shouldSkipElement(element)) return;

    const current = element.getAttribute(attribute) || '';
    const elementState = attributeState.get(element) || {};
    const state = elementState[attribute];
    const original = state && current === state.translated
        ? state.original
        : current;
    const translated = translateValue(original, language);

    elementState[attribute] = { original, translated };
    attributeState.set(element, elementState);

    if (current !== translated) {
        element.setAttribute(attribute, translated);
    }
}

function localizeCustomerRoot(root, language) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const textNodes = [];

    while (walker.nextNode()) {
        textNodes.push(walker.currentNode);
    }

    textNodes.forEach((node) => localizeTextNode(node, language));

    const attributeSelector = localizableAttributes.map((attribute) => `[${attribute}]`).join(',');

    root.querySelectorAll(attributeSelector).forEach((element) => {
        localizableAttributes.forEach((attribute) => localizeAttribute(element, attribute, language));
    });
}

export function useLocalizedCustomerText() {
    const { language } = useCustomerLanguage();

    useEffect(() => {
        const root = document.getElementById('customer-landing-root');

        if (!root) return undefined;

        let frame = 0;
        let isApplying = false;

        function applyLocalization() {
            frame = 0;
            isApplying = true;
            localizeCustomerRoot(root, language);
            isApplying = false;
        }

        function scheduleLocalization() {
            if (isApplying || frame) return;

            frame = window.requestAnimationFrame(applyLocalization);
        }

        applyLocalization();

        const observer = new MutationObserver(scheduleLocalization);

        observer.observe(root, {
            attributes: true,
            attributeFilter: localizableAttributes,
            characterData: true,
            childList: true,
            subtree: true,
        });

        return () => {
            observer.disconnect();
            if (frame) window.cancelAnimationFrame(frame);
        };
    }, [language]);
}
