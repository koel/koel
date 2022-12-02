<template>
  <div class="youtube-extra-wrapper">
    <template v-if="videos.length">
      <ul>
        <li v-for="video in videos" :key="video.id.videoId" data-testid="youtube-video">
          <YouTubeVideo :video="video" />
        </li>
      </ul>
      <Btn v-if="!loading" class="more" @click.prevent="loadMore">Load More</Btn>
    </template>

    <p v-if="loading" class="nope">Loading…</p>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { youTubeService } from '@/services'
import { logger } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const YouTubeVideo = defineAsyncComponent(() => import('@/components/ui/YouTubeVideoItem.vue'))

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const loading = ref(false)
const videos = ref<YouTubeVideo[]>([])

let nextPageToken = ''

const loadMore = async () => {
  loading.value = true

  try {
    const result = await youTubeService.searchVideosBySong(song.value, nextPageToken)
    nextPageToken = result.nextPageToken
    videos.value.push(...result.items)
  } catch (err) {
    logger.error(err)
  } finally {
    loading.value = false
  }
}

watch(song, () => {
  videos.value = []
  nextPageToken = ''
  loadMore()
}, { immediate: true })
</script>

<style lang="scss" scoped>
.youtube-extra-wrapper {
  overflow-x: hidden;

  ul {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
  }
}
</style>
