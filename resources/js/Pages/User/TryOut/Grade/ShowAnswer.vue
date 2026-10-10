<template>
  <Head>
    <title>{{ appName }} - Koreksi Jawaban</title>
  </Head>

  <div class="fullscreen-wrapper" ref="reviewRoot">
    <div class="container" style="margin-top: 80px; margin-bottom: 80px">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-1">Koreksi Jawaban</h4>
          <p class="text-muted mb-0">{{ exam.title }}</p>
        </div>
        <Link :href="`/user/grades/${grade.id}`" class="btn btn-outline-primary btn-sm">Kembali ke Hasil</Link>
      </div>

      <div v-if="currentAnswer" class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header bg-primary text-white">
              <span v-if="currentAnswer.type == 1" class="badge bg-danger">
                Pertanyaan {{ currentAnswer.review_number }}
              </span>
              <span v-else class="badge bg-danger">Petunjuk</span>
              <span class="ms-2">{{ currentAnswer.value_category_name }}</span>
            </div>

            <div class="card-body">
              <template v-if="currentAnswer.type == 1">
                <div v-html="currentAnswer.question" class="prevent-select mb-3"></div>

                <div class="list-group mb-4">
                  <div
                    v-for="option in currentOptions"
                    :key="option.number"
                    class="list-group-item"
                    :class="optionClass(option.number)">
                    <div class="d-flex align-items-start gap-2">
                      <strong>{{ option.label }}.</strong>
                      <div class="flex-grow-1" v-html="option.content"></div>
                      <span v-if="option.number == currentAnswer.question_answer" class="badge bg-success">Kunci</span>
                      <span
                        v-if="option.number == currentAnswer.answer && option.number != currentAnswer.question_answer"
                        class="badge bg-danger">
                        Jawaban Anda
                      </span>
                      <span
                        v-if="option.number == currentAnswer.answer && option.number == currentAnswer.question_answer"
                        class="badge bg-success">
                        Jawaban Anda
                      </span>
                    </div>
                  </div>
                </div>

                <div class="card border border-primary mb-0">
                  <div class="card-header bg-light-primary">
                    <h6 class="mb-0 text-primary">Pembahasan</h6>
                  </div>
                  <div class="card-body">
                    <div v-if="hasDiscussion(currentAnswer.discussion)" v-html="currentAnswer.discussion"></div>
                    <div v-else class="text-muted">Pembahasan untuk soal ini belum tersedia.</div>
                  </div>
                </div>
              </template>

              <div v-else v-html="currentAnswer.direction" class="prevent-select"></div>
            </div>

            <div class="card-footer d-flex justify-content-between">
              <button class="btn btn-primary" :disabled="indexPage === 0" @click="previousPage">Sebelumnya</button>
              <button class="btn btn-success" :disabled="indexPage >= answers.length - 1" @click="nextPage">Berikutnya</button>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0 text-white">Navigasi Soal</h5>
            </div>
            <div class="card-body" style="max-height: 560px; overflow-y: auto">
              <div v-for="group in answerGroups" :key="group.name" class="mb-4">
                <h6>{{ group.name }}</h6>
                <div class="d-flex flex-wrap gap-2">
                  <button
                    v-for="item in group.items"
                    :key="item.answer.question_id"
                    type="button"
                    class="btn btn-sm"
                    :class="navigationClass(item)"
                    @click="selectAnswer(item.index)">
                    {{ item.answer.review_number }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="alert alert-warning">Data jawaban untuk ujian ini tidak tersedia.</div>
    </div>
  </div>
</template>

<script>
import LayoutUser from "../../../../Layouts/LayoutUser.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { initMathJax, renderByMathjax } from "mathjax-vue3";

export default {
  layout: LayoutUser,
  components: { Head, Link },
  props: {
    exam: Object,
    grade: Object,
    answers: Array,
  },
  setup(props) {
    const indexPage = ref(0);
    const reviewRoot = ref(null);
    const optionLabels = ["A", "B", "C", "D", "E"];
    const currentAnswer = computed(() => props.answers[indexPage.value] || null);
    const currentOptions = computed(() => {
      if (!currentAnswer.value) {
        return [];
      }

      return optionLabels
        .map((label, index) => ({
          label,
          number: index + 1,
          content: currentAnswer.value[`option_${index + 1}`],
        }))
        .filter((option) => option.content !== null && option.content !== undefined && option.content !== "");
    });
    const answerGroups = computed(() => {
      const groups = new Map();

      props.answers.forEach((answer, index) => {
        if (Number(answer.type) !== 1) {
          return;
        }

        const name = answer.value_category_name || `Bagian ${answer.section || 1}`;
        if (!groups.has(name)) {
          groups.set(name, []);
        }
        groups.get(name).push({ answer, index });
      });

      return Array.from(groups, ([name, items]) => ({ name, items }));
    });

    const renderMath = async () => {
      await nextTick();
      if (reviewRoot.value) {
        renderByMathjax(reviewRoot.value);
      }
    };

    onMounted(() =>
      initMathJax(
        {
          url: "https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js",
          loadType: "defer",
        },
        renderMath
      )
    );
    watch(indexPage, renderMath);

    const hasDiscussion = (discussion) => {
      if (!discussion) {
        return false;
      }

      const element = document.createElement("div");
      element.innerHTML = discussion;
      return element.textContent.trim().length > 0 || element.querySelector("img, audio, video, table") !== null;
    };
    const optionClass = (optionNumber) => ({
      "border-success bg-light-success": optionNumber == currentAnswer.value?.question_answer,
      "border-danger bg-light-danger":
        optionNumber == currentAnswer.value?.answer && optionNumber != currentAnswer.value?.question_answer,
    });
    const navigationClass = (item) => {
      if (item.index === indexPage.value) {
        return "btn-primary";
      }

      return item.answer.answer == item.answer.question_answer ? "btn-outline-success" : "btn-outline-danger";
    };
    const selectAnswer = (index) => {
      indexPage.value = index;
    };
    const previousPage = () => {
      if (indexPage.value > 0) {
        indexPage.value--;
      }
    };
    const nextPage = () => {
      if (indexPage.value < props.answers.length - 1) {
        indexPage.value++;
      }
    };

    return {
      answerGroups,
      currentAnswer,
      currentOptions,
      hasDiscussion,
      indexPage,
      navigationClass,
      nextPage,
      optionClass,
      previousPage,
      reviewRoot,
      selectAnswer,
    };
  },
};
</script>
