<template>
    <Head><title>Mulai Belajar</title></Head>
    <div class="page-wrapper learning-page">
        <div class="page-content">
            <LearningHero />

            <section class="learning-section">
                <div class="learning-section__heading">
                    <div>
                        <span class="learning-eyebrow learning-eyebrow--dark">KATALOG BELAJAR</span>
                        <h2>Mulai Belajar</h2>
                        <p>Pilih bentuk belajar dan program yang sesuai dengan targetmu.</p>
                    </div>
                </div>

                <div class="learning-toolbar">
                    <div class="learning-tabs" role="tablist" aria-label="Jenis materi">
                        <button v-for="tab in tabs" :key="tab.value" type="button" :class="{ active: form.type === tab.value }" @click="selectType(tab.value)">
                            <i :class="tab.icon"></i>{{ tab.label }}
                        </button>
                    </div>
                    <form class="learning-search" @submit.prevent="applyFilters">
                        <select v-model="form.category" aria-label="Filter program">
                            <option value="">Semua program</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        <label>
                            <i class="bx bx-search"></i>
                            <input v-model="form.search" type="search" maxlength="120" placeholder="Cari produk belajar...">
                        </label>
                        <button type="submit" class="learning-button learning-button--primary">Cari</button>
                    </form>
                </div>

                <div v-if="packages.data.length" class="learning-package-grid">
                    <LearningPackageCard v-for="item in packages.data" :key="item.id" :learning-package="item" />
                </div>
                <div v-else class="learning-empty">
                    <i class="bx bx-book-open"></i>
                    <h3>Belum ada produk pada pilihan ini</h3>
                    <p>Coba pilih jenis materi atau program lain.</p>
                </div>

                <Pagination :links="packages.links" align="end" />
            </section>
        </div>
    </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import LayoutUser from '../../../Layouts/Layout.vue';
import LearningHero from '../../../Components/Learning/LearningHero.vue';
import LearningPackageCard from '../../../Components/Learning/LearningPackageCard.vue';
import Pagination from '../../../Components/Pagination.vue';

export default {
    layout: LayoutUser,
    components: { Head, LearningHero, LearningPackageCard, Pagination },
    props: {
        packages: Object,
        categories: Array,
        filters: Object,
    },
    data() {
        return {
            tabs: [
                { value: 'tryout', label: 'Tryout', icon: 'bx bx-edit-alt' },
                { value: 'video', label: 'Video Rekaman', icon: 'bx bx-play-circle' },
                { value: 'live_class', label: 'Kelas Online', icon: 'bx bx-video' },
            ],
            form: {
                search: this.filters?.search || '',
                type: this.filters?.type || 'tryout',
                category: this.filters?.category || '',
            },
        };
    },
    methods: {
        selectType(type) {
            this.form.type = type;
            this.applyFilters();
        },
        applyFilters() {
            Inertia.get('/user/learning', this.form, { preserveState: true });
        },
    },
};
</script>
