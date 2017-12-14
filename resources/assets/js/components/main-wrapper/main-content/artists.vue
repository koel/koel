<template>
  <section id="artistsWrapper">
    <h1 class="heading">
      <span>Artists</span>
      <view-mode-switch :mode="viewMode" for="artists"/>
    </h1>

    <div class="artists main-scroll-wrap" :class="`as-${viewMode}`" @scroll="scrolling">
      <artist-item v-for="item in displayedItems" :artist="item" :key="item.id"/>
      <span class="item filler" v-for="n in 6"/>
      <to-top-button/>
    </div>
  </section>
</template>

<script>
import { filterBy, limitBy, event } from '@/utils'
import { artistStore } from '@/stores'

import artistItem from '@/components/shared/artist-item.vue'
import viewModeSwitch from '@/components/shared/view-mode-switch.vue'
import infiniteScroll from '@/mixins/infinite-scroll'

export default {
  name: 'main-wrapper--main-content--artists',
  mixins: [infiniteScroll],
  components: { artistItem, viewModeSwitch },

  data () {
    return {
      perPage: 9,
      numOfItems: 9,
      q: '',
      viewMode: null,
      artists: []
    }
  },

  computed: {
    displayedItems () {
      return limitBy(
        filterBy(this.artists, this.q, 'name'),
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
      this.artists = artistStore.all
    })

    event.on({
      'filter:changed': q => {
        this.q = q
      }
    })
  }
}
</script>

<style lang="scss">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#artistsWrapper {
  .artists {
    @include artist-album-wrapper();
  }
}
</style>
