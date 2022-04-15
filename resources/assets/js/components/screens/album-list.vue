<template>
  <section id="albumsWrapper">
    <screen-header>
      Albums
      <template v-slot:controls>
        <view-mode-switch v-model="viewMode"/>
      </template>
    </screen-header>

    <div ref="scroller" class="albums main-scroll-wrap" :class="`as-${viewMode}`" @scroll="scrolling">
      <album-card v-for="item in displayedItems" :album="item" :layout="itemLayout" :key="item.id" />
      <to-top-button/>
    </div>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { limitBy, eventBus } from '@/utils'
import { albumStore, preferenceStore as preferences } from '@/stores'
import infiniteScroll from '@/mixins/infinite-scroll.ts'

export default mixins(infiniteScroll).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    AlbumCard: () => import('@/components/album/card.vue'),
    ViewModeSwitch: () => import('@/components/ui/view-mode-switch.vue')
  },

  data: () => ({
    perPage: 9,
    displayedItemCount: 9,
    viewMode: null as ArtistAlbumViewMode | null,
    albums: [] as Album[]
  }),

  computed: {
    displayedItems (): Album[] {
      return limitBy(this.albums, this.displayedItemCount)
    },

    itemLayout (): ArtistAlbumCardLayout {
      return this.viewMode === 'thumbnails' ? 'full' : 'compact'
    }
  },

  watch: {
    viewMode (): void {
      preferences.albumsViewMode = this.viewMode
    }
  },

  created (): void {
    eventBus.on({
      'KOEL_READY': (): void => {
        this.albums = albumStore.all
        this.viewMode = preferences.albumsViewMode || 'thumbnails'
      },

      'LOAD_MAIN_CONTENT': (view: MainViewName): void => {
        if (view === 'Albums') {
          this.$nextTick((): void => this.makeScrollable(this.$refs.scroller as HTMLElement, this.albums.length))
        }
      }
    })
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
