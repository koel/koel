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

    <div v-else class="home-sections space-y-12">
      <RecentlyPlayedPlayables :loading data-testid="recently-played-songs" />
      <NewAlbums :loading data-testid="recently-added-albums" />
      <SimilarSongs :loading data-testid="similar-songs" />
      <TopAlbums :loading data-testid="most-played-albums" />
      <MostPlayedSongs :loading data-testid="most-played-songs" />
      <TopArtists :loading data-testid="most-played-artists" />
      <NewSongs :loading data-testid="recently-added-songs" />
      <NewArtists :loading data-testid="recently-added-artists" />
      <LeastPlayedSongs :loading data-testid="least-played-songs" />
      <RandomSongs :loading data-testid="random-songs" />
      <BtnScrollToTop />
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { sample } from 'lodash'
import { computed, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
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
import LeastPlayedSongs from '@/components/screens/home/LeastPlayedSongs.vue'
import RandomSongs from '@/components/screens/home/RandomSongs.vue'
import SimilarSongs from '@/components/screens/home/SimilarSongs.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import BtnScrollToTop from '@/components/ui/BtnScrollToTop.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

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
.home-sections {
  > *:not(:first-child) {
    @apply pt-12 relative;

    &::before {
      @apply content-[''] absolute top-0 left-0 right-0 -mx-6 h-px bg-k-fg-5;
    }
  }
}
</style>
