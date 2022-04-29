<template>
  <article class="artist-info" :class="mode" data-test="artist-info">
    <h1 class="name">
      <span>{{ artist.name }}</span>
      <button :title="`Shuffle all songs by ${artist.name}`" class="shuffle control" @click.prevent="shuffleAll">
        <i class="fa fa-random"></i>
      </button>
    </h1>

    <main v-if="artist.info">
      <ArtistThumbnail :entity="artist"/>

      <template v-if="artist.info">
        <div v-if="artist.info?.bio?.summary" class="bio">
          <div v-if="showSummary" class="summary" v-html="artist.info?.bio?.summary"></div>
          <div v-if="showFull" class="full" v-html="artist.info?.bio?.full"></div>

          <button v-show="showSummary" class="more" data-test="more-btn" @click.prevent="showingFullBio = true">
            Full Bio
          </button>
        </div>
        <p v-else class="text-secondary none">This artist has no Last.fm biography – yet.</p>

        <footer>Data &copy; <a :href="artist.info?.url" rel="openener" target="_blank">Last.fm</a></footer>
      </template>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { playbackService } from '@/services'

type DisplayMode = 'sidebar' | 'full'

const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))

const props = withDefaults(defineProps<{ artist: Artist, mode?: DisplayMode }>(), { mode: 'sidebar' })
const { artist, mode } = toRefs(props)

const showingFullBio = ref(false)

watch(artist, () => (showingFullBio.value = false))

const showSummary = computed(() => mode.value !== 'full' && !showingFullBio.value)
const showFull = computed(() => !showSummary.value)

const shuffleAll = () => playbackService.playAllByArtist(artist.value, false)
</script>

<style lang="scss">
.artist-info {
  @include artist-album-info();

  .none {
    margin-top: 1rem;
  }
}
</style>
