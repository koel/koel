<template>
  <div class="youtube-extra-wrapper overflow-x-hidden">
    <template v-if="videos.length">
      <ul class="space-y-4 mb-8">
        <li v-for="video in videos" :key="video.id.videoId" data-testid="youtube-video">
          <YouTubeVideo :video="video" />
        </li>
      </ul>
      <Btn v-if="!loading" small @click.prevent="loadMore">Load More</Btn>
    </template>

    <p v-if="loading" class="nope">Loading…</p>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { youTubeService } from '@/services'
import { useErrorHandler } from '@/composables'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const YouTubeVideo = defineAsyncComponent(() => import('@/components/ui/youtube/YouTubeVideoItem.vue'))

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
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
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
