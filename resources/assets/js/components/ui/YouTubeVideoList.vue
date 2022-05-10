<template>
  <div class="youtube-extra-wrapper">
    <template v-if="videos.length">
      <a
        v-for="video in videos"
        :key="video.id.videoId"
        :href="`https://youtu.be/${video.id.videoId}`"
        class="video"
        data-testid="youtube-search-result"
        role="button"
        @click.prevent="play(video)"
      >
        <div class="thumb">
          <img :alt="video.snippet.title" :src="video.snippet.thumbnails.default.url" width="90">
        </div>
        <div class="meta">
          <h3 class="title">{{ video.snippet.title }}</h3>
          <p class="desc">{{ video.snippet.description }}</p>
        </div>
      </a>
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

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const loading = ref(false)
const videos = ref<YouTubeVideo[]>([])

watchEffect(() => (videos.value = song.value.youtube?.items || []))

const play = (video: YouTubeVideo) => youTubeService.play(video)

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

  .video {
    display: flex;
    padding: 12px 0;

    .thumb {
      margin-right: 10px;
    }

    .title {
      font-size: 1.1rem;
      margin-bottom: .4rem;
    }

    .desc {
      font-size: .9rem;
    }

    &:hover, &:active, &:focus {
      color: var(--color-text-primary);
    }

    &:last-of-type {
      margin-bottom: 16px;
    }
  }
}
</style>
