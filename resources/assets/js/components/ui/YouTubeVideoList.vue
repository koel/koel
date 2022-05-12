<template>
  <div class="youtube-extra-wrapper">
    <template v-if="videos.length">
      <YouTubeVideo v-for="video in videos" :key="video.id.videoId" :video="video"/>
      <Btn v-if="!loading" class="more" data-testid="youtube-search-more-btn" @click.prevent="loadMore">Load More</Btn>
    </template>

    <p class="nope" v-else>Play a song to retrieve related YouTube videos.</p>
    <p class="nope" v-show="loading">Loading…</p>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, toRefs, watchEffect } from 'vue'
import { youTubeService } from '@/services'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const YouTubeVideo = defineAsyncComponent(() => import('@/components/ui/YouTubeVideoItem.vue'))

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const loading = ref(false)
const videos = ref<YouTubeVideo[]>([])

watchEffect(() => (videos.value = song.value.youtube?.items || []))

const loadMore = async () => {
  loading.value = true

  try {
    song.value.youtube = song.value.youtube || { nextPageToken: '', items: [] }

    const result = await youTubeService.searchVideosRelatedToSong(song.value, song.value.youtube.nextPageToken!)
    song.value.youtube.nextPageToken = result.nextPageToken
    song.value.youtube.items.push(...result.items as YouTubeVideo[])

    videos.value = song.value.youtube.items
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.youtube-extra-wrapper {
  overflow-x: hidden;

  a:last-of-type {
    margin-bottom: 16px;
  }
}
</style>
