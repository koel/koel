<template>
  <dialog
    ref="dialog"
    class="m-auto text-k-fg min-w-full md:min-w-[480px] border-0 p-0 md:rounded-md overflow-visible bg-k-bg backdrop:bg-black/70"
    @close.prevent
    @keydown.esc.prevent
  >
    <component :is="options.component" v-if="options.component" v-bind="props" @close="close" />
  </dialog>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { ModalKey } from '@/config/symbols'

const dialog = ref<HTMLDialogElement>()
const options = requireInjection(ModalKey)

const toggleCssClass = (...classes: string[]) => classes.forEach(c => dialog.value?.classList.toggle(c))

const props = computed(() => ({
  ...(options.value.props || {}),
  toggleCssClass:
    options.value.props && 'toggleCssClass' in options.value.props
      ? options.value.props.toggleCssClass
      : toggleCssClass,
}))

const close = () => {
  options.value = {
    component: null,
  }
}

watch(
  () => options.value.component,
  component => (component ? dialog.value?.showModal() : dialog.value?.close()),
)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
dialog {
  :deep(> *) {
    @apply relative;

    > header,
    > main,
    > footer {
      @apply px-6 py-5;
    }

    > header {
      @apply flex bg-k-fg-5;

      h1 {
        @apply text-3xl leading-normal truncate;
      }
    }

    > footer {
      @apply mt-0 bg-black/10 border-t border-k-fg-5 space-x-2 rounded-b-md;
    }
  }
}
</style>
