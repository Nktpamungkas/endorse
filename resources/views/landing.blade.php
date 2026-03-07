<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Endorse Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #102544;
            --muted: #5c6b7a;
            --primary: #0f4c81;
            --accent: #f39c12;
            --bg-start: #f4f8ff;
            --bg-end: #fff6ec;
        }
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at 10% 10%, #deecff 0%, transparent 35%),
                        radial-gradient(circle at 80% 12%, #ffe6c2 0%, transparent 40%),
                        linear-gradient(135deg, var(--bg-start), var(--bg-end));
        }
        .hero {
            padding: 90px 0 70px;
        }
        .badge-soft {
            background: rgba(15, 76, 129, 0.1);
            color: var(--primary);
            font-weight: 700;
            border-radius: 999px;
            padding: 0.4rem 0.9rem;
            font-size: 0.9rem;
        }
        .card-soft {
            border: 1px solid rgba(16, 37, 68, 0.08);
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(16, 37, 68, 0.08);
            background: #fff;
        }
        .pricing-card {
            border: 1px solid rgba(16, 37, 68, 0.12);
            border-radius: 16px;
            padding: 1.6rem;
            background: #ffffff;
            height: 100%;
            position: relative;
        }
        .pricing-card.popular {
            border-color: var(--primary);
            box-shadow: 0 18px 45px rgba(15, 76, 129, 0.18);
        }
        .check {
            color: #198754;
        }
        .footer {
            color: var(--muted);
            font-size: 0.95rem;
            padding: 26px 0 20px;
        }
        .cta-btn {
            padding: 0.85rem 1.35rem;
            font-weight: 700;
            border-radius: 12px;
        }
        @media (max-width: 767.98px) {
            .hero { padding: 56px 0 40px; }
            .display-5 { font-size: 2rem; }
            .lead { font-size: 1rem; }
            .pricing-card { padding: 1.2rem; }
            .d-flex.justify-content-center.gap-3 { flex-direction: column; align-items: stretch !important; }
            .cta-btn { width: 100%; text-align: center; }
            nav .btn { width: auto; }
        }
    </style>
