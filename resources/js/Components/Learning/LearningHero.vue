<template>
    <section class="learning-hero">
        <div class="learning-hero__content">
            <span class="learning-eyebrow">ONE ZERO LEARNING</span>
            <h1>Satu tempat belajar.<br><span>Banyak tujuan.</span></h1>
            <p>Persiapan TOEFL, UTBK, dan CPNS dengan tryout, rekaman pembahasan, serta kelas online dalam satu akun.</p>
            <div class="learning-hero__actions">
                <Link href="/user/learning" class="learning-button learning-button--light">Mulai Belajar</Link>
                <Link href="/user/my-learning-packages" class="learning-button learning-button--ghost">Paket Saya</Link>
            </div>
        </div>
        <div class="learning-hero__art" aria-hidden="true">
            <div class="learning-hero__score">10</div>
            <div class="learning-hero__orbit learning-hero__orbit--one"></div>
            <div class="learning-hero__orbit learning-hero__orbit--two"></div>
            <div class="learning-hero__mini-card">
                <i class="bx bx-line-chart"></i>
                <span>Belajar terarah</span>
            </div>
        </div>
    </section>
</template>

<script>
import { Link } from '@inertiajs/inertia-vue3';

export default { components: { Link } };
</script>
