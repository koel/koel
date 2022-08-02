<template>
  <dialog ref="dialog" class="dialog" :class="`dialog-${type}`">
    <main>
      <aside>
        <icon :icon="faInfo" v-if="type === 'info'"/>
        <icon :icon="faCheck" v-if="type === 'success'"/>
        <icon :icon="faTriangleExclamation" v-if="type === 'warning'"/>
        <icon :icon="faExclamation" v-if="type === 'danger'"/>
        <icon :icon="faQuestion" v-if="type === 'confirm'"/>
      </aside>

      <div class="content">
        <h3 v-if="title" class="title">{{ title }}</h3>
        <div class="message">{{ message }}</div>
      </div>
    </main>

    <footer>
      <Btn v-if="showCancelButton" name="cancel" @click.prevent="cancel">Cancel</Btn>
      <Btn name="ok">OK</Btn>
    </footer>
  </dialog>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { faCheck, faExclamation, faInfo, faQuestion, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons'
import Btn from '@/components/ui/Btn.vue'

type DialogType = 'info' | 'success' | 'warning' | 'danger' | 'confirm'

const dialog = ref<HTMLDialogElement>()
const type = ref<DialogType>('info')
const title = ref('')
const message = ref('')

const showCancelButton = computed(() => type.value === 'confirm')

const close = () => dialog.value.close()
const cancel = () => dialog.value.dispatchEvent(new Event('cancel'))

const waitForInput = () => new Promise(resolve => {
  dialog.value.addEventListener('cancel', () => {
    close()
    resolve(false)
  }, { once: true })

  dialog.value.querySelector('[name=ok]')!.addEventListener('click', () => {
    close()
    resolve(true)
  }, { once: true })
})

const show = async (_type: DialogType, _message: string, _title: string = '') => {
  type.value = _type
  message.value = _message
  title.value = _title

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

<style lang="scss" scoped>
.dialog {
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
  border-radius: 5px;
  box-shadow: 0 5px 15px 3px rgba(0, 0, 0, .2);
  border: 0;
  padding: 0;
  min-width: 320px;
  max-width: calc(100vw - 40px);

  &::backdrop {
    background: rgba(0, 0, 0, 0.7);
  }

  main {
    display: flex;
    padding: 1.5rem 1.7rem;
    gap: 1.2rem;

    .title {
      font-size: 1.4rem;
      margin-bottom: 1rem;
    }

    .message {
      margin-top: .5rem;
    }
  }

  footer {
    display: flex;
    justify-content: flex-end;
    padding: 1rem 1.5rem;
    gap: .5rem;
    background: var(--dialog-footer-bg-color);

    [name=cancel] {
      background: #fefefe;
      color: #333;
    }
  }

  aside {
    font-size: 1.1rem;
    width: 2.3rem;
    height: 2.3rem;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  &-info aside {
    background-color: rgb(219 234 254);
    color: rgb(59 130 246);
  }

  &-success aside {
    background-color: rgb(209 250 229);
    color: rgb(16 185 129);
  }

  &-confirm aside {
    background-color: rgb(237 233 254);
    color: rgb(139 92 246);
  }

  &-warning aside {
    background-color: rgb(255 237 213);
    color: rgb(249 115 22);
  }

  &-danger aside {
    background-color: rgb(254 202 202);
    color: rgb(185 28 28);
  }
}
</style>
