<template>
  <article
    :class="message.type"
    class="rounded-l-md flex items-stretch transition-[background-color,color] duration-300"
    @focusin="onFocusIn"
    @focusout="onFocusOut"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <span v-if="pending" class="flex items-center px-3 bg-black/10" data-testid="toast-pending">
      <ProgressRing v-if="message.progress" :value="message.progress()" class="size-4" />
      <Icon v-else :icon="faCircleNotch" spin />
    </span>
    <button v-else class="flex items-center px-3 py-0 bg-black/10 cursor-pointer" type="button" @click="dismiss">
      <Icon :icon="hovering ? faTimesCircle : typeIcon" />
      <span class="sr-only">Dismiss</span>
    </button>
    <main class="flex-1 flex items-center py-2 pl-3 pr-4">
      <component :is="message.component" v-if="message.component" v-bind="message.props" />
      <template v-else>{{ message.content }}</template>
    </main>
    <ToastActions v-if="message.actions?.length" :actions="message.actions" class="pr-3" />
  </article>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, ref, toRefs, watch } from 'vue'
import {
  faCircleCheck,
  faCircleExclamation,
  faCircleInfo,
  faCircleNotch,
  faTimesCircle,
  faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons'

import ProgressRing from '@/components/ui/ProgressRing.vue'
import ToastActions from '@/components/ui/message-toaster/ToastActions.vue'

const props = defineProps<{ message: ToastMessage }>()
const emit = defineEmits<{ (e: 'dismiss', message: ToastMessage): void }>()

const { message } = toRefs(props)

const typeIcon = computed(() => {
  switch (message.value.type) {
    case 'info':
      return faCircleInfo
    case 'success':
      return faCircleCheck
    case 'warning':
      return faTriangleExclamation
    default:
      return faCircleExclamation
  }
})

const pending = computed(() => message.value.type === 'loading')
const dismissesOnTimeout = computed(() => !pending.value && !message.value.actions?.length)

let timeoutHandler: number | undefined
const hovering = ref(false)
let focused = false

const cancelAutoDismiss = () => window.clearTimeout(timeoutHandler)

const dismiss = () => {
  cancelAutoDismiss()
  emit('dismiss', message.value)
}

const setAutoDismiss = () => {
  cancelAutoDismiss()

  if (dismissesOnTimeout.value) {
    timeoutHandler = window.setTimeout(() => dismiss(), message.value.timeout * 1000)
  }
}

const onMouseEnter = () => {
  hovering.value = true
  cancelAutoDismiss()
}

const onMouseLeave = () => {
  hovering.value = false
  focused || setAutoDismiss()
}

const onFocusIn = () => {
  focused = true
  cancelAutoDismiss()
}

const onFocusOut = () => {
  focused = false
  hovering.value || setAutoDismiss()
}

watch([dismissesOnTimeout, () => message.value.timeout], () => hovering.value || focused || setAutoDismiss(), {
  immediate: true,
})

onBeforeUnmount(cancelAutoDismiss)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.info {
  @apply bg-blue-600 text-blue-100;
}

.danger {
  @apply bg-red-700 text-red-100;
}

.success {
  @apply bg-green-600 text-green-100;
}

.warning {
  @apply bg-orange-600 text-orange-100;
}

.loading {
  @apply bg-k-bg text-k-fg border border-r-0 border-k-fg-10;
}
</style>
