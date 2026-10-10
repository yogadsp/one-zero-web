<template>
    <Head>
        <title>{{ appName }} - Data Transaksi</title>
    </Head>
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Transaksi</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Data Transaksi</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div>
                            <h6 class="mb-3">Riwayat Transaksi</h6>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Kode Transaksi</th>
                                    <th>Kategori</th>
                                    <th>Judul</th>
                                    <th>Batas Pembayaran</th>
                                    <th>Pembayaran</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!transactions.data.length">
                                    <td colspan="8" align="center">Belum ada transaksi</td>
                                </tr>
                                <tr v-for="(transaction, index) in transactions.data" :key="index">
                                    <td>{{ ++index + (transactions.current_page - 1) * transactions.per_page }}</td>
                                    <td>{{ transaction.code }}</td>
                                    <td>{{ transaction.product_category_name }}</td>
                                    <td>{{ transaction.product_title }}</td>
                                    <td>{{ transaction.maximum_payment_time }}</td>
                                    <td>{{ formatPrice(transaction.total_purchases) }}</td>
                                    <td>
                                        <span class="badge bg-warning" v-if="transaction.transaction_status == 'pending'">Menunggu Pembayaran</span>
                                        <span class="badge bg-primary" v-if="transaction.transaction_status == 'paid'">Pembayaran Diterima</span>
                                        <span class="badge bg-danger" v-if="transaction.transaction_status == 'failed'">Transakasi Gagal</span>
                                        <span class="badge bg-success" v-if="transaction.transaction_status == 'done'">Transaksi Selesai</span>
                                    </td>
                                    <td>
                                        <div class="d-flex order-actions">
                                            <Link :href="`/user/transactions/${transaction.id}`"><i class='bx bx-grid-alt'></i></Link>
                                            <button
                                                v-if="transaction.can_be_deleted"
                                                type="button"
                                                class="transaction-action transaction-action--danger"
                                                :disabled="deletingId === transaction.id"
                                                :aria-label="`Batalkan transaksi ${transaction.code}`"
                                                title="Batalkan transaksi"
                                                @click="deleteTransaction(transaction)"
                                            >
                                                <i :class="deletingId === transaction.id ? 'bx bx-loader-alt bx-spin' : 'bx bx-trash'"></i>
                                            </button>
                                            <span v-else class="transaction-action transaction-action--locked" title="Transaksi yang sudah dibayar atau selesai tidak dapat dibatalkan" aria-label="Transaksi dikunci">
                                                <i class="bx bx-lock-alt"></i>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <Pagination :links="transactions.links" align="end" />

                </div>
            </div>
        </div>
    </div>
    <!--end page wrapper -->
</template>

<script>
    //import layout user
    import LayoutUser from '../../../../Layouts/Layout.vue';

    //import component pagination
    import Pagination from '../../../../Components/Pagination.vue';

    // import Link
    import { Link } from '@inertiajs/inertia-vue3';

    //import sweet alert2
    import Swal from 'sweetalert2';

    // import Head from Inertia
    import {
        Head
    } from '@inertiajs/inertia-vue3';

    import { Inertia } from '@inertiajs/inertia';

    export default {
        // layout
        layout: LayoutUser,

        // register components
        components: {
            Link,
            Head,
            Pagination
        },

        // props
        props: {
            transactions: Object
        },
        data() {
            return {
                deletingId: null
            }
        },
        methods: {
            formatPrice(value) {
                let val = (value/1).toFixed(2).replace('.', ',')
                return 'Rp.' + val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
            },
            deleteTransaction(transaction) {
                Swal.fire({
                    title: 'Batalkan transaksi?',
                    text: `${transaction.code} akan dihapus dari riwayat aktif. Anda dapat membeli produk ini lagi.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, batalkan',
                    cancelButtonText: 'Kembali',
                    confirmButtonColor: '#d92d20',
                    reverseButtons: true
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    Inertia.delete(`/user/transactions/${transaction.id}`, {
                        preserveScroll: true,
                        onStart: () => { this.deletingId = transaction.id },
                        onFinish: () => { this.deletingId = null }
                    });
                });
            }
        }
    }
</script>

<style scoped>
.transaction-action { display:inline-grid; place-items:center; width:34px; height:34px; margin-left:8px; border:0; border-radius:9px; font-size:1.05rem; transition:background-color .18s ease,color .18s ease,transform .18s ease; }
.transaction-action--danger { color:#b42318; background:#fff1f0; cursor:pointer; }
.transaction-action--danger:hover:not(:disabled),.transaction-action--danger:focus-visible { color:#fff; background:#d92d20; transform:translateY(-1px); outline:2px solid rgba(217,45,32,.22); outline-offset:2px; }
.transaction-action--danger:disabled { cursor:wait; opacity:.55; }
.transaction-action--locked { color:#98a2b3; background:#f2f4f7; cursor:not-allowed; }
@media (prefers-reduced-motion:reduce) { .transaction-action { transition:none; } }
</style>
