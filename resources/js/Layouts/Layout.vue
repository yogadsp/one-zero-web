<template>
    <div class="wrapper" :class="{ toggled : isToggleActive }">
        <!-- sidebar -->
        <Sidebar/>
        <!-- header -->
        <Header/>
        <!-- content -->
        <slot />

        <Footer/>
    </div>
</template>

<script>
    import { Link } from '@inertiajs/inertia-vue3';

    // import navbar
    import Sidebar from '../Components/LayoutAdmin/Sidebar.vue';
    // import header
    import Header from '../Components/LayoutAdmin/Header.vue';
    // import footer
    import Footer from '../Components/LayoutAdmin/Footer.vue';

    export default {
        // register components
        components: {
            Link,
            Sidebar,
            Header,
            Footer
        },
        data() {
            return {
                isToggleActive: false
            };
        },
        mounted() {
            $("#menu").metisMenu();

            $(".mobile-toggle-menu").on("click.layoutAdmin", () => {
                this.isToggleActive = true;
                $(".wrapper").addClass("toggled");
            });

            $(".menu-clicked").on("click.layoutAdmin", () => {
                this.isToggleActive = false;
                $(".wrapper").removeClass("toggled");
            });

            $(".toggle-icon").on("click.layoutAdmin", () => {
                this.isToggleActive = !this.isToggleActive;
                $(".wrapper").toggleClass("toggled", this.isToggleActive);

                if (this.isToggleActive) {
                    $(".sidebar-wrapper").on("mouseenter.layoutAdmin", () => {
                        $(".wrapper").addClass("sidebar-hovered");
                    }).on("mouseleave.layoutAdmin", () => {
                        $(".wrapper").removeClass("sidebar-hovered");
                    });
                } else {
                    $(".sidebar-wrapper").off(".layoutAdmin");
                    $(".wrapper").removeClass("sidebar-hovered");
                }
            });
        },
        beforeUnmount() {
            $(".mobile-toggle-menu, .menu-clicked, .toggle-icon").off(".layoutAdmin");
            $(".sidebar-wrapper").off(".layoutAdmin");
        }
    }
</script>
