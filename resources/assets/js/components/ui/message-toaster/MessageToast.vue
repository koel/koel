<template>
  <article
    ref="root"
    :class="[message.type, { swiping: isSwiping }]"
    :style="swipeOffset ? { transform: `translateX(${swipeOffset}px)` } : undefined"
    class="relative overflow-hidden flex items-center gap-4 py-3.5 pl-5 pr-4 rounded-2xl border border-k-fg-10 bg-k-bg-context-menu text-k-fg shadow-2xl transition-transform"
    @focusin="onFocusIn"
    @focusout="onFocusOut"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <span v-if="pending" class="icon-slot" data-testid="toast-pending">
      <ProgressRing v-if="message.progress" :value="message.progress()" class="size-4" />
      <Icon v-else :icon="faCircleNotch" spin />
    </span>
    <button
      v-else
      class="icon-slot dismiss"
      type="button"
      @click="dismiss"
      @mouseenter="hoveringIcon = true"
      @mouseleave="hoveringIcon = false"
    >
      <Icon v-if="hoveringIcon" :icon="faXmark" class="text-[14px]" />
      <component :is="message.icon" v-else-if="message.icon" :size="18" />
      <Icon v-else :icon="typeIcon" />
      <span class="sr-only">Dismiss</span>
    </button>
    <main class="flex-1 flex items-center gap-4">
      <component :is="message.component" v-if="message.component" v-bind="message.props" />
      <template v-else>{{ message.content }}</template>
    </main>
    <ToastActions v-if="message.actions?.length" :actions="message.actions" />
    <span
      v-if="dismissesOnTimeout"
      :key="countdownKey"
      :class="{ paused }"
      :style="{ animationDuration: `${message.timeout}s` }"
      class="countdown"
    />
  </article>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, ref, toRefs, watch } from 'vue'
import { usePointerSwipe } from '@vueuse/core'
import {
  faCircleCheck,
  faCircleExclamation,
  faCircleInfo,
  faCircleNotch,
  faTriangleExclamation,
  faXmark,
} from '@fortawesome/free-solid-svg-icons'

import ProgressRing from '@/components/ui/ProgressRing.vue'
import ToastActions from '@/components/ui/message-toaster/ToastActions.vue'

const SWIPE_DISMISS_DISTANCE = 80

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
const dismissesOnTimeout = computed(() => !pending.value && !message.value.persistent && !message.value.actions?.length)

let timeoutHandler: number | undefined
let remainingTime = 0
let countdownStartedAt = 0
let hovering = false
let focused = false
const paused = ref(false)
const countdownKey = ref(0)
const hoveringIcon = ref(false)

const cancelAutoDismiss = () => window.clearTimeout(timeoutHandler)

const dismiss = () => {
  cancelAutoDismiss()
  emit('dismiss', message.value)
}

const runCountdown = () => {
  cancelAutoDismiss()

  if (!dismissesOnTimeout.value || paused.value) {
    return
  }

  countdownStartedAt = Date.now()
  timeoutHandler = window.setTimeout(() => dismiss(), remainingTime)
}

const setPaused = (value: boolean) => {
  if (value === paused.value) {
    return
  }

  paused.value = value

  if (!value) {
    runCountdown()
    return
  }

  cancelAutoDismiss()

  if (dismissesOnTimeout.value) {
    remainingTime -= Date.now() - countdownStartedAt
  }
}

const onMouseEnter = () => {
  hovering = true
  setPaused(true)
}

const onMouseLeave = () => {
  hovering = false
  setPaused(focused)
}

const onFocusIn = () => {
  focused = true
  setPaused(true)
}

const onFocusOut = () => {
  focused = false
  setPaused(hovering)
}

watch(
  [dismissesOnTimeout, () => message.value.timeout],
  () => {
    remainingTime = message.value.timeout * 1000
    countdownKey.value++
    runCountdown()
  },
  { immediate: true },
)

const root = ref<HTMLElement>()
const swipeOffset = ref(0)

const { isSwiping, distanceX } = usePointerSwipe(root, {
  threshold: 10,
  pointerTypes: ['touch', 'pen'],
  onSwipe: () => {
    if (!pending.value) {
      swipeOffset.value = Math.max(0, -distanceX.value)
    }
  },
  onSwipeEnd: () => {
    if (swipeOffset.value >= SWIPE_DISMISS_DISTANCE) {
      dismiss()
      return
    }

    swipeOffset.value = 0
  },
})

onBeforeUnmount(cancelAutoDismiss)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

.icon-slot {
  @apply shrink-0 flex items-center text-[18px];
}

.info :is(.icon-slot, .countdown) {
  @apply text-k-primary;
}

.danger :is(.icon-slot, .countdown) {
  @apply text-k-danger;
}

.success :is(.icon-slot, .countdown) {
  @apply text-k-success;
}

.warning :is(.icon-slot, .countdown) {
  @apply text-k-warning;
}

.countdown {
  @apply absolute inset-x-0 bottom-0 h-0.5 bg-current origin-left;
  animation-name: countdown;
  animation-timing-function: linear;
  animation-fill-mode: forwards;

  &.paused {
    animation-play-state: paused;
  }
}

@keyframes countdown {
  from {
    transform: scaleX(1);
  }

  to {
    transform: scaleX(0);
  }
}

.swiping {
  @apply transition-none;
}

button.dismiss {
  @apply relative size-6 -m-[3px] justify-center rounded-full cursor-pointer transition-colors;

  &::before {
    @apply content-[''] absolute -inset-2;
  }

  &:hover {
    @apply bg-k-danger text-white;
  }
}
</style>