</head>
<body>
<div class="container">
    <nav class="navbar navbar-expand-lg mt-3">
        <a class="navbar-brand fw-bold" href="{{ route('landing') }}">Endorse Tracker</a>
        <div class="ms-auto d-flex gap-2">
            <a class="btn btn-outline-dark btn-sm" href="{{ route('login.form') }}">Login</a>
            <a class="btn btn-dark btn-sm" href="{{ route('login.form') }}">Mulai Sekarang</a>
        </div>
    </nav>

    <section class="hero text-center">
        <div class="badge-soft mx-auto mb-3 d-inline-flex align-items-center gap-2">
            <span>Monitoring Endorse TikTok & Instagram</span>
        </div>
        <h1 class="display-5 fw-bold mb-3">Catat endorse, status, dan pembayaran dalam satu dasbor.</h1>
        <p class="lead text-muted mb-4">Dari deal masuk, revisi, insight, sampai pembayaran — semuanya terpantau rapi, lengkap dengan perhitungan laba bersih otomatis.</p>
        <div class="d-flex justify-content-center gap-3">
            <a class="btn btn-dark cta-btn" href="{{ route('login.form') }}">Masuk / Daftar</a>
            <a class="btn btn-outline-dark cta-btn" href="#pricing">Lihat Paket</a>
        </div>
    </section>

    <section class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="fw-bold text-uppercase text-muted small mb-2">Kontrol Penuh</div>
                <h5 class="fw-bold mb-2">Status lengkap hingga insight & payment</h5>
                <p class="text-muted mb-0">Deal, beli produk, draft, revisi, posting, insight, sampai pembayaran. Semua status jelas beserta tanggal due.</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="fw-bold text-uppercase text-muted small mb-2">Keuangan Jelas</div>
                <h5 class="fw-bold mb-2">Hitung laba bersih otomatis</h5>
                <p class="text-muted mb-0">Catat fee, modal produk, reimburse, biaya lain. Laba bersih dihitung otomatis dan tampil di dashboard.</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-soft p-4 h-100">
                <div class="fw-bold text-uppercase text-muted small mb-2">Kolaborasi Aman</div>
                <h5 class="fw-bold mb-2">Akun master & trial terpisah</h5>
                <p class="text-muted mb-0">Data tiap akun terpisah. Master bisa kelola user trial dan hapus data trial dengan sekali klik.</p>
            </div>
        </div>
    </section>

    <section id="pricing" class="mb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Paket Harga Sederhana</h2>
            <p class="text-muted">Pilih yang sesuai kebutuhan, bisa mingguan atau bulanan.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="pricing-card">
                    <div class="small text-muted fw-semibold mb-2">Harian / Cepat</div>
                    <h3 class="fw-bold">Rp 15.000<span class="fs-6 text-muted"> / minggu</span></h3>
                    <p class="text-muted">Cocok untuk trial berbayar atau kebutuhan jangka pendek.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><span class="check">✔</span> Semua fitur tracking endorse</li>
                        <li class="mb-2"><span class="check">✔</span> Export laporan ke Excel</li>
                        <li class="mb-2"><span class="check">✔</span> Notasi rupiah & perhitungan laba</li>
                        <li class="mb-2"><span class="check">✔</span> Manajemen status & insight due</li>
                    </ul>
                    <a href="https://wa.me/6285156637499?text=Halo%2C%20saya%20ingin%20paket%20Mingguan%20%28Rp15.000%29%20Endorse%20Tracker.%20Mohon%20info%20pembayaran%20dan%20aksesnya."
                       class="btn btn-outline-dark w-100"
                       target="_blank" rel="noopener">Pilih Paket Mingguan</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="pricing-card popular">
                    <div class="small text-muted fw-semibold mb-2">Rekomendasi</div>
                    <h3 class="fw-bold">Rp 30.000<span class="fs-6 text-muted"> / bulan</span></h3>
                    <p class="text-muted">Lebih hemat untuk penggunaan rutin produksi konten.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><span class="check">✔</span> Semua di paket mingguan</li>
                        <li class="mb-2"><span class="check">✔</span> Kelola user trial (User1-User3)</li>
                        <li class="mb-2"><span class="check">✔</span> Riwayat revisi & draft terarsip</li>
                        <li class="mb-2"><span class="check">✔</span> Dukungan prioritas via admin</li>
                    </ul>
                    <a href="https://wa.me/6285156637499?text=Halo%2C%20saya%20ingin%20paket%20Bulanan%20%28Rp30.000%29%20Endorse%20Tracker.%20Mohon%20info%20pembayaran%20dan%20aksesnya."
                       class="btn btn-dark w-100"
                       target="_blank" rel="noopener">Pilih Paket Bulanan</a>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card-soft p-4 h-100">
                <h5 class="fw-bold mb-3">Benefit utama</h5>
                <ul class="text-muted mb-0">
                    <li>Dashboard laba bersih, pendapatan, modal, dan jumlah endorse aktif.</li>
                    <li>Status lengkap dengan due date insight & payment.</li>
                    <li>Export data endorse ke Excel untuk laporan.</li>
                    <li>Input keuangan jelas: fee, modal, reimburse, biaya lain.</li>
                    <li>Upload bukti checkout (opsional) & catatan per job.</li>
                    <li>Multi-user: master + user trial terisolasi datanya.</li>
                    <li>Tampilan responsif, nyaman di HP.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-soft p-4 h-100">
                <h5 class="fw-bold mb-3">Cara mulai</h5>
                <ol class="text-muted mb-3">
                    <li>Login atau buat akun trial (User1/User2/User3) lewat akun master.</li>
                    <li>Tambah endorse: isi detail brand, status, keuangan.</li>
                    <li>Update status dan insight due seiring progress.</li>
                    <li>Export laporan atau hapus data trial bila selesai.</li>
                </ol>
                <a class="btn btn-dark" href="{{ route('login.form') }}">Masuk & Mulai</a>
            </div>
        </div>
    </section>

    <div class="footer text-center">
        © {{ date('Y') }} Endorse Tracker. Semua harga dalam IDR.
    </div>
</div>
</body>
</html>
