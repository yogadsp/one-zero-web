<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="One Zero Learning - Bimbel & Tryout Online Terpadu untuk CPNS, UTBK-SNBT, TKA, TOEFL, dan TOEIC. Dilengkapi simulasi CAT/CBT mirip aslinya, video pembahasan, dan kelas interaktif.">
  <meta name="author" content="One Zero Learning">
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <title>One Zero Learning - Bimbel & Tryout Online CPNS, UTBK, TKA, TOEFL & TOEIC</title>

  <!-- Bootstrap core CSS -->
  <link href="{{ asset('id/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

  <!-- Additional CSS Files -->
  <link rel="stylesheet" href="{{ asset('id/assets/css/templatemo-space-dynamic.css') }}">
  <link rel="stylesheet" href="{{ asset('id/assets/css/fontawesome.css') }}">
  <link rel="stylesheet" href="{{ asset('id/assets/css/animated.css') }}">
  <link rel="stylesheet" href="{{ asset('id/assets/css/owl.css') }}">

  <style>
    /* Custom Bimbel Landing Page Enhancements */
    html {
      scroll-behavior: smooth;
      scroll-padding-top: 100px;
    }
    section, .section, .main-banner {
      scroll-margin-top: 100px;
    }
    .header-area .main-nav .logo {
      display: flex;
      align-items: center;
      height: 100px;
      line-height: normal;
    }
    .background-header .main-nav .logo {
      height: 80px;
    }
    .header-area .main-nav .logo img {
      max-height: 60px;
      width: auto;
      display: block;
      transition: all 0.3s ease;
    }
    .background-header .main-nav .logo img {
      max-height: 50px;
    }
    .main-banner {
      padding-top: 180px !important;
    }
    .main-banner .left-content h2 {
      font-size: 38px !important;
      line-height: 52px !important;
      margin-top: 10px;
      margin-bottom: 18px;
    }
    .main-banner .left-content p {
      font-size: 15px !important;
      line-height: 26px !important;
      color: #555 !important;
      margin-bottom: 25px;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 18px;
      background: rgba(254, 63, 64, 0.1);
      color: #fe3f40;
      font-size: 13px;
      font-weight: 600;
      border-radius: 50px;
      margin-bottom: 18px;
      letter-spacing: 0.3px;
    }
    .hero-cta-group {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 14px;
      margin-top: 25px;
    }
    .hero-stats {
      display: flex;
      flex-wrap: wrap;
      gap: 28px;
      margin-top: 35px;
      padding-top: 25px;
      border-top: 1px solid rgba(0,0,0,0.08);
    }
    .stat-item h5 {
      font-size: 22px;
      font-weight: 800;
      color: #fe3f40;
      margin: 0;
    }
    .stat-item p {
      font-size: 13px;
      color: #666;
      margin: 0;
      font-weight: 500;
    }

    /* Program Section Styling */
    #programs {
      padding-top: 110px;
      margin-top: 30px;
    }
    .section-subtitle {
      display: inline-block;
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      color: #fe3f40;
      letter-spacing: 1.2px;
      margin-bottom: 8px;
    }
    .program-card {
      background: #fff;
      border-radius: 22px;
      padding: 32px 26px;
      box-shadow: 0px 8px 30px rgba(0,0,0,0.05);
      border: 1px solid #f0f0f0;
      transition: all 0.35s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
    }
    .program-card:hover {
      transform: translateY(-8px);
      box-shadow: 0px 18px 40px rgba(254, 63, 64, 0.12);
      border-color: rgba(254, 63, 64, 0.25);
    }
    .program-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }
    .program-icon {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }
    .program-icon.cpns { background: rgba(254, 63, 64, 0.12); color: #fe3f40; }
    .program-icon.utbk { background: rgba(3, 164, 237, 0.12); color: #03a4ed; }
    .program-icon.tka { background: rgba(245, 158, 11, 0.15); color: #d97706; }
    .program-icon.toefl { background: rgba(16, 185, 129, 0.12); color: #059669; }
    .program-icon.toeic { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }
    
    .program-badge {
      font-size: 11px;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 20px;
      letter-spacing: 0.3px;
      text-transform: uppercase;
    }
    .program-badge.cpns { background: #fee2e2; color: #dc2626; }
    .program-badge.utbk { background: #e0f2fe; color: #0284c7; }
    .program-badge.tka { background: #fef3c7; color: #b45309; }
    .program-badge.toefl { background: #d1fae5; color: #047857; }
    .program-badge.toeic { background: #ede9fe; color: #6d28d9; }

    .program-card h4 {
      font-size: 20px;
      font-weight: 700;
      color: #2a2a2a;
      margin-bottom: 10px;
    }
    .program-card p.desc {
      font-size: 13.5px;
      color: #666;
      line-height: 1.6;
      margin-bottom: 16px;
    }
    .subtest-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 18px;
    }
    .subtest-pill {
      font-size: 11.5px;
      font-weight: 600;
      background: #f8fafc;
      color: #475569;
      border: 1px solid #e2e8f0;
      padding: 4px 10px;
      border-radius: 8px;
    }
    .feature-list {
      list-style: none;
      padding: 0;
      margin: 0 0 22px 0;
    }
    .feature-list li {
      font-size: 13px;
      color: #555;
      margin-bottom: 9px;
      display: flex;
      align-items: flex-start;
      gap: 9px;
      line-height: 1.45;
    }
    .feature-list li i {
      color: #10b981;
      font-size: 14px;
      margin-top: 2px;
      flex-shrink: 0;
    }
    .program-btn-wrap {
      margin-top: auto;
    }
    .program-btn {
      display: block;
      text-align: center;
      padding: 11px 20px;
      border-radius: 25px;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s;
      text-decoration: none;
    }
    .program-btn-primary {
      background: #fe3f40;
      color: #fff !important;
    }
    .program-btn-primary:hover {
      background: #d62d2e;
      color: #fff !important;
      box-shadow: 0 5px 15px rgba(254, 63, 64, 0.3);
    }
    .program-btn-outline {
      background: #f8fafc;
      color: #03a4ed !important;
      border: 1px solid #cbd5e1;
    }
    .program-btn-outline:hover {
      background: #03a4ed;
      color: #fff !important;
      border-color: #03a4ed;
      box-shadow: 0 5px 15px rgba(3, 164, 237, 0.3);
    }

    /* Features Section Styling */
    #features {
      padding-top: 100px;
      padding-bottom: 60px;
    }
    .feature-box {
      background: #fff;
      border-radius: 22px;
      padding: 34px 26px;
      box-shadow: 0px 6px 25px rgba(0,0,0,0.05);
      border: 1px solid #f1f5f9;
      margin-bottom: 30px;
      transition: all 0.3s;
      height: 100%;
      border-top: 4px solid transparent;
    }
    .feature-box:hover {
      transform: translateY(-6px);
      box-shadow: 0px 14px 35px rgba(0,0,0,0.08);
      border-top-color: #fe3f40;
    }
    .feature-box-icon {
      width: 58px;
      height: 58px;
      border-radius: 16px;
      background: linear-gradient(135deg, #fff5f5 0%, #fee2e2 100%);
      color: #fe3f40;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 20px;
    }
    .feature-box h4 {
      font-size: 19px;
      font-weight: 700;
      color: #2a2a2a;
      margin-bottom: 12px;
    }
    .feature-box p {
      font-size: 13.5px;
      color: #64748b;
      line-height: 1.6;
      margin: 0;
    }
  </style>
</head>

<body>
  <?php 
    $login_url = url('/login');
    $register_url = url('/register');
  ?>

  <!-- ***** Header Area Start ***** -->
  <header class="header-area header-sticky wow slideInDown" data-wow-duration="0.75s" data-wow-delay="0s">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <nav class="main-nav">
            <!-- ***** Logo Start ***** -->
            <a href="{{ url('/') }}" class="logo">
              <img src="{{ asset('id/assets/images/logo.png') }}" class="img-responsive" alt="One Zero Akademi">
            </a>
            <!-- ***** Logo End ***** -->
            <!-- ***** Menu Start ***** -->
            <ul class="nav">
              <li class="scroll-to-section"><a href="#top" class="active">Beranda</a></li>
              <li class="scroll-to-section"><a href="#programs">Program Belajar</a></li>
              <li class="scroll-to-section"><a href="#features">Keunggulan</a></li>
              <li class="scroll-to-section"><a href="#about">Cara Kerja</a></li>
              <li class="scroll-to-section"><a href="#portfolio">Sertifikat</a></li>
              <li class="scroll-to-section"><a href="#contact">Hubungi Kami</a></li>
              <li class="scroll-to-section">
                <div class="main-red-button"><a href="{{ $login_url }}">Masuk</a></div>
              </li>
            </ul>
            <a class='menu-trigger'>
              <span>Menu</span>
            </a>
            <!-- ***** Menu End ***** -->
          </nav>
        </div>
      </div>
    </div>
  </header>
  <!-- ***** Header Area End ***** -->

  <!-- ***** Hero Banner Start ***** -->
  <div class="main-banner wow fadeIn" id="top" data-wow-duration="1s" data-wow-delay="0.5s">
    <div class="container">
      <div class="row">
        <div class="col-lg-12">
          <div class="row">
            <div class="col-lg-6 align-self-center">
              <div class="left-content header-text wow fadeInLeft" data-wow-duration="1s" data-wow-delay="1s">
                <div class="hero-badge">
                  <i class="fa fa-graduation-cap"></i> Platform Bimbel & Tryout Terpadu
                </div>
                <h2>Raih Skor Impian & Lolos Seleksi Bersama <em>One Zero Learning</em></h2>
                <p>Bimbingan belajar online dan sistem tryout terintegrasi untuk persiapan <strong>SKD CPNS, UTBK-SNBT, TKA</strong>, serta <strong>Sertifikasi Resmi TOEFL & TOEIC</strong>. Dilengkapi simulasi CAT/CBT berstandar nasional, video pembahasan tuntas, dan kelas interaktif.</p>
                
                <div class="hero-cta-group">
                  <div class="main-red-button">
                    <a href="{{ $register_url }}"><i class="fa fa-user-plus mr-1"></i> Mulai Belajar Gratis</a>
                  </div>
                  <div class="main-blue-button">
                    <a href="#programs">Lihat Pilihan Program</a>
                  </div>
                </div>

                <div class="hero-stats">
                  <div class="stat-item">
                    <h5>5</h5>
                    <p>Program Utama</p>
                  </div>
                  <div class="stat-item">
                    <h5>CAT & CBT</h5>
                    <p>Sistem Mirip Aslinya</p>
                  </div>
                  <div class="stat-item">
                    <h5>E-Sertifikat</h5>
                    <p>TOEFL & TOEIC Resmi</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="right-image wow fadeInRight" data-wow-duration="1s" data-wow-delay="0.5s">
                <img src="{{ asset('id/assets/images/banner-right-image.png') }}" alt="Bimbel Online One Zero Learning" style="border-radius: 5%; box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ***** Hero Banner End ***** -->

  <!-- ***** Program Belajar Unggulan Start ***** -->
  <div id="programs" class="section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 offset-lg-2 text-center">
          <div class="section-heading wow fadeInDown" data-wow-duration="1s" data-wow-delay="0.2s">
            <span class="section-subtitle">PROGRAM BIMBEL & TRYOUT</span>
            <h2>Pilihan Program Belajar <em>Sesuai Targetmu</em></h2>
            <p>Materi, bank soal, dan simulasi tryout dirancang khusus mengikuti format kisi-kisi dan sistem penilaian resmi terkini.</p>
          </div>
        </div>
      </div>

      <div class="row" style="row-gap: 30px;">
        <!-- Program 1: CPNS -->
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.2s">
          <div class="program-card">
            <div>
              <div class="program-card-header">
                <div class="program-icon cpns">
                  <i class="fa fa-university"></i>
                </div>
                <span class="program-badge cpns">CAT BKN Standard</span>
              </div>
              <h4>SKD CPNS & Kedinasan</h4>
              <p class="desc">Persiapan lengkap seleksi Calon Pegawai Negeri Sipil & Sekolah Kedinasan dengan sistem simulasi CAT BKN sesungguhnya.</p>
              
              <div class="subtest-pills">
                <span class="subtest-pill">TWK</span>
                <span class="subtest-pill">TIU</span>
                <span class="subtest-pill">TKP (Bobot 1-5)</span>
              </div>

              <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i> Sistem CAT persis ujian resmi BKN</li>
                <li><i class="fa fa-check-circle"></i> Passing grade & pembobotan otomatis</li>
                <li><i class="fa fa-check-circle"></i> Perangkingan nasional & evaluasi lolos</li>
                <li><i class="fa fa-check-circle"></i> Video pembahasan tuntas per butir soal</li>
              </ul>
            </div>

            <div class="program-btn-wrap">
              <a href="{{ $register_url }}" class="program-btn program-btn-primary">Pilih Program CPNS</a>
            </div>
          </div>
        </div>

        <!-- Program 2: UTBK-SNBT -->
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.3s">
          <div class="program-card">
            <div>
              <div class="program-card-header">
                <div class="program-icon utbk">
                  <i class="fa fa-graduation-cap"></i>
                </div>
                <span class="program-badge utbk">SNPMB Terbaru</span>
              </div>
              <h4>UTBK - SNBT</h4>
              <p class="desc">Sukses tembus Perguruan Tinggi Negeri impian dengan bank soal HOTS dan simulasi CBT berstandar SNPMB terkini.</p>
              
              <div class="subtest-pills">
                <span class="subtest-pill">TPS (PU, PK, PBM, PPU)</span>
                <span class="subtest-pill">Literasi Indonesia & Inggris</span>
                <span class="subtest-pill">Penalaran Matematika</span>
              </div>

              <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i> 7 Subtes lengkap format SNBT terbaru</li>
                <li><i class="fa fa-check-circle"></i> Bank soal HOTS & timer per subtes</li>
                <li><i class="fa fa-check-circle"></i> Prediksi skor dan analisis kelemahan</li>
                <li><i class="fa fa-check-circle"></i> Kelas interaktif bedah konsep kilat</li>
              </ul>
            </div>

            <div class="program-btn-wrap">
              <a href="{{ $register_url }}" class="program-btn program-btn-primary">Pilih Program UTBK</a>
            </div>
          </div>
        </div>

        <!-- Program 3: TKA -->
        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.4s">
          <div class="program-card">
            <div>
              <div class="program-card-header">
                <div class="program-icon tka">
                  <i class="fa fa-book"></i>
                </div>
                <span class="program-badge tka">Seleksi Mandiri PTN</span>
              </div>
              <h4>TKA (Tes Kemampuan Akademik)</h4>
              <p class="desc">Pendalaman konsep dan latihan soal mata pelajaran spesifik untuk menghadapi Ujian Mandiri PTN favorit (SIMAK UI, UTUL UGM, dll).</p>
              
              <div class="subtest-pills">
                <span class="subtest-pill">Matematika</span>
                <span class="subtest-pill">Bahasa Indonesia</span>
                <span class="subtest-pill">Bahasa Inggris</span>
                <span class="subtest-pill">Saintek / Soshum</span>
              </div>

              <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i> Penguasaan materi konsep dasar & lanjutan</li>
                <li><i class="fa fa-check-circle"></i> Soal-soal adaptif seleksi mandiri PTN</li>
                <li><i class="fa fa-check-circle"></i> Rumus praktis & cara cepat penyelesaian</li>
                <li><i class="fa fa-check-circle"></i> Pembahasan detail teks & hitungan</li>
              </ul>
            </div>

            <div class="program-btn-wrap">
              <a href="{{ $register_url }}" class="program-btn program-btn-primary">Pilih Program TKA</a>
            </div>
          </div>
        </div>

        <!-- Program 4: TOEFL -->
        <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.5s">
          <div class="program-card">
            <div>
              <div class="program-card-header">
                <div class="program-icon toefl">
                  <i class="fa fa-globe"></i>
                </div>
                <span class="program-badge toefl">Skor 310 - 677</span>
              </div>
              <h4>TOEFL Preparation & Prediction</h4>
              <p class="desc">Asah kemampuan bahasa Inggris akademik untuk syarat sidang sarjana, wisuda, beasiswa, dan berkas seleksi CPNS/BUMN.</p>
              
              <div class="subtest-pills">
                <span class="subtest-pill">Listening Comprehension (50 Soal)</span>
                <span class="subtest-pill">Structure & Written (40 Soal)</span>
                <span class="subtest-pill">Reading Comprehension (50 Soal)</span>
              </div>

              <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i> Audio listening berkualitas tinggi & jernih</li>
                <li><i class="fa fa-check-circle"></i> Konversi skor akurat standar skala 310 - 677</li>
                <li><i class="fa fa-check-circle"></i> Dilengkapi <strong>E-Sertifikat Digital</strong> langsung terbit</li>
                <li><i class="fa fa-check-circle"></i> Tips & trik grammar TOEFL paling sering keluar</li>
              </ul>
            </div>

            <div class="program-btn-wrap">
              <a href="{{ $register_url }}" class="program-btn program-btn-outline">Pilih Program TOEFL</a>
            </div>
          </div>
        </div>

        <!-- Program 5: TOEIC -->
        <div class="col-lg-6 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.6s">
          <div class="program-card">
            <div>
              <div class="program-card-header">
                <div class="program-icon toeic">
                  <i class="fa fa-briefcase"></i>
                </div>
                <span class="program-badge toeic">Skor 10 - 990</span>
              </div>
              <h4>TOEIC Preparation & Test</h4>
              <p class="desc">Standar tes kompetensi bahasa Inggris dunia kerja untuk syarat rekrutmen BUMN, instansi pemerintah, dan perusahaan multinasional.</p>
              
              <div class="subtest-pills">
                <span class="subtest-pill">Listening Section (100 Soal)</span>
                <span class="subtest-pill">Reading Section (100 Soal)</span>
                <span class="subtest-pill">Durasi 120 Menit</span>
              </div>

              <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i> Simulasi ujian 2 jam berstandar dunia kerja</li>
                <li><i class="fa fa-check-circle"></i> Konversi skor resmi skala 10 - 990</li>
                <li><i class="fa fa-check-circle"></i> Dilengkapi <strong>E-Sertifikat Digital</strong> resmi</li>
                <li><i class="fa fa-check-circle"></i> Kosakata & konteks profesional perkantoran</li>
              </ul>
            </div>

            <div class="program-btn-wrap">
              <a href="{{ $register_url }}" class="program-btn program-btn-outline">Pilih Program TOEIC</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ***** Program Belajar Unggulan End ***** -->

  <!-- ***** Keunggulan / Features Start ***** -->
  <div id="features" class="section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 offset-lg-2 text-center">
          <div class="section-heading wow fadeInDown" data-wow-duration="1s" data-wow-delay="0.2s">
            <span class="section-subtitle">EKOSISTEM BELAJAR MODERN</span>
            <h2>Kenapa Memilih <em>One Zero Learning</em>?</h2>
            <p>Bukan sekadar bank soal biasa, kami menyediakan ekosistem belajar komprehensif yang dirancang untuk mempercepat pemahaman materi.</p>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.2s">
          <div class="feature-box">
            <div class="feature-box-icon">
              <i class="fa fa-laptop"></i>
            </div>
            <h4>Simulasi CBT / CAT Mirip Asli</h4>
            <p>Tampilan navigasi soal, tombol ragu-ragu, dan timer countdown dibuat persis seperti aplikasi ujian resmi agar tidak canggung saat hari-H.</p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.3s">
          <div class="feature-box">
            <div class="feature-box-icon">
              <i class="fa fa-play-circle"></i>
            </div>
            <h4>Video Pembahasan Tuntas</h4>
            <p>Setiap butir soal dilengkapi analisis pembahasan langkah-demi-langkah dan trik kilat dari tutor yang dapat ditonton kapan saja.</p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.4s">
          <div class="feature-box">
            <div class="feature-box-icon">
              <i class="fa fa-users"></i>
            </div>
            <h4>Live Class Interaktif</h4>
            <p>Bimbingan tatap muka online bersama mentor berpengalaman untuk bedah kisi-kisi terbaru, strategi pengerjaan, dan sesi tanya jawab.</p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-duration="1s" data-wow-delay="0.5s">
          <div class="feature-box">
            <div class="feature-box-icon">
              <i class="fa fa-line-chart"></i>
            </div>
            <h4>Analisis & Pemetaan Skor</h4>
            <p>Evaluasi grafik performa tiap subtes untuk mendeteksi materi mana yang masih lemah, sehingga waktu belajarmu jauh lebih efektif.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ***** Keunggulan / Features End ***** -->

  <!-- ***** Cara Kerja / About Us Start ***** -->
  <div id="about" class="about-us section">
    <div class="container">
      <div class="row">
        <div class="col-lg-4">
          <div class="left-image wow fadeIn" data-wow-duration="1s" data-wow-delay="0.2s">
            <img src="{{ asset('id/assets/images/about-left-image.png') }}" alt="Alur Belajar Bimbel">
          </div>
        </div>
        <div class="col-lg-8 align-self-center">
          <div class="services">
            <div class="row">
              <div class="col-lg-6">
                <div class="item wow fadeIn" data-wow-duration="1s" data-wow-delay="0.5s">
                  <div class="icon">
                    <img src="{{ asset('id/assets/images/service-icon-01.png') }}" alt="Buat Akun">
                  </div>
                  <div class="right-text">
                    <h4>1. Buat Akun Belajar</h4>
                    <p>Daftar cepat dan praktis dalam 1 menit menggunakan nomor WhatsApp yang aktif.</p>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="item wow fadeIn" data-wow-duration="1s" data-wow-delay="0.7s">
                  <div class="icon">
                    <img src="{{ asset('id/assets/images/service-icon-02.png') }}" alt="Pilih Paket">
                  </div>
                  <div class="right-text">
                    <h4>2. Pilih Program Belajar</h4>
                    <p>Tentukan program CPNS, UTBK, TKA, TOEFL, atau TOEIC yang sesuai dengan target impianmu.</p>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="item wow fadeIn" data-wow-duration="1s" data-wow-delay="0.9s">
                  <div class="icon">
                    <img src="{{ asset('id/assets/images/service-icon-03.png') }}" alt="Mulai Belajar">
                  </div>
                  <div class="right-text">
                    <h4>3. Ikuti Tryout & Kelas</h4>
                    <p>Kerjakan simulasi tryout CBT/CAT, tonton rekaman pembahasan, dan ikuti live class intensif.</p>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="item wow fadeIn" data-wow-duration="1s" data-wow-delay="1.1s">
                  <div class="icon">
                    <img src="{{ asset('id/assets/images/service-icon-04.png') }}" alt="Pantau Progres">
                  </div>
                  <div class="right-text">
                    <h4>4. Pantau & Raih Kelulusan</h4>
                    <p>Lihat grafik perkembangan nilai, perbaiki kelemahan, dan klaim e-sertifikat resmimu.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- ***** Cara Kerja / About Us End ***** -->

  <!-- ***** Portfolio / Sertifikat Start ***** -->
  <div id="portfolio" class="our-portfolio section">
    <div class="container">
      <div class="row">
        <div class="col-lg-8 offset-lg-2 text-center">
          <div class="section-heading wow bounceIn" data-wow-duration="1s" data-wow-delay="0.2s">
            <span class="section-subtitle">DOKUMEN RESMI</span>
            <h2>Dapatkan <em>E-Sertifikat Digital</em> Terverifikasi</h2>
            <p>Untuk program TOEFL dan TOEIC Prediction Test, kamu akan mendapatkan sertifikat digital resmi yang dapat diverifikasi dan siap digunakan untuk kebutuhan sidang, wisuda, maupun berkas lamaran kerja.</p>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-6 col-sm-6">
          <a href="#">
            <div class="item wow bounceInUp" data-wow-duration="1s" data-wow-delay="0.3s">
              <div class="hidden-content">
                <h4>Sertifikat TOEIC Prediction</h4>
                <p>Contoh sertifikat resmi TOEIC Prediction Test dengan skor skala 10-990 untuk syarat rekrutmen BUMN & korporat.</p>
              </div>
              <div class="showed-content">
                <img src="{{ asset('id/assets/images/sertifikat-toeic.jpg') }}" alt="Contoh Sertifikat TOEIC">
              </div>
            </div>
          </a>
        </div>
        <div class="col-lg-6 col-sm-6">
          <a href="#">
            <div class="item wow bounceInUp" data-wow-duration="1s" data-wow-delay="0.4s">
              <div class="hidden-content">
                <h4>Sertifikat TOEFL Prediction</h4>
                <p>Contoh sertifikat resmi TOEFL Prediction Test dengan skor skala 310-677 untuk syarat wisuda & CPNS.</p>
              </div>
              <div class="showed-content">
                <img src="{{ asset('id/assets/images/sertifikat-toefl.jpg') }}" alt="Contoh Sertifikat TOEFL">
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
  <!-- ***** Portfolio / Sertifikat End ***** -->

  <!-- ***** Contact Us Start ***** -->
  <div id="contact" class="contact-us section">
    <div class="container">
      <div class="row">
        <div class="col-lg-6 align-self-center wow fadeInLeft" data-wow-duration="0.5s" data-wow-delay="0.25s">
          <div class="section-heading">
            <span class="section-subtitle">KONSULTASI GRATIS</span>
            <h2>Konsultasikan Rencana Belajarmu Bersama <em>One Zero Learning</em></h2>
            <p>Bingung memilih program yang tepat atau ingin bertanya mengenai jadwal tryout akbar dan voucher promo? Hubungi konsultan belajar kami via WhatsApp!</p>
            <div class="phone-info">
              <h4>Hubungi Konsultan Belajar: <span><i class="fa fa-whatsapp"></i> <a href="https://wa.me/6282279725398" target="_blank">+62 822-7972-5398</a></span></h4>
            </div>
          </div>
        </div>
        <div class="col-lg-6 wow fadeInRight" data-wow-duration="0.5s" data-wow-delay="0.25s">
          <form id="contact" action="" method="post">
            <div class="row">
              <div class="col-lg-6">
                <fieldset>
                  <input type="name" name="name" id="name" placeholder="Nama Depan" autocomplete="on" required>
                </fieldset>
              </div>
              <div class="col-lg-6">
                <fieldset>
                  <input type="surname" name="surname" id="surname" placeholder="Nama Belakang" autocomplete="on" required>
                </fieldset>
              </div>
              <div class="col-lg-12">
                <fieldset>
                  <input type="text" name="email" id="email" pattern="[^ @]*@[^ @]*" placeholder="Email" required="">
                </fieldset>
              </div>
              <div class="col-lg-12">
                <fieldset>
                  <textarea name="message" class="form-control" id="message" placeholder="Tuliskan pertanyaanmu (misal: Mau tanya paket tryout CPNS / UTBK)..." required></textarea>
                </fieldset>
              </div>
              <div class="col-lg-12">
                <fieldset>
                  <button type="submit" id="form-submit" class="main-button"><i class="fa fa-whatsapp"></i> Kirim ke WhatsApp</button>
                </fieldset>
              </div>
            </div>
            <div class="contact-dec">
              <img src="{{ asset('id/assets/images/contact-dec.png') }}" alt="">
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- ***** Contact Us End ***** -->

  <footer>
    <div class="container">
      <div class="row">
        <div class="col-lg-12 wow fadeIn" data-wow-duration="1s" data-wow-delay="0.25s">
          <p>© Copyright 2026 One Zero Learning. All Rights Reserved.
            <br>Platform Bimbel & Tryout Online CPNS, UTBK, TKA, TOEFL & TOEIC.
          </p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="{{ asset('id/vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('id/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('id/assets/js/owl-carousel.js') }}"></script>
  <script src="{{ asset('id/assets/js/animation.js') }}"></script>
  <script src="{{ asset('id/assets/js/imagesloaded.js') }}"></script>
  <script src="{{ asset('id/assets/js/templatemo-custom.js') }}"></script>

  <script type="text/javascript">
    document.getElementById("contact").addEventListener("submit", function(event) {
      event.preventDefault();

      var name = document.getElementById("name").value;
      var surname = document.getElementById("surname").value;
      var email = document.getElementById("email").value;
      var message = document.getElementById("message").value;
      let contact = '+6282279725398';

      var encodedMessage = encodeURIComponent(
        "Halo Admin One Zero Learning,\n" +
        "Saya ingin konsultasi mengenai program belajar:\n\n" +
        "Nama: " + name + " " + surname + "\n" +
        "Email: " + email + "\n" +
        "Pertanyaan: " + message
      );

      var link;
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        link = `whatsapp://send?phone=${contact}&text=${encodedMessage}`;
      } else {
        link = `https://api.whatsapp.com/send?phone=${contact}&text=${encodedMessage}`;
      }

      window.open(link, '_blank');
    });
  </script>

</body>

</html>
