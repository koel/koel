<template>
  <dialog
    ref="dialog"
    :class="`${type}`"
    class="rounded-md shadow-xl border-0 p-0 min-w-[320px] max-w-[calc(100vw - 40px)] backdrop:bg-black/50"
  >
    <div class="flex gap-5 py-6 px-7">
      <aside>
        <i class="text-lg w-[2.3rem] aspect-square flex justify-center items-center rounded-full">
          <Icon v-if="type === 'info'" :icon="faInfo" />
          <Icon v-if="type === 'success'" :icon="faCheck" />
          <Icon v-if="type === 'warning'" :icon="faTriangleExclamation" />
          <Icon v-if="type === 'danger'" :icon="faExclamation" />
          <Icon v-if="type === 'confirm'" :icon="faQuestion" />
        </i>
      </aside>

      <main>
        <h3 v-if="title" class="text-2xl mb-4">{{ title }}</h3>
        <div class="mt-2">{{ message }}</div>
      </main>
    </div>

    <footer class="flex justify-end gap-2 px-6 py-4">
      <Btn v-if="showCancelButton" class="!bg-gray-100 !text-gray-600" name="cancel" @click.prevent="cancel">
        Cancel
      </Btn>
      <Btn name="ok">OK</Btn>
    </footer>
  </dialog>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { faCheck, faExclamation, faInfo, faQuestion, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons'
import Btn from '@/components/ui/form/Btn.vue'

type DialogType = 'info' | 'success' | 'warning' | 'danger' | 'confirm'

const dialog = ref<HTMLDialogElement>()
const type = ref<DialogType>('info')
const title = ref('')
const message = ref('')

const showCancelButton = computed(() => type.value === 'confirm')

// @ts-ignore
const close = () => dialog.value?.close()
const cancel = () => dialog.value?.dispatchEvent(new Event('cancel'))

const waitForInput = () => new Promise(resolve => {
  dialog.value?.addEventListener('cancel', () => {
    close()
    resolve(false)
  }, { once: true })

  dialog.value?.querySelector('[name=ok]')!.addEventListener('click', () => {
    close()
    resolve(true)
  }, { once: true })
})

const show = async (_type: DialogType, _message: string, _title: string = '') => {
  type.value = _type
  message.value = _message
  title.value = _title

  // @ts-ignore
  dialog.value.showModal()

  return waitForInput()
}

const success = async (message: string, title: string = '') => show('success', message, title)
const info = async (message: string, title: string = '') => show('info', message, title)
const warning = async (message: string, title: string = '') => show('warning', message, title)
const error = async (message: string, title: string = '') => show('danger', message, title)
const confirm = async (message: string, title: string = '') => show('confirm', message, title)

defineExpose({ success, info, warning, error, confirm })
</script>

<style lang="postcss" scoped>
dialog {
  --dialog-bg-color: #fff;
  --dialog-fg-color: #333;
  --dialog-footer-bg-color: rgba(0, 0, 0, .05);

  @media (prefers-color-scheme: dark) {
    --dialog-bg-color: rgb(38 38 38);
    --dialog-fg-color: #eee;
    --dialog-footer-bg-color: rgba(255, 255, 255, .02);
  }

  background: var(--dialog-bg-color);
  color: var(--dialog-fg-color);

  footer {
    background: var(--dialog-footer-bg-color);
  }

  &.info aside i {
    @apply bg-blue-100 text-blue-600;
  }

  &.success aside i {
    @apply bg-green-100 text-green-600;
  }

  &.confirm aside i {
    @apply bg-purple-100 text-purple-700;
  }

  &.warning aside i {
    @apply bg-orange-100 text-orange-600;
  }

  &.danger aside i {
    @apply bg-red-300 text-red-800;
  }
}
</style>
