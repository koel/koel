<template>
  <section id="artistsWrapper">
    <ScreenHeader>
      Artists
      <template v-slot:controls>
        <ViewModeSwitch v-model="viewMode"/>
      </template>
    </ScreenHeader>

    <div ref="scroller" :class="`as-${viewMode}`" class="artists main-scroll-wrap" @scroll="scrolling">
      <ArtistCard v-for="artist in artists" :key="artist.id" :artist="artist" :layout="itemLayout"/>
      <ToTopButton/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, ref, toRef, watch } from 'vue'
import { eventBus } from '@/utils'
import { artistStore, preferenceStore as preferences } from '@/stores'
import { useInfiniteScroll } from '@/composables'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'

const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const artists = toRef(artistStore.state, 'artists')

const {
  ToTopButton,
  scroller,
  scrolling,
  makeScrollable
} = useInfiniteScroll(async () => await fetchArtists())

const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')

watch(viewMode, () => preferences.artistsViewMode = viewMode.value)

let initialized = false
let loading = false
const page = ref<number | null>(1)
const moreArtistsAvailable = computed(() => page.value !== null)

const fetchArtists = async () => {
  if (loading || !moreArtistsAvailable.value) return

  loading = true
  page.value = await artistStore.fetch(page.value!)
  loading = false
}

eventBus.on('KOEL_READY', () => (viewMode.value = preferences.artistsViewMode || 'thumbnails'))

eventBus.on('LOAD_MAIN_CONTENT', async (view: MainViewName) => {
  if (view === 'Artists' && !initialized) {
    await makeScrollable()
    initialized = true
  }
})
</script>

<style lang="scss">
#artistsWrapper {
  .artists {
    @include artist-album-wrapper();
  }
}
</style>
