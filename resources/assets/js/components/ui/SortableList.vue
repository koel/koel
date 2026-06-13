<template>
  <TransitionGroup move-class="sortable-list-move">
    <SortableListItem
      v-for="item in renderItems"
      :key="item.id"
      :id="item.id"
      :is-dragging="draggedId === item.id"
      @dragstart="onItemDragStart"
      @dragover="onItemDragOver"
      @drop="onItemDrop"
    >
      <slot :item />
    </SortableListItem>
  </TransitionGroup>
</template>

<script lang="ts" setup generic="Item extends { id: string }">
import { isEqual } from 'lodash-es'
import { computed, onBeforeUnmount, ref } from 'vue'

import SortableListItem from '@/components/ui/SortableListItem.vue'

const props = defineProps<{
  items: Item[]
}>()

const emit = defineEmits<{
  (e: 'reorder', ids: string[]): void
}>()

const AUTO_SCROLL_HOT_ZONE = 80
const AUTO_SCROLL_MAX_SPEED = 18

const draggedId = ref<string | null>(null)
const liveOrder = ref<string[]>([])
const orderSnapshot = ref<string[]>([])
let didDrop = false

let scrollContainer: HTMLElement | null = null
let autoScrollSpeed = 0
let autoScrollRafId = 0

const itemById = computed(() => new Map(props.items.map(item => [item.id, item])))

const renderItems = computed<Item[]>(() => {
  if (draggedId.value === null) {
    return props.items
  }

  return liveOrder.value.map(id => itemById.value.get(id)).filter((item): item is Item => item !== undefined)
})

const findScrollableAncestor = (el: HTMLElement): HTMLElement | null => {
  for (let current = el.parentElement; current; current = current.parentElement) {
    const overflowY = getComputedStyle(current).overflowY
    if (overflowY === 'auto' || overflowY === 'scroll') {
      return current
    }
  }
  return null
}

const stepAutoScroll = () => {
  if (!scrollContainer || autoScrollSpeed === 0) {
    autoScrollRafId = 0
    return
  }

  scrollContainer.scrollTop += autoScrollSpeed
  autoScrollRafId = requestAnimationFrame(stepAutoScroll)
}

const stopAutoScroll = () => {
  if (autoScrollRafId !== 0) {
    cancelAnimationFrame(autoScrollRafId)
    autoScrollRafId = 0
  }

  autoScrollSpeed = 0
}

const onDocumentDragOver = (event: DragEvent) => {
  if (draggedId.value === null || !scrollContainer) {
    return
  }

  const rect = scrollContainer.getBoundingClientRect()
  const distanceFromTop = event.clientY - rect.top
  const distanceFromBottom = rect.bottom - event.clientY

  if (distanceFromTop > 0 && distanceFromTop < AUTO_SCROLL_HOT_ZONE) {
    const intensity = 1 - distanceFromTop / AUTO_SCROLL_HOT_ZONE
    autoScrollSpeed = -Math.max(1, Math.round(AUTO_SCROLL_MAX_SPEED * intensity))
  } else if (distanceFromBottom > 0 && distanceFromBottom < AUTO_SCROLL_HOT_ZONE) {
    const intensity = 1 - distanceFromBottom / AUTO_SCROLL_HOT_ZONE
    autoScrollSpeed = Math.max(1, Math.round(AUTO_SCROLL_MAX_SPEED * intensity))
  } else {
    autoScrollSpeed = 0
  }

  if (autoScrollSpeed !== 0 && autoScrollRafId === 0) {
    autoScrollRafId = requestAnimationFrame(stepAutoScroll)
  }
}

const setUpGhost = (event: DragEvent, wrapper: HTMLElement) => {
  if (!event.dataTransfer) {
    return
  }

  event.dataTransfer.effectAllowed = 'move'

  // Clone the wrapper into <body>, outside any scrolled ancestor. setDragImage's
  // rasterization of an offscreen wrapper inside a scrolled parent would
  // otherwise bleed the parent's scrollbar into the snapshot.
  const rect = wrapper.getBoundingClientRect()
  const ghost = wrapper.cloneNode(true) as HTMLElement
  ghost.style.position = 'absolute'
  ghost.style.top = '0'
  ghost.style.left = '-99999px'
  ghost.style.width = `${rect.width}px`
  ghost.style.pointerEvents = 'none'
  document.body.appendChild(ghost)

  const offsetX = Math.min(event.clientX - rect.left, rect.width)
  const offsetY = Math.min(event.clientY - rect.top, rect.height)
  event.dataTransfer.setDragImage(ghost, offsetX, offsetY)
  event.dataTransfer.setData('application/x-koel.sortable-item', '1')

  setTimeout(() => ghost.remove(), 0)
}

const finalizeDrag = (commit: boolean) => {
  if (commit && !isEqual(liveOrder.value, orderSnapshot.value)) {
    emit('reorder', [...liveOrder.value])
  }

  draggedId.value = null
  liveOrder.value = []
  orderSnapshot.value = []

  stopAutoScroll()
  scrollContainer = null
}

const onItemDragStart = (id: string, wrapper: HTMLElement, event: DragEvent) => {
  const baseline = props.items.map(item => item.id)
  orderSnapshot.value = baseline
  liveOrder.value = [...baseline]

  draggedId.value = id
  didDrop = false

  scrollContainer = findScrollableAncestor(wrapper)
  setUpGhost(event, wrapper)
}

const onItemDragOver = (targetId: string, event: DragEvent) => {
  if (draggedId.value === null || draggedId.value === targetId) {
    return
  }

  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
  const insertBefore = event.clientY < rect.top + rect.height / 2

  const next = liveOrder.value.filter(id => id !== draggedId.value)
  const targetIndex = next.indexOf(targetId)
  if (targetIndex === -1) {
    return
  }

  next.splice(insertBefore ? targetIndex : targetIndex + 1, 0, draggedId.value)

  if (!isEqual(next, liveOrder.value)) {
    liveOrder.value = next
  }
}

const onItemDrop = () => {
  didDrop = true
  finalizeDrag(true)
}

const onDocumentDragEnd = () => {
  if (draggedId.value === null) {
    return
  }

  // dragend fires after drop in the normal flow. didDrop guards us against
  // double-finalizing; only here do we handle the "released outside any drop
  // target" path, which must revert the live preview to the snapshot.
  finalizeDrag(didDrop)
  didDrop = false
}

document.addEventListener('dragend', onDocumentDragEnd, true)
document.addEventListener('dragover', onDocumentDragOver)

onBeforeUnmount(() => {
  document.removeEventListener('dragend', onDocumentDragEnd, true)
  document.removeEventListener('dragover', onDocumentDragOver)
  stopAutoScroll()
})
</script>

<style lang="postcss" scoped>
.sortable-list-move {
  transition: transform 220ms ease;
}
</style>
