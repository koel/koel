<template>
  <section id="albumsWrapper">
    <ScreenHeader>
      Albums
      <template v-slot:controls>
        <ViewModeSwitch v-model="viewMode" :value="viewMode"/>
      </template>
    </ScreenHeader>

    <div ref="scroller" class="albums main-scroll-wrap" :class="`as-${viewMode}`" @scroll="scrolling">
      <AlbumCard v-for="item in displayedItems" :album="item" :layout="itemLayout" :key="item.id"/>
      <ToTopButton/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, ref, watch } from 'vue'
import { eventBus, limitBy } from '@/utils'
import { albumStore, preferenceStore as preferences } from '@/stores'
import { useInfiniteScroll } from '@/composables'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const AlbumCard = defineAsyncComponent(() => import('@/components/album/card.vue'))
const ViewModeSwitch = defineAsyncComponent(() => import('@/components/ui/view-mode-switch.vue'))

const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const albums = ref<Album[]>([])

const {
  ToTopButton,
  displayedItemCount,
  scroller,
  scrolling,
  makeScrollable
} = useInfiniteScroll(9)

const displayedItems = computed(() => limitBy(albums.value, displayedItemCount.value))
const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')

watch(viewMode, () => preferences.albumsViewMode = viewMode.value)

eventBus.on({
  KOEL_READY () {
    albums.value = albumStore.all
    viewMode.value = preferences.albumsViewMode || 'thumbnails'
  },

  async LOAD_MAIN_CONTENT (view: MainViewName) {
    if (view === 'Albums') {
      await nextTick()
      makeScrollable(albums.value.length)
    }
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
