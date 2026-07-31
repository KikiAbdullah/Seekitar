@once
  <style>
    /* ============ Umum ============ */
    .eyebrow {
      letter-spacing: .18em;
      text-transform: uppercase;
      font-size: .8125rem;
      font-weight: 700;
      color: var(--bs-primary);
    }
    .eyebrow::before {
      content: "";
      display: inline-block;
      width: 30px;
      height: 2px;
      border-radius: 2px;
      background: var(--bs-primary);
      vertical-align: middle;
      margin-right: .65rem;
    }
    .icon-soft {
      width: 46px;
      height: 46px;
      border-radius: 1rem;
      background: var(--bs-primary-bg-subtle);
      color: var(--bs-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }
    .card-lift {
      transition: transform .25s ease, box-shadow .25s ease;
    }
    .card-lift:hover {
      transform: translateY(-6px);
      box-shadow: var(--bs-box-shadow);
    }
    .check-item {
      display: flex;
      gap: .75rem;
      align-items: flex-start;
    }
    .check-item i {
      color: var(--bs-primary);
      font-size: 1.15rem;
      line-height: 1.5;
    }
    .stat-num {
      font-size: calc(1.8rem + .8vw);
      font-weight: 800;
      color: #1f2933;
      line-height: 1;
    }
    .feature-icon {
      width: 60px;
      height: 60px;
      border-radius: 1.1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      flex-shrink: 0;
    }

    /* ============ NAVBAR TRANSPARAN DI POSISI PALING ATAS ============
       Navbar mengambang di atas hero: transparan saat di atas, putih
       dengan bayangan setelah discroll (kelas .fixed-header dipicu
       custom.js pada scroll >= 60px). Hero diberi padding-top sendiri
       agar kontennya tidak tertutup navbar 80px. */
    .header {
      position: fixed;
      background-color: transparent;
    }
    .header.fixed-header {
      background-color: #fff;
    }

    /* ============ HERO FULL (beranda & katalog) ============ */
    .hero-wrap {
      background: linear-gradient(180deg, #e9faf1 0%, rgba(233, 250, 241, 0) 100%);
    }
    .hero-wrap::before {
      content: "";
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: .55;
      pointer-events: none;
      width: 420px;
      height: 420px;
      top: -120px;
      right: -80px;
      background: radial-gradient(circle, #c9f2dd, transparent 70%);
    }
    .hero-figure {
      position: relative;
      z-index: 1;
    }
    .hero-figure .hero-img {
      border-radius: 2rem;
      box-shadow: 0 34px 70px -22px rgba(22, 138, 74, .35);
      width: 100%;
      aspect-ratio: 3 / 2;
      object-fit: cover;
    }
    .hero-badge {
      position: absolute;
      display: flex;
      align-items: center;
      gap: .75rem;
      background: #fff;
      border-radius: 1rem;
      padding: .85rem 1.1rem;
      box-shadow: 0 18px 40px -18px rgba(31, 41, 51, .35);
      z-index: 2;
      white-space: nowrap;
    }
    .hero-badge .badge-icon {
      width: 42px;
      height: 42px;
      border-radius: .85rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }
    .hero-badge .num {
      font-weight: 800;
      color: #1f2933;
      line-height: 1.1;
    }
    .hero-badge .lbl {
      font-size: .75rem;
      color: var(--bs-secondary-color);
      line-height: 1.2;
    }
    .hero-badge.badge-top {
      top: -1.25rem;
      left: 1rem;
    }
    .hero-badge.badge-bottom {
      bottom: -1.25rem;
      right: 1rem;
    }

    /* Badge hero tidak boleh keluar bingkai gambar di layar sempit:
       dari melayang keluar (offset negatif) jadi duduk di dalam gambar. */
    @media (max-width: 767.98px) {
      .hero-badge {
        padding: .6rem .8rem;
        gap: .5rem;
        border-radius: .85rem;
      }
      .hero-badge .badge-icon {
        width: 34px;
        height: 34px;
        font-size: 1.05rem;
      }
      .hero-badge .num {
        font-size: .9rem;
      }
      .hero-badge .lbl {
        font-size: .65rem;
      }
      .hero-figure .hero-img {
        border-radius: 1.5rem;
      }
    }
    @media (max-width: 575.98px) {
      .hero-badge.badge-top {
        top: .75rem;
        left: .75rem;
      }
      .hero-badge.badge-bottom {
        bottom: .75rem;
        right: .75rem;
      }
    }

    /* ============ HERO MINI (halaman statis) ============ */
    .page-hero {
      background: linear-gradient(180deg, #e9faf1 0%, rgba(233, 250, 241, 0) 100%);
      position: relative;
      overflow: hidden;
    }
    .page-hero::before {
      content: "";
      position: absolute;
      width: 420px;
      height: 420px;
      top: -160px;
      right: -80px;
      border-radius: 50%;
      background: radial-gradient(circle, #c9f2dd, transparent 70%);
      filter: blur(80px);
      opacity: .55;
      pointer-events: none;
    }
    .page-hero .container {
      position: relative;
      z-index: 1;
    }
    .page-hero img.hero-img {
      border-radius: 1.5rem;
      box-shadow: 0 30px 60px -22px rgba(22, 138, 74, .35);
      width: 100%;
      aspect-ratio: 3 / 2;
      object-fit: cover;
    }

    /* ============ DOKUMEN LEGAL ============ */
    .doc-toc {
      position: sticky;
      top: 1.5rem;
    }
    .doc-toc .toc-link {
      display: flex;
      align-items: center;
      gap: .6rem;
      padding: .45rem .9rem;
      border-radius: .75rem;
      color: var(--bs-secondary-color);
      text-decoration: none;
      font-size: .9rem;
      line-height: 1.35;
    }
    .doc-toc .toc-link:hover {
      background: var(--bs-primary-bg-subtle);
      color: var(--bs-primary);
    }
    .doc-toc .toc-link i {
      font-size: 1.05rem;
      color: var(--bs-primary);
      flex-shrink: 0;
    }
    .doc-content {
      font-size: 1rem;
      line-height: 1.75;
    }
    .doc-content h2 {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 2.5rem 0 1rem;
      scroll-margin-top: 1.5rem;
    }
    .doc-content h2:first-child {
      margin-top: 0;
    }
    .doc-content h3 {
      font-size: 1.15rem;
      font-weight: 700;
      margin: 1.75rem 0 .75rem;
    }
    .doc-content p {
      margin-bottom: 1rem;
    }
    .doc-content ul,
    .doc-content ol {
      margin-bottom: 1rem;
      padding-left: 1.25rem;
    }
    .doc-content li {
      margin-bottom: .4rem;
    }
    .doc-content table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.5rem;
      font-size: .95rem;
    }
    .doc-content th,
    .doc-content td {
      border: 1px solid var(--bs-border-color);
      padding: .7rem 1rem;
      text-align: left;
      vertical-align: top;
    }
    .doc-content th {
      background: var(--bs-primary-bg-subtle);
      font-weight: 700;
    }
    .doc-content .kicker {
      letter-spacing: .18em;
      text-transform: uppercase;
      font-size: .75rem;
      font-weight: 700;
      color: var(--bs-primary);
    }

    /* ============ AKORDION FAQ ============ */
    .accordion-item {
      border: 0;
      box-shadow: var(--bs-box-shadow-sm);
      border-radius: 1rem !important;
      overflow: hidden;
    }
    .accordion-button:not(.collapsed) {
      background: var(--bs-primary-bg-subtle);
      color: var(--bs-primary);
      font-weight: 600;
      box-shadow: none;
    }
    .accordion-button:focus {
      box-shadow: none;
    }
  </style>
@endonce
