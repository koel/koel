<template>
  <article class="artist-info" :class="mode" data-test="artist-info">
    <h1 class="name">
      <span>{{ artist.name }}</span>
      <button :title="`Shuffle all songs by ${artist.name}`" @click.prevent="shuffleAll" class="shuffle control">
        <i class="fa fa-random"></i>
      </button>
    </h1>

    <main v-if="artist.info">
      <ArtistThumbnail :entity="artist"/>

      <template v-if="artist.info">
        <div class="bio" v-if="artist.info.bio?.summary">
          <div class="summary" v-if="showSummary" v-html="artist.info.bio.summary"></div>
          <div class="full" v-if="showFull" v-html="artist.info.bio.full"></div>

          <button class="more" v-show="showSummary" @click.prevent="showingFullBio = true" data-test="more-btn">
            Full Bio
          </button>
        </div>
        <p class="text-secondary none" v-else>This artist has no Last.fm biography – yet.</p>

        <footer>Data &copy; <a target="_blank" rel="openener" :href="artist.info.url">Last.fm</a></footer>
      </template>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { playback } from '@/services'

type DisplayMode = 'sidebar' | 'full'

const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))

const props = withDefaults(defineProps<{ artist: Artist, mode: DisplayMode }>(), { mode: 'sidebar' })
const { artist, mode } = toRefs(props)

const showingFullBio = ref(false)

watch(artist, () => (showingFullBio.value = false))

const showSummary = computed(() => mode.value !== 'full' && !showingFullBio)
const showFull = computed(() => !showSummary.value)

const shuffleAll = () => playback.playAllByArtist(artist.value, false)
</script>

<style lang="scss">
.artist-info {
  @include artist-album-info();

  .none {
    margin-top: 1rem;
  }
}
</style>
