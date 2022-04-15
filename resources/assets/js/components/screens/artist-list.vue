<template>
  <section id="artistsWrapper">
    <ScreenHeader>
      Artists
      <template v-slot:controls>
        <ViewModeSwitch v-model="viewMode"/>
      </template>
    </ScreenHeader>

    <div ref="scroller" class="artists main-scroll-wrap" :class="`as-${viewMode}`" @scroll="scrolling">
      <ArtistCard v-for="item in displayedItems" :artist="item" :layout="itemLayout" :key="item.id"/>
      <ToTopButton/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, ref, watch } from 'vue'
import { eventBus, limitBy } from '@/utils'
import { artistStore, preferenceStore as preferences } from '@/stores'
import { useInfiniteScroll } from '@/composables'

const {
  ToTopButton,
  displayedItemCount,
  scroller,
  scrolling,
  makeScrollable
} = useInfiniteScroll(9)

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ArtistCard = defineAsyncComponent(() => import('@/components/artist/card.vue'))
const ViewModeSwitch = defineAsyncComponent(() => import('@/components/ui/view-mode-switch.vue'))

const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const artists = ref<Artist[]>([])

const displayedItems = computed(() => limitBy(artists.value, displayedItemCount.value))
const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')

watch(viewMode, () => preferences.artistsViewMode = viewMode.value)

eventBus.on({
  KOEL_READY () {
    artists.value = artistStore.all
    viewMode.value = preferences.artistsViewMode || 'thumbnails'
  },

  async LOAD_MAIN_CONTENT (view: MainViewName) {
    if (view === 'Artists') {
      await nextTick()
      makeScrollable(artists.value.length)
    }
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
