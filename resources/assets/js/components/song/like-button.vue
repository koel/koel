<template>
  <button @click.stop="toggleLike" :title="title" class="text-secondary" data-test="like-btn">
    <i class="fa fa-heart text-maroon" v-if="song.liked" data-test="btn-like-liked"></i>
    <i class="fa fa-heart-o" data-test="btn-like-unliked" v-else></i>
  </button>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { favoriteStore } from '@/stores'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const title = computed(() => `${song.value.liked ? 'Unlike' : 'Like'} ${song.value.title} by ${song.value.artist.name}`)

const toggleLike = () => favoriteStore.toggleOne(song.value)
</script>

<style lang="scss" scoped>
button {
  &:hover .fa-heart-o {
    color: var(--color-maroon);
  }
}
</style>
