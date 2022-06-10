<template>
  <section id="homeWrapper">
    <ScreenHeader>{{ greeting }}</ScreenHeader>

    <div class="main-scroll-wrap" @scroll="scrolling">
      <div class="two-cols">
        <MostPlayedSongs/>
        <RecentlyPlayedSongs/>
      </div>

      <div class="two-cols">
        <RecentlyAddedAlbums/>
        <RecentlyAddedSongs/>
      </div>

      <MostPlayedArtists/>
      <MostPlayedAlbum/>

      <ToTopButton/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { sample } from 'lodash'
import { computed, defineAsyncComponent } from 'vue'

import { eventBus, noop } from '@/utils'
import { overviewStore, userStore } from '@/stores'
import { useInfiniteScroll } from '@/composables'

import MostPlayedSongs from '@/components/screens/home/MostPlayedSongs.vue'
import RecentlyPlayedSongs from '@/components/screens/home/RecentlyPlayedSongs.vue'
import RecentlyAddedAlbums from '@/components/screens/home/RecentlyAddedAlbums.vue'
import RecentlyAddedSongs from '@/components/screens/home/RecentlyAddedSongs.vue'
import MostPlayedArtists from '@/components/screens/home/MostPlayedArtists.vue'
import MostPlayedAlbum from '@/components/screens/home/MostPlayedAlbum.vue'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const { ToTopButton, scrolling } = useInfiniteScroll(() => noop())

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

const greeting = computed(() => sample(greetings)!.replace('%s', userStore.current.name))

let initialized = false

eventBus.on('LOAD_MAIN_CONTENT', async (view: MainViewName) => {
  if (view === 'Home' && !initialized) {
    await overviewStore.init()
    initialized = true
  }
})
</script>

<style lang="scss">
#homeWrapper {
  .two-cols {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-gap: .7em 1em;

    ol, li {
      overflow: hidden;
    }
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
