<template>
  <dialog
    ref="dialog"
    :class="{ resizable }"
    class="m-auto text-k-fg min-w-full md:min-w-[480px] border-0 md:border md:border-k-fg-10 md:shadow-2xl p-0 md:rounded-xl overflow-visible bg-k-bg backdrop:bg-black/70"
    @cancel.prevent="forwardEscapeToModalContent"
    @close="closeIfDismissedNatively"
    @keydown.esc="handleDialogEscape"
  >
    <component :is="options.component" v-if="options.component" v-bind="props" @close="close" />
    <span
      v-if="resizable"
      aria-hidden="true"
      class="grip absolute bottom-1 right-1 size-3 cursor-nwse-resize"
      data-testid="modal-resize-grip"
      @pointerdown.prevent="startResizing"
    />
  </dialog>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { ModalKey } from '@/config/symbols'
import { useResizableModal } from '@/composables/useResizableModal'
import { useModalDialogs } from '@/composables/useModalDialogs'

const { showModalDialog } = useModalDialogs()

const dialog = ref<HTMLDialogElement>()
const options = requireInjection(ModalKey)
const { resizable, startResizing } = useResizableModal(dialog)

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

const forwardEscapeToModalContent = () =>
  dialog.value?.firstElementChild?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))

const handleDialogEscape = (event: KeyboardEvent) => {
  event.preventDefault()

  if (!dialog.value?.firstElementChild?.contains(event.target as Node)) {
    forwardEscapeToModalContent()
  }
}

const closeIfDismissedNatively = () => {
  if (!dialog.value?.open) {
    close()
  }
}

watch(
  () => options.value.component,
  component => (component ? dialog.value && showModalDialog(dialog.value) : dialog.value?.close()),
)

onBeforeUnmount(() => dialog.value?.close())
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
dialog {
  :deep(> *:not(.grip)) {
    @apply relative;

    > header,
    > main,
    > footer {
      @apply px-6 py-5;
    }

    > header {
      @apply flex bg-k-fg-5 md:rounded-t-xl;

      h1 {
        @apply text-3xl leading-normal truncate;
      }
    }

    > footer {
      @apply mt-0 bg-black/10 border-t border-k-fg-5 space-x-2 md:rounded-b-xl;
    }
  }

  &.resizable[open] {
    @apply flex flex-col;
  }

  &.resizable :deep(> form) {
    @apply flex-1 min-h-0 flex flex-col w-full max-w-none max-h-none;

    > main {
      @apply flex-1 overflow-auto;
    }

    > footer {
      @apply mt-auto;
    }
  }
}

.grip {
  background: linear-gradient(
    135deg,
    transparent 45%,
    var(--k-fg-30) 45% 55%,
    transparent 55% 70%,
    var(--k-fg-30) 70% 80%,
    transparent 80%
  );
}
</style>
