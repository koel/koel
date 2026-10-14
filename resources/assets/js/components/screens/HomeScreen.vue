<template>
  <ScreenBase id="homeWrapper">
    <template #header>
      <ScreenHeader layout="collapsed">
        {{ greeting }}
        <template #controls>
          <button
            v-if="!libraryEmpty"
            v-koel-tooltip
            type="button"
            class="w-9 h-9 rounded-full flex items-center justify-center text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 transition shrink-0"
            data-testid="reorder-home-blocks-btn"
            @click="openReorderModal"
          >
            <ListChevronsUpDownIcon class="w-5 h-5" />
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
      <EmptyLibraryHint />
    </ScreenEmptyState>

    <div v-else class="home-sections space-y-12 w-full">
      <component
        v-for="block in orderedBlocks"
        :key="block.id"
        :is="block.component"
        v-bind="block.props"
        :loading
        :data-testid="block.id"
      />
      <BtnScrollToTop />
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { ListChevronsUpDownIcon } from 'lucide-vue-next'
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
import { useErrorHandler } from '@/composables/useErrorHandler'

import RecentlyPlayedPlayables from '@/components/screens/home/RecentlyPlayedPlayables.vue'
import AlbumArtistShelf from '@/components/screens/home/AlbumArtistShelf.vue'
import PlayableShelf from '@/components/screens/home/PlayableShelf.vue'
import SimilarSongs from '@/components/screens/home/SimilarSongs.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import BtnScrollToTop from '@/components/ui/BtnScrollToTop.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import EmptyLibraryHint from '@/components/ui/EmptyLibraryHint.vue'

const ReorderBlocksModal = defineAsyncComponent(() => import('@/components/screens/home/ReorderBlocksModal.vue'))

interface Block {
  id: string
  label: string
  component: Component
  props?: Record<string, unknown>
}

const blocks: Block[] = [
  { id: 'recently-played-songs', label: 'Recently Played', component: RecentlyPlayedPlayables },
  {
    id: 'recently-added-albums',
    label: 'Latest Albums',
    component: AlbumArtistShelf,
    props: { header: 'Latest Albums', stateKey: 'recentlyAddedAlbums', emptyText: 'No albums added yet.' },
  },
  { id: 'similar-songs', label: 'You Might Also Like', component: SimilarSongs },
  {
    id: 'most-played-albums',
    label: 'Top Albums',
    component: AlbumArtistShelf,
    props: { header: 'Top Albums', stateKey: 'mostPlayedAlbums', emptyText: 'No albums found.' },
  },
  {
    id: 'most-played-songs',
    label: 'Most Played',
    component: PlayableShelf,
    props: { header: 'Most Played', stateKey: 'mostPlayedSongs', emptyText: 'Nothing played as of late.' },
  },
  {
    id: 'most-played-artists',
    label: 'Top Artists',
    component: AlbumArtistShelf,
    props: { header: 'Top Artists', stateKey: 'mostPlayedArtists', emptyText: 'No artists found.' },
  },
  {
    id: 'recently-added-songs',
    label: 'New Songs',
    component: PlayableShelf,
    props: { header: 'New Songs', stateKey: 'recentlyAddedSongs', emptyText: 'No songs available.' },
  },
  {
    id: 'recently-added-artists',
    label: 'New Artists',
    component: AlbumArtistShelf,
    props: { header: 'New Artists', stateKey: 'recentlyAddedArtists', emptyText: 'No new artists.' },
  },
  {
    id: 'least-played-songs',
    label: 'Hidden Gems',
    component: PlayableShelf,
    props: { header: 'Hidden Gems', stateKey: 'leastPlayedSongs', emptyText: 'No songs to rediscover.' },
  },
  {
    id: 'random-songs',
    label: 'Random Songs',
    component: PlayableShelf,
    props: {
      header: 'Random Songs',
      stateKey: 'randomSongs',
      emptyText: 'No songs available.',
      refresh: () => overviewStore.refreshRandomSongs(),
    },
  },
  {
    id: 'random-albums',
    label: 'Random Albums',
    component: AlbumArtistShelf,
    props: {
      header: 'Random Albums',
      stateKey: 'randomAlbums',
      emptyText: 'No albums yet.',
      refresh: () => overviewStore.refreshRandomAlbums(),
    },
  },
  {
    id: 'random-artists',
    label: 'Random Artists',
    component: AlbumArtistShelf,
    props: {
      header: 'Random Artists',
      stateKey: 'randomArtists',
      emptyText: 'No artists yet.',
      refresh: () => overviewStore.refreshRandomArtists(),
    },
  },
]

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
let overviewOutdated = false

// Sort `blocks` so they appear in the order saved in the preference. Blocks
// whose id isn't in the saved list fall to the end (Infinity), keeping their
// canonical relative order via Array.sort's stability.
const orderedBlocks = computed<Block[]>(() => {
  const saved = preferenceStore.home_blocks_order ?? []
  const positionOf = (id: string) => {
    const i = saved.indexOf(id)
    return i === -1 ? Infinity : i
  }

  return [...blocks].sort((a, b) => positionOf(a.id) - positionOf(b.id))
})

const openReorderModal = () =>
  openModal<'REORDER_HOME_BLOCKS'>(ReorderBlocksModal, {
    blocks: orderedBlocks.value.map(({ id, label }) => ({ id, label })),
  })

eventBus
  .on('SONGS_DELETED', () => overviewStore.fetch())
  .on('SONGS_UPDATED', () => overviewStore.fetch())
  .on('SONG_UPLOADED', () => {
    overviewOutdated = true
  })

useRouter().onScreenActivated('Home', async () => {
  if (!initialized) {
    overviewOutdated = false
    loading.value = true
    try {
      await overviewStore.fetch()
      initialized = true
    } catch (error: unknown) {
      useErrorHandler('dialog').handleHttpError(error)
    } finally {
      loading.value = false
    }
  } else if (overviewOutdated) {
    overviewOutdated = false
    await overviewStore.fetch()
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
