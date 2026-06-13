<template>
  <ScreenBase id="homeWrapper">
    <template #header>
      <ScreenHeader layout="collapsed">{{ greeting }}</ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faVolumeOff" />
      </template>
      No songs found.
      <span v-if="currentUserCan.manageSettings()" class="secondary block"> Have you set up your library yet? </span>
    </ScreenEmptyState>

    <div v-else class="home-sections space-y-12 w-full">
      <TransitionGroup move-class="home-block-move">
        <HomeBlockSortable
          v-for="block in orderedBlocks"
          :key="block.id"
          :id="block.id"
          :is-dragging="draggedId === block.id"
          @dragstart="onDragStart"
          @dragover="onDragOver"
          @drop="onDrop"
        >
          <component :is="block.component" :loading :data-testid="block.id" />
        </HomeBlockSortable>
      </TransitionGroup>
      <BtnScrollToTop />
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { sample } from 'lodash-es'
import type { Component } from 'vue'
import { computed, onBeforeUnmount, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { userStore } from '@/stores/userStore'
import { useRouter } from '@/composables/useRouter'
import { usePolicies } from '@/composables/usePolicies'
import { useErrorHandler } from '@/composables/useErrorHandler'

import MostPlayedSongs from '@/components/screens/home/MostPlayedSongs.vue'
import RecentlyPlayedPlayables from '@/components/screens/home/RecentlyPlayedPlayables.vue'
import NewAlbums from '@/components/screens/home/NewAlbums.vue'
import NewSongs from '@/components/screens/home/NewSongs.vue'
import TopArtists from '@/components/screens/home/TopArtists.vue'
import TopAlbums from '@/components/screens/home/TopAlbums.vue'
import NewArtists from '@/components/screens/home/NewArtists.vue'
import RandomAlbums from '@/components/screens/home/RandomAlbums.vue'
import RandomArtists from '@/components/screens/home/RandomArtists.vue'
import LeastPlayedSongs from '@/components/screens/home/LeastPlayedSongs.vue'
import RandomSongs from '@/components/screens/home/RandomSongs.vue'
import SimilarSongs from '@/components/screens/home/SimilarSongs.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import BtnScrollToTop from '@/components/ui/BtnScrollToTop.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import HomeBlockSortable from '@/components/screens/home/HomeBlockSortable.vue'

interface Block {
  id: string
  label: string
  component: Component
}

const BLOCKS: Block[] = [
  { id: 'recently-played-songs', label: 'Recently Played', component: RecentlyPlayedPlayables },
  { id: 'recently-added-albums', label: 'Latest Albums', component: NewAlbums },
  { id: 'similar-songs', label: 'You Might Also Like', component: SimilarSongs },
  { id: 'most-played-albums', label: 'Top Albums', component: TopAlbums },
  { id: 'most-played-songs', label: 'Most Played', component: MostPlayedSongs },
  { id: 'most-played-artists', label: 'Top Artists', component: TopArtists },
  { id: 'recently-added-songs', label: 'New Songs', component: NewSongs },
  { id: 'recently-added-artists', label: 'New Artists', component: NewArtists },
  { id: 'least-played-songs', label: 'Hidden Gems', component: LeastPlayedSongs },
  { id: 'random-songs', label: 'Random Songs', component: RandomSongs },
  { id: 'random-albums', label: 'Random Albums', component: RandomAlbums },
  { id: 'random-artists', label: 'Random Artists', component: RandomArtists },
]

const BLOCK_BY_ID = new Map(BLOCKS.map(block => [block.id, block]))

const { currentUserCan } = usePolicies()

const greetings = [
  'Oh hai!',
  'Hey, %s!',
  'Howdy, %s!',
  'Yo!',
  'How’s it going, %s?',
  'Sup, %s?',
  'How’s life, %s?',
  'How’s your day, %s?',
  'How have you been, %s?',
]

const greeting = computed(() => (userStore.current ? sample(greetings)!.replace('%s', userStore.current.name) : ''))
const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const loading = ref(false)
let initialized = false

const reconcileOrder = (saved: readonly string[]): string[] => {
  const next: string[] = []
  const seen = new Set<string>()

  for (const id of saved) {
    if (BLOCK_BY_ID.has(id) && !seen.has(id)) {
      next.push(id)
      seen.add(id)
    }
  }

  for (const block of BLOCKS) {
    if (!seen.has(block.id)) {
      next.push(block.id)
    }
  }

  return next
}

const draggedId = ref<string | null>(null)
const liveOrder = ref<string[]>([])
const orderSnapshot = ref<string[]>([])
let didDrop = false

const orderedBlocks = computed<Block[]>(() => {
  const ids = draggedId.value !== null ? liveOrder.value : reconcileOrder(preferenceStore.home_blocks_order ?? [])
  return ids.map(id => BLOCK_BY_ID.get(id)!).filter(Boolean)
})

const arraysEqual = (a: readonly string[], b: readonly string[]) =>
  a.length === b.length && a.every((value, index) => value === b[index])

const setUpGhost = (event: DragEvent, wrapper: HTMLElement) => {
  if (!event.dataTransfer) {
    return
  }

  event.dataTransfer.effectAllowed = 'move'

  // Clone the wrapper into <body>, outside the scrolled <main>. Otherwise
  // setDragImage's rasterization of a wrapper that's currently offscreen
  // inside a scrolled parent picks up the parent's scrollbar.
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
  event.dataTransfer.setData('application/x-koel.home-block', '1')

  // The browser has snapshotted the clone synchronously; remove it on the
  // next tick so it doesn't leak into the document.
  setTimeout(() => ghost.remove(), 0)
}

const AUTO_SCROLL_HOT_ZONE = 80
const AUTO_SCROLL_MAX_SPEED = 18

let scrollContainer: HTMLElement | null = null
let autoScrollSpeed = 0
let autoScrollRafId = 0

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

const onDragStart = (id: string, wrapper: HTMLElement, event: DragEvent) => {
  const baseline = reconcileOrder(preferenceStore.home_blocks_order ?? [])
  orderSnapshot.value = baseline
  liveOrder.value = [...baseline]
  draggedId.value = id
  didDrop = false
  scrollContainer = findScrollableAncestor(wrapper)
  setUpGhost(event, wrapper)
}

const onDragOver = (targetId: string, event: DragEvent) => {
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

  if (!arraysEqual(next, liveOrder.value)) {
    liveOrder.value = next
  }
}

const finalizeDrag = (commit: boolean) => {
  if (commit && !arraysEqual(liveOrder.value, orderSnapshot.value)) {
    preferenceStore.home_blocks_order = [...liveOrder.value]
  }
  draggedId.value = null
  liveOrder.value = []
  orderSnapshot.value = []
  stopAutoScroll()
  scrollContainer = null
}

const onDrop = () => {
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

eventBus
  .on('SONGS_DELETED', () => overviewStore.fetch())
  .on('SONGS_UPDATED', () => overviewStore.fetch())
  .on('SONG_UPLOADED', () => overviewStore.fetch())

useRouter().onScreenActivated('Home', async () => {
  if (!initialized) {
    loading.value = true
    try {
      await overviewStore.fetch()
      initialized = true
    } catch (error: unknown) {
      useErrorHandler('dialog').handleHttpError(error)
    } finally {
      loading.value = false
    }
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('dragend', onDocumentDragEnd, true)
  document.removeEventListener('dragover', onDocumentDragOver)
  stopAutoScroll()
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.home-sections {
  @apply min-w-0;

  > * {
    @apply min-w-0;
  }

  > *:not(:first-child) {
    @apply relative;

    /* The divider sits halfway up the space-y-12 (3rem) gap, so it stretches edge-to-edge
       without forcing top padding inside each block. */
    &::before {
      @apply content-[''] absolute -top-6 left-0 right-0 -mx-6 h-px bg-k-fg-5;
    }
  }
}

/* FLIP move animation for blocks reflowing during a drag. */
.home-block-move {
  transition: transform 220ms ease;
}
</style>
