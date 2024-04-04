<template>
  <TransitionGroup name="toast" tag="ul">
    <li v-for="message in messages" :key="message.id">
      <MessageToast :message="message" @dismiss="removeMessage(message)" />
    </li>
  </TransitionGroup>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { uuid } from '@/utils'
import MessageToast from '@/components/ui/MessageToast.vue'

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

defineExpose({ info, success, warning, error })
</script>

<style lang="postcss" scoped>
ul {
  position: fixed;
  z-index: 9999;
  right: 0;
  top: 10px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 5px;
}

.toast-enter-active {
  opacity: 1;
  transition: all 0.2s ease-in;
}

.toast-leave-active {
  opacity: 0;
  transition: all 0.2s ease-out;
}

.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translateX(100px);
}
</style>
