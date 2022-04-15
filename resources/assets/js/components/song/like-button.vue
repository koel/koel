<template>
  <button @click.stop="toggleLike" :title="title" class="text-secondary" data-test="like-btn">
    <i class="fa fa-heart text-maroon" v-if="song.liked" data-test="btn-like-liked"></i>
    <i class="fa fa-heart-o" data-test="btn-like-unliked" v-else></i>
  </button>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { favoriteStore } from '@/stores'

export default Vue.extend({
  props: {
    song: {
      type: Object,
      required: true
    } as PropOptions<Song>
  },

  computed: {
    title (): string {
      return `${this.song.liked ? 'Unlike' : 'Like'} ${this.song.title} by ${this.song.artist.name}`
    }
  },

  methods: {
    toggleLike () {
      favoriteStore.toggleOne(this.song)
    }
  }
})
</script>

<style lang="scss" scoped>
button {
  &:hover .fa-heart-o {
    color: var(--color-maroon);
  }
}
</style>
