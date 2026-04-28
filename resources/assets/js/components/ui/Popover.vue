<template>
  <div ref="panel" :id="panelId" class="fixed inset-auto m-0" style="left: -9999px; top: -9999px">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, onMounted, ref, useId, watch } from 'vue'
import type { Placement } from '@floating-ui/dom'
import { autoUpdate, computePosition, flip, offset } from '@floating-ui/dom'

const props = withDefaults(
  defineProps<{
    anchor: HTMLElement | undefined
    placement?: Placement
    mode?: 'auto' | 'manual'
    /** Pixel gap between anchor and panel. Default 6. */
    gap?: number
  }>(),
  { placement: 'bottom', mode: 'auto', gap: 6 },
)

const emit = defineEmits<{ (e: 'toggle', open: boolean): void }>()

const panel = ref<HTMLElement>()
const panelId = `popover-${useId()}`
const isOpen = ref(false)

let stopAutoUpdate: (() => void) | null = null

const reposition = async () => {
  if (!panel.value || !props.anchor) {
    return
  }
  const { x, y } = await computePosition(props.anchor, panel.value, {
    placement: props.placement,
    middleware: [flip(), offset(props.gap)],
    strategy: 'fixed',
  })
  panel.value.style.left = `${x}px`
  panel.value.style.top = `${y}px`
}

const onToggle = (event: Event) => {
  const open = (event as ToggleEvent).newState === 'open'
  isOpen.value = open
  emit('toggle', open)

  if (open && props.anchor && panel.value) {
    stopAutoUpdate?.()
    stopAutoUpdate = autoUpdate(props.anchor, panel.value, reposition)
  } else {
    stopAutoUpdate?.()
    stopAutoUpdate = null
    if (panel.value) {
      // Reset to off-screen so a re-open doesn't briefly flash at the previous position.
      panel.value.style.left = '-9999px'
      panel.value.style.top = '-9999px'
    }
  }
}

const wireAnchor = (el: HTMLElement) => {
  el.setAttribute('aria-haspopup', 'menu')
  el.setAttribute('aria-controls', panelId)
  el.setAttribute('aria-expanded', String(isOpen.value))
  // Use the native popover-trigger relationship so the browser handles toggle
  // correctly (suppresses light-dismiss when the trigger itself is clicked).
  el.setAttribute('popovertarget', panelId)
  el.setAttribute('popovertargetaction', 'toggle')
}

const unwireAnchor = (el: HTMLElement) => {
  el.removeAttribute('aria-haspopup')
  el.removeAttribute('aria-controls')
  el.removeAttribute('aria-expanded')
  el.removeAttribute('popovertarget')
  el.removeAttribute('popovertargetaction')
}

watch(
  () => props.anchor,
  (next, prev) => {
    prev && unwireAnchor(prev)
    next && wireAnchor(next)
  },
  { immediate: true, flush: 'post' },
)

watch(isOpen, open => {
  props.anchor?.setAttribute('aria-expanded', String(open))
})

onMounted(() => {
  if (!panel.value) {
    return
  }
  panel.value.setAttribute('popover', props.mode)
  panel.value.addEventListener('toggle', onToggle)
})

onBeforeUnmount(() => {
  panel.value?.removeEventListener('toggle', onToggle)
  stopAutoUpdate?.()
  stopAutoUpdate = null
  props.anchor && unwireAnchor(props.anchor)
})

// Guard against calls in the wrong state — the native popover API throws
// InvalidStateError on already-open / not-showing, but consumers expect
// these to be no-ops so they can call hide() defensively.
const show = () => {
  if (panel.value && !isOpen.value) {
    panel.value.showPopover()
  }
}
const hide = () => {
  if (panel.value && isOpen.value) {
    panel.value.hidePopover()
  }
}
const toggle = () => panel.value?.togglePopover()

defineExpose({ show, hide, toggle, isOpen, panel })
</script>
