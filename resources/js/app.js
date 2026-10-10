import jquery from "jquery";
window.jQuery = window.jquery = jquery;
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { InertiaProgress } from "@inertiajs/progress";
import moment from "moment";
import VueApexCharts from "vue3-apexcharts";

// GET navigations use a browser reload so every menu/page link renders the
// response returned by Laravel. This avoids the stale persistent-layout DOM
// produced by the current legacy Inertia/Vue adapter in this application.
const inertiaVisit = Inertia.visit.bind(Inertia);
Inertia.visit = (url, options = {}) => {
  const method = (options.method || "get").toLowerCase();

  if (method === "get") {
    const target = new URL(url, window.location.href);
    const data = options.data || {};

    Object.entries(data).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== "") {
        target.searchParams.set(key, value);
      }
    });

    window.location.assign(target.toString());
    return;
  }

  return inertiaVisit(url, options);
};

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob("./Pages/**/*.vue")),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      //set mixins
      .mixin({
        methods: {
          examTimeRangeChecker: function (start_time, end_time) {
            return new Date() >= new Date(start_time) && new Date() <= new Date(end_time);
          },

          examTimeStartChecker: function (start_time) {
            return new Date() < new Date(start_time);
          },

          examTimeEndChecker: function (end_time) {
            return new Date() > new Date(end_time);
          },
          formatDateWithTime: function (date) {
            return moment(date).format("DD MMMM YYYY H:mm:ss");
          },
          formatDate: function (date) {
            return moment(date).format("DD MMMM YYYY");
          },
        },
        data() {
          return {
            appName: "One Zero Learning",
            wa: "+6282279725398",
            copyright: "© 2026 One Zero Learning",
            email: "triefitriexcelltricks@gmail.com",
            alamat: "Jl. Raden Gunawan II, Kec. Tanjung Karang Pusat, Kota Bandar Lampung, Lampung 35111",
          };
        },
      })
      .use(plugin)
      .use(VueApexCharts)
      .mount(el);
  },
});

InertiaProgress.init();
