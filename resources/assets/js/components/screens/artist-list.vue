<template>
  <section id="artistsWrapper">
    <screen-header>
      Artists
      <template v-slot:controls>
        <view-mode-switch v-model="viewMode"/>
      </template>
    </screen-header>

    <div ref="scroller" class="artists main-scroll-wrap" :class="`as-${viewMode}`" @scroll="scrolling">
      <artist-card v-for="item in displayedItems" :artist="item" :layout="itemLayout" :key="item.id"/>
      <to-top-button/>
    </div>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { limitBy, eventBus } from '@/utils'
import { artistStore, preferenceStore as preferences } from '@/stores'
import infiniteScroll from '@/mixins/infinite-scroll.ts'

export default mixins(infiniteScroll).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ArtistCard: () => import('@/components/artist/card.vue'),
    ViewModeSwitch: () => import('@/components/ui/view-mode-switch.vue')
  },

  data: () => ({
    perPage: 9,
    displayedItemCount: 9,
    viewMode: null as ArtistAlbumViewMode | null,
    artists: [] as Artist[]
  }),

  computed: {
    displayedItems (): Artist[] {
      return limitBy(this.artists, this.displayedItemCount)
    },

    itemLayout (): ArtistAlbumCardLayout {
      return this.viewMode === 'thumbnails' ? 'full' : 'compact'
    }
  },

  watch: {
    viewMode (): void {
      preferences.artistsViewMode = this.viewMode
    }
  },

  created (): void {
    eventBus.on({
      'KOEL_READY': (): void => {
        this.artists = artistStore.all
        this.viewMode = preferences.artistsViewMode || 'thumbnails'
      },

      'LOAD_MAIN_CONTENT': (view: MainViewName): void => {
        if (view === 'Artists') {
          this.$nextTick((): void => this.makeScrollable(this.$refs.scroller as HTMLElement, this.artists.length))
        }
      }
    })
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
