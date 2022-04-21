<template>
  <div class="youtube-extra-wrapper">
    <template v-if="videos.length">
      <a
        :href="`https://youtu.be/${video.id.videoId}`"
        :key="video.id.videoId"
        @click.prevent="play(video)"
        class="video"
        role="button"
        v-for="video in videos"
        data-test="youtube-search-result"
      >
        <div class="thumb">
          <img :src="video.snippet.thumbnails.default.url" width="90" :alt="video.snippet.title">
        </div>
        <div class="meta">
          <h3 class="title">{{ video.snippet.title }}</h3>
          <p class="desc">{{ video.snippet.description }}</p>
        </div>
      </a>
      <Btn @click.prevent="loadMore" v-if="!loading" class="more" data-testid="youtube-search-more-btn">
        Load More
      </Btn>
    </template>

    <p class="nope" v-else>Play a song to retrieve related YouTube videos.</p>
    <p class="nope" v-show="loading">Loading…</p>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, toRefs, watchEffect } from 'vue'
import { youtube as youtubeService } from '@/services'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const props = defineProps<{ song: Song }>()
const { song } = toRefs(props)

const loading = ref(false)
const videos = ref<YouTubeVideo[]>([])

watchEffect(() => (videos.value = song.value.youtube ? song.value.youtube.items : []))

const play = (video: YouTubeVideo) => youtubeService.play(video)

const loadMore = async () => {
  loading.value = true

  try {
    song.value.youtube = song.value.youtube || { nextPageToken: '', items: [] }

    const result = await youtubeService.searchVideosRelatedToSong(song.value, song.value.youtube.nextPageToken!)
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
