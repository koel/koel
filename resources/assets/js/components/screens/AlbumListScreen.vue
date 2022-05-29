<template>
  <section id="albumsWrapper">
    <ScreenHeader>
      Albums
      <template v-slot:controls>
        <ViewModeSwitch v-model="viewMode"/>
      </template>
    </ScreenHeader>

    <div ref="scroller" :class="`as-${viewMode}`" class="albums main-scroll-wrap" @scroll="scrolling">
      <AlbumCard v-for="item in displayedItems" :key="item.id" :album="item" :layout="itemLayout"/>
      <ToTopButton/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, ref, toRef, watch } from 'vue'
import { eventBus, limitBy } from '@/utils'
import { albumStore, preferenceStore as preferences } from '@/stores'
import { useInfiniteScroll } from '@/composables'

const AlbumCard = defineAsyncComponent(() => import('@/components/album/AlbumCard.vue'))
const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ViewModeSwitch = defineAsyncComponent(() => import('@/components/ui/ViewModeSwitch.vue'))

const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const albums = toRef(albumStore.state, 'albums')

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
