<template>
  <a :href="url" data-testid="youtube-search-result" role="button" @click.prevent="play">
    <img :alt="video.snippet.title" :src="video.snippet.thumbnails.default.url" width="90">
    <div class="meta">
      <h3 class="title">{{ video.snippet.title }}</h3>
      <p class="desc">{{ video.snippet.description }}</p>
    </div>
  </a>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { youTubeService } from '@/services'
import { useRouter } from '@/composables'

const { go } = useRouter()

const props = defineProps<{ video: YouTubeVideo }>()
const { video } = toRefs(props)

const url = computed(() => `https://youtu.be/${video.value.id.videoId}`)

const play = () => {
  youTubeService.play(video.value)
  go('youtube')
}
</script>

<style lang="scss" scoped>
a {
  display: flex;
  gap: 10px;

  &:hover, &:active, &:focus {
    color: var(--color-text-primary);
  }
}

.title {
  font-size: 1.1rem;
  margin-bottom: .4rem;
}

.desc {
  font-size: .9rem;
}

img {
  align-self: self-start;
}

.meta {
  overflow-wrap: anywhere;
}
</style>
