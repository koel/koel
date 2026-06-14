<template>
  <ScreenBase id="homeWrapper">
    <template #header>
      <ScreenHeader layout="collapsed">
        {{ greeting }}
        <template #controls>
          <button
            v-if="!libraryEmpty"
            type="button"
            class="w-9 h-9 rounded-full flex items-center justify-center text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 transition shrink-0"
            title="Reorder home blocks"
            data-testid="reorder-home-blocks-btn"
            @click="openReorderModal"
          >
            <Icon :icon="faSliders" />
            <span class="sr-only">Reorder home blocks</span>
          </button>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faVolumeOff" />
      </template>
      No songs found.
      <span v-if="currentUserCan.manageSettings()" class="secondary block"> Have you set up your library yet? </span>
    </ScreenEmptyState>

    <div v-else class="home-sections space-y-12 w-full">
      <component
        v-for="block in orderedBlocks"
        :key="block.id"
        :is="block.component"
        :loading
        :data-testid="block.id"
      />
      <BtnScrollToTop />
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faSliders, faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { sample } from 'lodash-es'
import type { Component } from 'vue'
import { computed, defineAsyncComponent, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { userStore } from '@/stores/userStore'
import { useRouter } from '@/composables/useRouter'
import { useModal } from '@/composables/useModal'
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

const ReorderBlocksModal = defineAsyncComponent(() => import('@/components/screens/home/ReorderBlocksModal.vue'))

interface Block {
  id: string
  label: string
  component: Component
}

const blocks: Block[] = [
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

const { currentUserCan } = usePolicies()
const { openModal } = useModal()

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

// Sort by position in `saved`. Blocks not listed sort to the end (Infinity);
// among themselves they keep canonical order via Array.sort stability.
const bySavedOrder = (saved: readonly string[]) => (a: Block, b: Block) => {
  const positionOf = (id: string) => {
    const i = saved.indexOf(id)
    return i === -1 ? Infinity : i
  }

  return positionOf(a.id) - positionOf(b.id)
}

const orderedBlocks = computed<Block[]>(() => [...blocks].sort(bySavedOrder(preferenceStore.home_blocks_order ?? [])))

const openReorderModal = () =>
  openModal<'REORDER_HOME_BLOCKS'>(ReorderBlocksModal, {
    blocks: blocks.map(({ id, label }) => ({ id, label })),
  })

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

    /* Divider sits in the gap between blocks. */
    &::before {
      @apply content-[''] absolute -top-6 left-0 right-0 -mx-6 h-px bg-k-fg-5;
    }
  }
}
</style>
