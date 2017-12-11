<template>
  <div id="youtube-extra-wrapper">
    <template v-if="videos && videos.length">
      <a class="video" v-for="video in videos" :href="`https://youtu.be/${video.id.videoId}`"
        @click.prevent="play(video)">
        <div class="thumb">
          <img :src="video.snippet.thumbnails.default.url" width="90">
        </div>
        <div class="meta">
          <h3 class="title">{{ video.snippet.title }}</h3>
          <p class="desc">{{ video.snippet.description }}</p>
        </div>
      </a>
      <button @click="loadMore" v-if="!loading" class="more btn-blue">Load More</button>
    </template>
    <p class="nope" v-else>Play a song to retrieve related YouTube videos.</p>
    <p class="nope" v-show="loading">Loading…</p>
  </div>
</template>

<script>
import { youtube as youtubeService } from '@/services'

export default {
  name: 'main-wrapper--extra--youtube',
  props: {
    song: {
      type: Object,
      required: true
    }
  },

  data () {
    return {
      loading: false,
      videos: []
    }
  },

  watch: {
    song (val) {
      this.videos = val.youtube ? val.youtube.items : []
    }
  },

  methods: {
    play (video) {
      youtubeService.play(video)
    },

    /**
     * Load more videos.
     */
    async loadMore () {
      this.loading = true
      try {
        await youtubeService.searchVideosRelatedToSong(this.song)
        this.videos = this.song.youtube.items
      } catch (e) {
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
#youtube-extra-wrapper {
  overflow-x: hidden;

  .video {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid #333;

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

    &:hover {
      .title {
        color: #fff;
      }
    }

    &:last-of-type {
      margin-bottom: 16px;
    }
  }
}
</style>
