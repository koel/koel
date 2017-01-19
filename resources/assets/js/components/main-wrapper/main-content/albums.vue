<template>
  <section id="albumsWrapper">
    <h1 class="heading">
      <span>Albums</span>
      <view-mode-switch :mode="viewMode" for="albums"/>
    </h1>

    <div ref="scroller" class="albums main-scroll-wrap" :class="'as-'+viewMode" @scroll="scrolling">
      <album-item v-for="item in displayedItems" :album="item"/>
      <span class="item filler" v-for="n in 6"/>
      <to-top-button/>
    </div>
  </section>
</template>

<script>
import { filterBy, limitBy, event } from '../../../utils'
import { albumStore } from '../../../stores'
import albumItem from '../../shared/album-item.vue'
import viewModeSwitch from '../../shared/view-mode-switch.vue'
import infiniteScroll from '../../../mixins/infinite-scroll'

export default {
  mixins: [infiniteScroll],
  components: { albumItem, viewModeSwitch },

  data () {
    return {
      perPage: 9,
      numOfItems: 9,
      q: '',
      viewMode: null
    }
  },

  computed: {
    displayedItems () {
      return limitBy(
        filterBy(albumStore.all, this.q, 'name', 'artist.name'),
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
    event.on({
      /**
       * When the application is ready, load the first batch of items.
       */
      'koel:ready': () => this.displayMore(),

      'filter:changed': q => {
        this.q = q
      }
    })
  }
}
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#albumsWrapper {
  .albums {
    @include artist-album-wrapper();
  }
}
</style>
