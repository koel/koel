<template>
  <section id="albumsWrapper">
    <h1 class="heading">
      <span>Albums</span>
      <view-mode-switch :mode="viewMode" for="albums"/>
    </h1>

    <div ref="scroller" class="albums main-scroll-wrap" :class="`as-${viewMode}`" @scroll="scrolling">
      <album-item v-for="item in displayedItems" :album="item" :key="item.id"/>
      <span class="item filler" v-for="n in 6"/>
      <to-top-button/>
    </div>
  </section>
</template>

<script>
import { filterBy, limitBy, event } from '@/utils'
import { albumStore } from '@/stores'
import albumItem from '@/components/shared/album-item.vue'
import viewModeSwitch from '@/components/shared/view-mode-switch.vue'
import infiniteScroll from '@/mixins/infinite-scroll'

export default {
  mixins: [infiniteScroll],
  components: { albumItem, viewModeSwitch },

  data () {
    return {
      perPage: 9,
      numOfItems: 9,
      q: '',
      viewMode: null,
      albums: []
    }
  },

  computed: {
    displayedItems () {
      return limitBy(
        filterBy(this.albums, this.q, 'name', 'artist.name'),
        this.numOfItems
      )
    }
  },

  methods: {
    changeViewMode (mode) {
      this.viewMode = mode
    }
  },

  created () {
    event.on('koel:ready', () => {
      this.albums = albumStore.all
    })

    event.on('filter:changed', q => {
        this.q = q
    })
  }
}
</script>

<style lang="scss">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#albumsWrapper {
  .albums {
    @include artist-album-wrapper();
  }
}
</style>
