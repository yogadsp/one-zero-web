<template>
    <Head><title>Beranda Belajar</title></Head>
    <div class="page-wrapper learning-page">
        <div class="page-content">
            <LearningHero />

            <section class="learning-stats" aria-label="Ringkasan aktivitas">
                <article><span class="learning-stat-icon is-blue"><i class="bx bx-edit-alt"></i></span><div><strong>{{ totalExamToday }}</strong><span>Tryout hari ini</span></div></article>
                <article><span class="learning-stat-icon is-purple"><i class="bx bx-package"></i></span><div><strong>{{ ownedPackageCount }}</strong><span>Produk belajar aktif</span></div></article>
                <article><span class="learning-stat-icon is-green"><i class="bx bx-transfer"></i></span><div><strong>{{ totalTransactionThisMonth }}</strong><span>Transaksi bulan ini</span></div></article>
            </section>

            <section class="learning-section">
                <div class="learning-section__heading">
                    <div><span class="learning-eyebrow learning-eyebrow--dark">PILIHAN UNTUKMU</span><h2>Rekomendasi Produk Belajar</h2><p>Tryout, video rekaman, dan kelas online dapat dipilih serta dibeli secara terpisah.</p></div>
                    <Link href="/user/learning" class="learning-text-link">Lihat semua <i class="bx bx-right-arrow-alt"></i></Link>
                </div>
                <div v-if="recommendedPackages.length" class="learning-package-grid">
                    <LearningPackageCard v-for="item in recommendedPackages" :key="item.id" :learning-package="item" />
                </div>
                <div v-else class="learning-empty"><i class="bx bx-book-open"></i><h3>Katalog sedang disiapkan</h3><p>Admin dapat menerbitkan paket dari data ujian yang sudah ada.</p></div>
            </section>

            <section class="learning-dashboard-grid">
                <article class="learning-panel">
                    <div class="learning-panel__heading"><div><span class="learning-eyebrow learning-eyebrow--dark">PROGRES</span><h2>Aktivitas Tryout</h2></div><Link href="/user/grades">Riwayat</Link></div>
                    <div v-if="gradeSummaries.length" class="learning-activity-list">
                        <div v-for="grade in gradeSummaries" :key="grade.id" class="learning-activity-item">
                            <span class="learning-activity-item__icon"><i class="bx bx-check-circle"></i></span>
                            <div><strong>{{ grade.exam.title }}</strong><span>{{ grade.category.name }} · {{ formatDate(grade.start_time) }}</span></div>
                        </div>
                    </div>
                    <div v-else class="learning-panel-empty">Belum ada tryout yang diselesaikan.</div>
                </article>

                <article class="learning-panel">
                    <div class="learning-panel__heading"><div><span class="learning-eyebrow learning-eyebrow--dark">PEMBELIAN</span><h2>Transaksi Terakhir</h2></div><Link href="/user/transactions">Semua</Link></div>
                    <div v-if="transactionSummaries.length" class="learning-activity-list">
                        <Link v-for="transaction in transactionSummaries" :key="transaction.id" :href="`/user/transactions/${transaction.id}`" class="learning-activity-item">
                            <span class="learning-activity-item__icon is-orange"><i class="bx bx-receipt"></i></span>
                            <div><strong>{{ transaction.product_title }}</strong><span>{{ transaction.code }} · {{ transaction.created_at }}</span></div>
                            <span class="learning-status" :class="`is-${transaction.transaction_status}`">{{ statusLabel(transaction.transaction_status) }}</span>
                        </Link>
                    </div>
                    <div v-else class="learning-panel-empty">Belum ada transaksi.</div>
                </article>
            </section>

            <section v-if="announcementSummaries.length" class="learning-panel learning-announcements">
                <div class="learning-panel__heading"><div><span class="learning-eyebrow learning-eyebrow--dark">INFORMASI</span><h2>Pengumuman</h2></div><Link href="/user/announcements">Semua</Link></div>
                <Link v-for="announcement in announcementSummaries" :key="announcement.id" :href="`/user/announcements/${announcement.id}`" class="learning-announcement-item"><i class="bx bx-bell"></i><span>{{ announcement.title }}</span><i class="bx bx-chevron-right"></i></Link>
            </section>
        </div>
    </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3';
import LayoutUser from '../../../Layouts/Layout.vue';
import LearningHero from '../../../Components/Learning/LearningHero.vue';
import LearningPackageCard from '../../../Components/Learning/LearningPackageCard.vue';

export default {
    layout: LayoutUser,
    components: { Head, Link, LearningHero, LearningPackageCard },
    props: {
        totalExamToday: Number,
        transactionSummaries: Array,
        gradeSummaries: Array,
        announcementSummaries: Array,
        totalTransactionThisMonth: Number,
        recommendedPackages: Array,
        ownedPackageCount: Number,
    },
    methods: {
        formatDate(value) { return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value)) : '-'; },
        statusLabel(status) { return { pending: 'Menunggu', paid: 'Dibayar', failed: 'Gagal', done: 'Selesai' }[status] || status; },
    },
};
</script>
