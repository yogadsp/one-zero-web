<template>
    <article class="learning-package-card">
        <div class="learning-package-card__visual" :class="visualClass">
            <span class="learning-package-card__program">{{ learningPackage.category?.name || 'PROGRAM' }}</span>
            <span class="learning-package-card__mark" aria-hidden="true">10</span>
            <div>
                <small>{{ productLabel }}</small>
                <strong>{{ learningPackage.title }}</strong>
            </div>
        </div>

        <div class="learning-package-card__body">
            <div class="learning-package-card__contents" :aria-label="productLabel">
                <span><i :class="productIcon"></i> {{ productLabel }}</span>
                <span v-if="learningPackage.starts_at"><i class="bx bx-calendar"></i> {{ formatDate(learningPackage.starts_at) }}</span>
            </div>
            <h3>{{ learningPackage.title }}</h3>
            <p>{{ plainDescription }}</p>

            <div class="learning-package-card__footer">
                <div>
                    <small>{{ learningPackage.has_access ? 'Sudah dimiliki' : 'Harga paket' }}</small>
                    <strong>{{ learningPackage.has_access ? 'Siap dipelajari' : formatPrice(learningPackage.price) }}</strong>
                </div>
                <Link
                    v-if="learningPackage.has_access"
                    :href="detailUrl"
                    class="learning-button learning-button--primary"
                >Buka Paket</Link>
                <Link
                    v-else
                    :href="detailUrl"
                    class="learning-button learning-button--primary"
                >Lihat Paket</Link>
            </div>
        </div>
    </article>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3';

export default {
    components: { Link },
    props: {
        learningPackage: {
            type: Object,
            required: true,
        },
    },
    computed: {
        productType() {
            return this.learningPackage.product_type || 'tryout';
        },
        productLabel() {
            return { tryout: 'TRYOUT', video: 'VIDEO REKAMAN', live_class: 'KELAS ONLINE' }[this.productType] || 'PRODUK BELAJAR';
        },
        productIcon() {
            return { tryout: 'bx bx-edit-alt', video: 'bx bx-play-circle', live_class: 'bx bx-video' }[this.productType] || 'bx bx-book';
        },
        detailUrl() {
            return `/user/learning/${this.productType}/${this.learningPackage.id}`;
        },
        plainDescription() {
            const text = String(this.learningPackage.description || '')
                .replace(/<[^>]*>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
            return text || 'Produk belajar terarah untuk membantu persiapanmu.';
        },
        visualClass() {
            const name = String(this.learningPackage.category?.name || '').toLowerCase();
            if (name.includes('utbk')) return 'is-utbk';
            if (name.includes('cpns') || name.includes('skd')) return 'is-cpns';
            return 'is-language';
        },
    },
    methods: {
        formatPrice(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            }).format(Number(value || 0));
        },
        formatDate(value) {
            return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
        },
    },
};
</script>
