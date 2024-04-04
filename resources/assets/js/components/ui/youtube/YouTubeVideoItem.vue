<template>
  <a
    :href="url"
    data-testid="youtube-search-result"
    class="flex gap-3 !text-k-text-secondary hover:!text-k-text-primary focus:!text-k-text-primary active:!text-k-text-primary"
    role="button"
    @click.prevent="play"
  >
    <img class="self-start" :alt="video.snippet.title" :src="video.snippet.thumbnails.default.url" width="90">
    <aside class="space-y-1">
      <h3 class="text-lg">{{ unescape(video.snippet.title) }}</h3>
      <p class="text-[0.9rem]">{{ video.snippet.description }}</p>
    </aside>
  </a>
</template>

<script lang="ts" setup>
import { unescape } from 'lodash'
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

<style lang="postcss" scoped>
aside {
  overflow-wrap: anywhere;
}
</style>
