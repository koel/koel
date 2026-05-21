<template>
  <article
    :class="message.type"
    class="rounded-l-md cursor-pointer flex items-stretch opacity-90 transition-transform duration-300 origin-right hover:opacity-100 hover:scale-110"
    title="Click to dismiss"
    @click="dismiss"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <aside class="flex items-center px-3 py-0 bg-black/10">
      <Icon :icon="hovering ? faTimesCircle : typeIcon" />
    </aside>
    <main class="flex-1 py-2 pl-3 pr-4">{{ message.content }}</main>
  </article>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRefs } from 'vue'
import {
  faCircleCheck,
  faCircleExclamation,
  faCircleInfo,
  faTimesCircle,
  faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons'

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

let timeoutHandler: number
const hovering = ref(false)

const dismiss = () => {
  emit('dismiss', message.value)
  window.clearTimeout(timeoutHandler)
}

const cancelAutoDismiss = () => window.clearTimeout(timeoutHandler)
const setAutoDismiss = () => (timeoutHandler = window.setTimeout(() => dismiss(), message.value.timeout * 1000))

const onMouseEnter = () => {
  hovering.value = true
  cancelAutoDismiss()
}

const onMouseLeave = () => {
  hovering.value = false
  setAutoDismiss()
}

onMounted(() => setAutoDismiss())
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
</style>
