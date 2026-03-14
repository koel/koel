<template>
  <span v-if="prefersReducedMotion" class="block truncate">
    <template v-if="text">{{ text }}</template>
    <slot v-else />
  </span>
  <span
    v-else
    ref="containerRef"
    class="block max-w-full overflow-hidden"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <span
      ref="textRef"
      class="inline-block whitespace-nowrap"
      :class="animating ? '' : 'max-w-full truncate align-bottom'"
      :style="animationStyle"
    >
      <template v-if="text">{{ text }}</template>
      <slot v-else />
    </span>
  </span>
</template>

<script lang="ts" setup>
import { useMediaQuery } from '@vueuse/core'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = withDefaults(defineProps<{ text?: string; speed?: number; hoverOnly?: boolean }>(), {
  text: undefined,
  speed: 30,
  hoverOnly: false,
})

const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)')

const containerRef = ref<HTMLElement>()
const textRef = ref<HTMLElement>()
const overflow = ref(0)
const animating = ref(false)
const offset = ref(0)
const direction = ref<1 | -1>(-1)

let frameId = 0
let pauseTimeout = 0

const animationStyle = computed(() => {
  if (!animating.value) {
    return {}
  }

  return { transform: `translateX(${offset.value}px)` }
})

const measure = () => {
  if (!containerRef.value || !textRef.value) {
    return
  }

  const containerWidth = containerRef.value.offsetWidth
  const textWidth = textRef.value.scrollWidth

  overflow.value = textWidth - containerWidth
}

const stop = () => {
  animating.value = false
  offset.value = 0
  direction.value = -1
  cancelAnimationFrame(frameId)
  clearTimeout(pauseTimeout)
}

const pause = (ms: number) =>
  new Promise<void>(resolve => {
    pauseTimeout = window.setTimeout(resolve, ms)
  })

const animate = async () => {
  if (prefersReducedMotion.value) {
    return
  }

  measure()

  if (overflow.value <= 0) {
    stop()
    return
  }

  animating.value = true
  offset.value = 0
  direction.value = -1

  await pause(1500)

  let lastTime = 0

  const step = (time: number) => {
    if (!animating.value) {
      return
    }

    if (lastTime) {
      const delta = ((time - lastTime) / 1000) * props.speed

      offset.value += delta * direction.value

      if (offset.value <= -overflow.value) {
        offset.value = -overflow.value
        direction.value = 1
        lastTime = 0
        pauseTimeout = window.setTimeout(() => {
          lastTime = 0
          frameId = requestAnimationFrame(step)
        }, 1500)
        return
      }

      if (offset.value >= 0) {
        offset.value = 0
        direction.value = -1
        lastTime = 0
        pauseTimeout = window.setTimeout(() => {
          lastTime = 0
          frameId = requestAnimationFrame(step)
        }, 1500)
        return
      }
    }

    lastTime = time
    frameId = requestAnimationFrame(step)
  }

  frameId = requestAnimationFrame(step)
}

const onMouseEnter = () => {
  if (props.hoverOnly) {
    stop()
    animate()
  }
}

const onMouseLeave = () => {
  if (props.hoverOnly) {
    stop()
  }
}

watch(
  () => props.text,
  () => {
    stop()

    if (!props.hoverOnly) {
      animate()
    }
  },
  { flush: 'post' },
)

watch(
  containerRef,
  () => {
    if (containerRef.value && !props.hoverOnly) {
      stop()
      animate()
    }
  },
  { flush: 'post' },
)

onBeforeUnmount(stop)
</script>
