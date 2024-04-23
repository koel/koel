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
      <span class="secondary d-block">
        {{ isAdmin ? 'Have you set up your library yet?' : 'Contact your administrator to set up your library.' }}
      </span>
    </ScreenEmptyState>

    <div v-else class="space-y-12">
      <div class="grid grid-cols-1 md:grid-cols-2 w-full gap-8 md:gap-4">
        <MostPlayedSongs data-testid="most-played-songs" :loading="loading" />
        <RecentlyPlayedSongs data-testid="recently-played-songs" :loading="loading" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 w-full gap-8 md:gap-4">
        <RecentlyAddedAlbums data-testid="recently-added-albums" :loading="loading" />
        <RecentlyAddedSongs data-testid="recently-added-songs" :loading="loading" />
      </div>

      <MostPlayedArtists data-testid="most-played-artists" :loading="loading" />
      <MostPlayedAlbums data-testid="most-played-albums" :loading="loading" />

      <BtnScrollToTop />
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { sample } from 'lodash'
import { computed, ref } from 'vue'
import { eventBus } from '@/utils'
import { commonStore, overviewStore, userStore } from '@/stores'
import { useAuthorization, useErrorHandler, useRouter } from '@/composables'

import MostPlayedSongs from '@/components/screens/home/MostPlayedSongs.vue'
import RecentlyPlayedSongs from '@/components/screens/home/RecentlyPlayedSongs.vue'
import RecentlyAddedAlbums from '@/components/screens/home/RecentlyAddedAlbums.vue'
import RecentlyAddedSongs from '@/components/screens/home/RecentlyAddedSongs.vue'
import MostPlayedArtists from '@/components/screens/home/MostPlayedArtists.vue'
import MostPlayedAlbums from '@/components/screens/home/MostPlayedAlbums.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import BtnScrollToTop from '@/components/ui/BtnScrollToTop.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { isAdmin } = useAuthorization()

const greetings = [
  'Oh hai!',
  'Hey, %s!',
  'Howdy, %s!',
  'Yo!',
  'How’s it going, %s?',
  'Sup, %s?',
  'How’s life, %s?',
  'How’s your day, %s?',
  'How have you been, %s?'
]

const greeting = computed(() => userStore.current ? sample(greetings)!.replace('%s', userStore.current.name) : '')
const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const loading = ref(false)
let initialized = false

eventBus.on('SONGS_DELETED', () => overviewStore.refresh())
  .on('SONGS_UPDATED', () => overviewStore.refresh())

useRouter().onScreenActivated('Home', async () => {
  if (!initialized) {
    loading.value = true
    try {
      await overviewStore.init()
      initialized = true
    } catch (error: unknown) {
      useErrorHandler('dialog').handleHttpError(error)
    } finally {
      loading.value = false
    }
  }
})
</script>
