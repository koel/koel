<template>
  <section id="homeWrapper">
    <ScreenHeader layout="collapsed">{{ greeting }}</ScreenHeader>

    <div class="main-scroll-wrap" @scroll="scrolling">
      <ScreenEmptyState v-if="libraryEmpty">
        <template v-slot:icon>
          <icon :icon="faVolumeOff"/>
        </template>
        No songs found.
        <span class="secondary d-block">
          {{ isAdmin ? 'Have you set up your library yet?' : 'Contact your administrator to set up your library.' }}
        </span>
      </ScreenEmptyState>

      <template v-else>
        <div class="two-cols">
          <MostPlayedSongs data-testid="most-played-songs"/>
          <RecentlyPlayedSongs data-testid="recently-played-songs"/>
        </div>

        <div class="two-cols">
          <RecentlyAddedAlbums data-testid="recently-added-albums"/>
          <RecentlyAddedSongs data-testid="recently-added-songs"/>
        </div>

        <MostPlayedArtists data-testid="most-played-artists"/>
        <MostPlayedAlbums data-testid="most-played-albums"/>

        <ToTopButton/>
      </template>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faVolumeOff } from '@fortawesome/free-solid-svg-icons'
import { sample } from 'lodash'
import { computed } from 'vue'
import { eventBus, noop } from '@/utils'
import { commonStore, overviewStore, userStore } from '@/stores'
import { useAuthorization, useInfiniteScroll } from '@/composables'

import MostPlayedSongs from '@/components/screens/home/MostPlayedSongs.vue'
import RecentlyPlayedSongs from '@/components/screens/home/RecentlyPlayedSongs.vue'
import RecentlyAddedAlbums from '@/components/screens/home/RecentlyAddedAlbums.vue'
import RecentlyAddedSongs from '@/components/screens/home/RecentlyAddedSongs.vue'
import MostPlayedArtists from '@/components/screens/home/MostPlayedArtists.vue'
import MostPlayedAlbums from '@/components/screens/home/MostPlayedAlbums.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

const { ToTopButton, scrolling } = useInfiniteScroll(() => noop())

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

const greeting = computed(() => sample(greetings)!.replace('%s', userStore.current?.name))
const libraryEmpty = computed(() => commonStore.state.song_length === 0)

let initialized = false

eventBus.on('LOAD_MAIN_CONTENT', async (view: MainViewName) => {
  if (view === 'Home' && !initialized) {
    try {
      await overviewStore.init()
      initialized = true
    } catch (e) {
      console.error(e)
    }
  }
})
</script>

<style lang="scss">
#homeWrapper {
  .two-cols {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-gap: .7em 1em;
  }

  .recent {
    h1 button {
      float: right;
      padding: 6px 10px;
      margin-top: -3px;
    }
  }

  ol {
    display: grid;
    grid-gap: .7em 1em;
    align-content: start;
  }

  .main-scroll-wrap {
    section {
      margin-bottom: 48px;
    }

    h1 {
      font-size: 1.4rem;
      margin: 0 0 1.8rem;
      font-weight: var(--font-weight-thin);
    }
  }

  @media only screen and (max-width: 768px) {
    .two-cols {
      grid-template-columns: 1fr;
    }
  }
}
</style>
