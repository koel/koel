<template>
  <section id="artistsWrapper">
    <h1 class="heading">
      <span>Artists</span>
      <view-mode-switch :mode="viewMode" for="artists"/>
    </h1>

    <div class="artists main-scroll-wrap" :class="'as-'+viewMode" @scroll="scrolling">
      <artist-item v-for="item in displayedItems" :artist="item"/>
      <span class="item filler" v-for="n in 6"/>
      <to-top-button :showing="showBackToTop"/>
    </div>
  </section>
</template>

<script>
import { filterBy, limitBy, event } from '../../../utils'
import { artistStore } from '../../../stores'

import artistItem from '../../shared/artist-item.vue'
import viewModeSwitch from '../../shared/view-mode-switch.vue'
import infiniteScroll from '../../../mixins/infinite-scroll'

export default {
  mixins: [infiniteScroll],

  components: { artistItem, viewModeSwitch },

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
        filterBy(artistStore.all, this.q, 'name'),
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

      'koel:teardown': () => {
        this.q = ''
        this.numOfItems = 9
      },

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

#artistsWrapper {
  .artists {
    @include artist-album-wrapper();
  }
}
</style>
