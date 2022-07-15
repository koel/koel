<template>
  <button @click.stop="toggleLike" :title="title" class="text-secondary" data-testid="like-btn">
    <icon v-if="song.liked" :icon="faHeart" class="text-maroon" data-testid="btn-like-liked"/>
    <icon v-else :icon="faEmptyHeart" data-testid="btn-like-unliked"/>
  </button>
</template>

<script lang="ts" setup>
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { computed, toRefs } from 'vue'
import { favoriteStore } from '@/stores'

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const title = computed(() => `${song.value.liked ? 'Unlike' : 'Like'} ${song.value.title} by ${song.value.artist_name}`)

const toggleLike = () => favoriteStore.toggleOne(song.value)
</script>

<style lang="scss" scoped>
button {
  &:hover .fa-heart {
    color: var(--color-maroon);
  }
}
</style>
