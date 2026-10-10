<template>
    <Head><title>{{ product.title }}</title></Head>
    <div class="page-wrapper learning-page">
        <div class="page-content">
            <Link href="/user/learning" class="learning-back"><i class="bx bx-left-arrow-alt"></i> Kembali ke katalog</Link>

            <div v-if="$page.props.session.error" class="alert alert-danger border-0">{{ $page.props.session.error }}</div>
            <div v-if="$page.props.session.success" class="alert alert-success border-0">{{ $page.props.session.success }}</div>

            <section class="learning-detail-hero">
                <div>
                    <span class="learning-eyebrow">{{ product.category?.name }}</span>
                    <h1>{{ product.title }}</h1>
                    <p>{{ plainDescription }}</p>
                    <div class="learning-detail-hero__meta">
                        <span><i :class="productIcon"></i> {{ productLabel }}</span>
                        <span v-if="product.starts_at"><i class="bx bx-calendar"></i> {{ formatDate(product.starts_at) }}</span>
                    </div>
                </div>
                <aside class="learning-purchase-box">
                    <span>{{ product.has_access ? 'Status akses' : 'Harga' }}</span>
                    <strong>{{ product.has_access ? 'Sudah dimiliki' : formatPrice(product.price) }}</strong>
                    <template v-if="product.has_access">
                        <Link v-if="isTryout" :href="`/user/categories/${product.category_id}/exams/${product.id}`" class="learning-button learning-button--light">Buka Tryout</Link>
                        <button v-else-if="isVideo" type="button" class="learning-button learning-button--light" @click="showVideo">Tonton Rekaman</button>
                        <a v-else :href="`/user/learning-materials/${product.id}/open`" class="learning-button learning-button--light">Buka Tautan Zoom</a>
                    </template>
                    <button v-else type="button" class="learning-button learning-button--light" :disabled="processing" @click="buyProduct">{{ processing ? 'Memproses…' : `Beli ${productLabel}` }}</button>
                    <small>Pembayaran dan aktivasi voucher memakai alur transaksi yang sudah tersedia.</small>
                </aside>
            </section>

            <section v-if="isVideo && activeVideo" id="video-player" class="learning-section learning-video-player">
                <div class="learning-section__heading">
                    <div><h2>Video Rekaman</h2><p>Video diputar langsung di halaman ini.</p></div>
                    <button type="button" class="learning-button learning-button--secondary" @click="activeVideo = false">Tutup</button>
                </div>
                <div class="learning-video-frame">
                    <iframe
                        :src="videoUrl"
                        :title="`Video rekaman ${product.title}`"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    ></iframe>
                </div>
            </section>

            <section class="learning-section learning-section--compact">
                <div class="learning-section__heading">
                    <div><h2>{{ isTryout ? 'Akses Tryout' : productLabel }}</h2><p>{{ accessDescription }}</p></div>
                </div>
                <div class="learning-content-list">
                    <article class="learning-content-item">
                        <span class="learning-content-item__icon"><i :class="productIcon"></i></span>
                        <div>
                            <small>{{ productLabel }}</small>
                            <h3>{{ product.title }}</h3>
                            <p>{{ plainDescription }}</p>
                            <time v-if="product.starts_at"><i class="bx bx-calendar"></i> {{ formatDate(product.starts_at) }}</time>
                        </div>
                        <template v-if="product.has_access">
                            <Link v-if="isTryout" :href="`/user/categories/${product.category_id}/exams/${product.id}`" class="learning-button learning-button--secondary">Mulai</Link>
                            <button v-else-if="isVideo" type="button" class="learning-button learning-button--secondary" @click="showVideo">Tonton</button>
                            <a v-else :href="`/user/learning-materials/${product.id}/open`" class="learning-button learning-button--secondary">Gabung Kelas</a>
                        </template>
                        <span v-else class="learning-lock"><i class="bx bx-lock-alt"></i> Perlu pembelian</span>
                    </article>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import { Head, Link } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import LayoutUser from '../../../Layouts/Layout.vue';

export default {
    layout: LayoutUser,
    components: { Head, Link },
    props: { product: Object },
    data() { return { processing: false, activeVideo: false }; },
    computed: {
        productType() { return this.product.product_type || 'tryout'; },
        isTryout() { return this.productType === 'tryout'; },
        isVideo() { return this.productType === 'video'; },
        videoUrl() {
            return `/user/learning-materials/${this.product.id}/embed`;
        },
        productLabel() { return { tryout: 'Tryout', video: 'Video Rekaman', live_class: 'Kelas Online' }[this.productType] || 'Produk Belajar'; },
        productIcon() { return { tryout: 'bx bx-edit-alt', video: 'bx bx-play-circle', live_class: 'bx bx-video' }[this.productType] || 'bx bx-book'; },
        plainDescription() {
            return String(this.product.description || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'Informasi produk belajar.';
        },
        accessDescription() {
            if (this.isTryout) return 'Kerjakan simulasi ujian setelah voucher aktif.';
            if (this.isVideo) return 'Tonton rekaman melalui tautan yang hanya dibuka setelah pemeriksaan hak akses.';
            return 'Nama kelas, jadwal, dan tautan Zoom tersedia setelah voucher aktif.';
        },
        buyUrl() {
            return this.isTryout
                ? `/user/transactions/exams/${this.product.id}/buy`
                : `/user/transactions/learning-materials/${this.product.id}/buy`;
        },
    },
    methods: {
        buyProduct() {
            this.processing = true;
            Inertia.post(this.buyUrl, {}, { onFinish: () => { this.processing = false; } });
        },
        showVideo() {
            this.activeVideo = true;
            this.$nextTick(() => document.getElementById('video-player')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },
        formatPrice(value) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
        },
        formatDate(value) {
            return new Intl.DateTimeFormat('id-ID', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(value));
        },
    },
};
</script>
