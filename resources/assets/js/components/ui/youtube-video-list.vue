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
          <img :src="video.snippet.thumbnails.default.url" width="90">
        </div>
        <div class="meta">
          <h3 class="title">{{ video.snippet.title }}</h3>
          <p class="desc">{{ video.snippet.description }}</p>
        </div>
      </a>
      <btn @click.prevent="loadMore" v-if="!loading" class="more" data-testid="youtube-search-more-btn">
        Load More
      </btn>
    </template>

    <p class="nope" v-else>Play a song to retrieve related YouTube videos.</p>
    <p class="nope" v-show="loading">Loading…</p>
  </div>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { youtube as youtubeService } from '@/services'

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue')
  },

  props: {
    song: {
      type: Object,
      required: true
    } as PropOptions<Song>
  },

  data: () => ({
    loading: false,
    videos: [] as YouTubeVideo[]
  }),

  watch: {
    song: {
      immediate: true,
      handler (val: Song): void {
        this.videos = val.youtube ? val.youtube.items : []
      }
    }
  },

  methods: {
    play: (video: YouTubeVideo): void => youtubeService.play(video),

    async loadMore (): Promise<void> {
      this.loading = true

      try {
        this.song.youtube = this.song.youtube || { nextPageToken: '', items: [] }

        const result = await youtubeService.searchVideosRelatedToSong(this.song, this.song.youtube.nextPageToken!)
        this.song.youtube.nextPageToken = result.nextPageToken
        this.song.youtube.items.push(...result.items as YouTubeVideo[])

        this.videos = this.song.youtube.items
      } finally {
        this.loading = false
      }
    }
  }
})
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
