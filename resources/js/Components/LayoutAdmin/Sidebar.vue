<template>
    <aside class="sidebar-wrapper" data-simplebar="true">
        <div class="sidebar-header learning-sidebar-brand">
            <div class="learning-sidebar-brand__mark">10</div>
            <div><strong>ONE ZERO</strong><small>LEARNING</small></div>
            <button type="button" class="toggle-icon ms-auto" aria-label="Tutup menu"><i class="bx bx-arrow-to-left"></i></button>
        </div>

        <ul v-if="isAdmin" id="menu" class="metismenu">
            <li><Link href="/admin/dashboard"><div class="parent-icon"><i class="bx bx-home-circle"></i></div><div class="menu-title">Dashboard</div></Link></li>
            <li class="menu-label">Manajemen</li>
            <li>
                <a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="bx bx-grid-alt"></i></div><div class="menu-title">Master Data</div></a>
                <ul>
                    <li><Link href="/admin/categories"><i class="bx bx-right-arrow-alt"></i>Kategori / Program</Link></li>
                    <li><Link href="/admin/users"><i class="bx bx-right-arrow-alt"></i>User</Link></li>
                </ul>
            </li>
            <li>
                <a href="javascript:;" class="has-arrow"><div class="parent-icon"><i class="bx bx-folder"></i></div><div class="menu-title">Bank Soal</div></a>
                <ul>
                    <li><Link href="/admin/value-categories"><i class="bx bx-right-arrow-alt"></i>Kategori Penilaian</Link></li>
                    <li><Link href="/admin/question-titles"><i class="bx bx-right-arrow-alt"></i>Soal</Link></li>
                </ul>
            </li>
            <li><Link href="/admin/exams"><div class="parent-icon"><i class="bx bx-edit-alt"></i></div><div class="menu-title">Paket Tryout</div></Link></li>
            <li><Link href="/admin/learning-materials"><div class="parent-icon"><i class="bx bx-video"></i></div><div class="menu-title">Materi Belajar</div></Link></li>
            <li class="menu-label">Transaksi</li>
            <li><Link href="/admin/banks"><div class="parent-icon"><i class="bx bx-credit-card-front"></i></div><div class="menu-title">Bank</div></Link></li>
            <li><Link href="/admin/transactions"><div class="parent-icon"><i class="bx bx-transfer"></i></div><div class="menu-title">Transaksi</div></Link></li>
        </ul>

        <ul v-else id="menu" class="metismenu learning-user-menu">
            <li class="menu-label">Belajar</li>
            <li><Link href="/user/dashboard"><div class="parent-icon"><i class="bx bx-home-alt"></i></div><div class="menu-title">Beranda</div></Link></li>
            <li><Link href="/user/learning"><div class="parent-icon"><i class="bx bx-book-open"></i></div><div class="menu-title">Mulai Belajar</div></Link></li>
            <li><Link href="/user/my-learning-packages"><div class="parent-icon"><i class="bx bx-package"></i></div><div class="menu-title">Paket Belajar Saya</div></Link></li>

            <li class="menu-label">Aktivitas</li>
            <li><Link href="/user/grades"><div class="parent-icon"><i class="bx bx-line-chart"></i></div><div class="menu-title">Riwayat Tryout</div></Link></li>
            <li><Link href="/user/announcements"><div class="parent-icon"><i class="bx bx-bell"></i></div><div class="menu-title">Pengumuman</div></Link></li>

            <li class="menu-label">Pembelian</li>
            <li><Link href="/user/transactions"><div class="parent-icon"><i class="bx bx-receipt"></i></div><div class="menu-title">Riwayat Transaksi</div></Link></li>
            <li><Link href="/user/voucher-activations"><div class="parent-icon"><i class="bx bx-purchase-tag"></i></div><div class="menu-title">Aktivasi Voucher</div></Link></li>

            <li class="menu-label">Akun</li>
            <li><Link href="/user/faqs"><div class="parent-icon"><i class="bx bx-help-circle"></i></div><div class="menu-title">FAQ</div></Link></li>
            <li><Link href="/user/users"><div class="parent-icon"><i class="bx bx-user"></i></div><div class="menu-title">Profil</div></Link></li>
        </ul>
    </aside>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3';

export default {
    components: { Link },
    computed: {
        isAdmin() { return Number(this.$page.props.auth.user.level) === 1; },
    },
};
</script>
