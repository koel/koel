<template>
  <div ref="root" class="popover">
    <TransitionGroup class="flex flex-col items-end gap-2" name="toast" tag="ul">
      <li v-for="message in messages" :key="message.id">
        <MessageToast :message="message" @dismiss="removeMessage(message)" />
      </li>
    </TransitionGroup>
  </div>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { uuid } from '@/utils'

import MessageToast from '@/components/ui/message-toaster/MessageToast.vue'

const root = ref<HTMLDivElement & {
  popover?: 'manual' | 'auto',
  showPopover?: () => void
}>()

const messages = ref<ToastMessage[]>([])

const addMessage = (type: 'info' | 'success' | 'warning' | 'danger', content: string, timeout = 5) => {
  messages.value.push({
    type,
    content,
    timeout,
    id: uuid()
  })
}

const removeMessage = (message: ToastMessage) => (messages.value = messages.value.filter(({ id }) => id !== message.id))

const info = (content: string, timeout?: number) => addMessage('info', content, timeout)
const success = (content: string, timeout?: number) => addMessage('success', content, timeout)
const warning = (content: string, timeout?: number) => addMessage('warning', content, timeout)
const error = (content: string, timeout?: number) => addMessage('danger', content, timeout)

onMounted(() => {
  if (!root.value) return
  root.value.popover = 'manual'
  root.value.showPopover?.()
})

defineExpose({ info, success, warning, error })
</script>

<style lang="postcss" scoped>
.popover, .popover:popover-open {
  inset: unset;
  @apply fixed top-3 right-0 p-0 bg-transparent m-0 overflow-visible border-0 backdrop:bg-transparent;
}

.toast-enter-active {
  @apply opacity-100 transition-all duration-200 ease-in;
}

.toast-leave-active {
  @apply opacity-0 transition-all duration-200 ease-out;
}

.toast-enter-from, .toast-leave-to {
  @apply opacity-0;
  transform: translateX(100px);
}
</style>
