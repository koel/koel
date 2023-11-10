<template>
  <button :title="title" type="button" @click.stop="toggleLike">
    <Icon v-if="song.liked" :icon="faHeart" />
    <Icon v-else :icon="faEmptyHeart" />
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
