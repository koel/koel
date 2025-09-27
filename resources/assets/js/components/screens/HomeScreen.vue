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
      <span v-if="currentUserCan.manageSettings()" class="secondary block">
        Have you set up your library yet?
      </span>
    </ScreenEmptyState>

    <div v-else class="space-y-12">
      <RecentlyPlayedPlayables :loading="loading" data-testid="recently-played-songs" />
      <NewAlbums :loading="loading" data-testid="recently-added-albums" />
      <NewSongs :loading="loading" data-testid="recently-added-songs" />
      <TopAlbums :loading="loading" data-testid="most-played-albums" />
      <MostPlayedSongs :loading="loading" data-testid="most-played-songs" />
      <TopArtists :loading="loading" data-testid="most-played-artists" />
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

const greeting = computed(() => userStore.current ? sample(greetings)!.replace('%s', userStore.current.name) : '')
const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const loading = ref(false)
let initialized = false

eventBus.on('SONGS_DELETED', () => overviewStore.fetch())
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
