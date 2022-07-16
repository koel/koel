<template>
  <section id="albumsWrapper">
    <ScreenHeader layout="collapsed">
      Albums
      <template v-slot:controls>
        <ViewModeSwitch v-model="viewMode"/>
      </template>
    </ScreenHeader>

    <div
      ref="scroller"
      :class="`as-${viewMode}`"
      class="albums main-scroll-wrap"
      data-testid="album-list"
      @scroll="scrolling"
    >
      <AlbumCard v-for="album in albums" :key="album.id" :album="album" :layout="itemLayout"/>
      <ToTopButton/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, ref, toRef, watch } from 'vue'
import { eventBus } from '@/utils'
import { albumStore, preferenceStore as preferences } from '@/stores'
import { useInfiniteScroll } from '@/composables'

import AlbumCard from '@/components/album/AlbumCard.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'

const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const albums = toRef(albumStore.state, 'albums')

const {
  ToTopButton,
  scroller,
  scrolling,
  makeScrollable
} = useInfiniteScroll(async () => await fetchAlbums())

const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')

watch(viewMode, () => (preferences.albumsViewMode = viewMode.value))

let initialized = false
let loading = false
const page = ref<number | null>(1)
const moreAlbumsAvailable = computed(() => page.value !== null)

const fetchAlbums = async () => {
  if (loading || !moreAlbumsAvailable.value) return

  loading = true
  page.value = await albumStore.fetch(page.value!)
  loading = false
}

eventBus.on('LOAD_MAIN_CONTENT', async (view: MainViewName) => {
  if (view === 'Albums' && !initialized) {
    viewMode.value = preferences.albumsViewMode || 'thumbnails'
    await makeScrollable()
    initialized = true
  }
})
</script>

<style lang="scss">
#albumsWrapper {
  .albums {
    @include artist-album-wrapper();
  }
}
</style>
`
