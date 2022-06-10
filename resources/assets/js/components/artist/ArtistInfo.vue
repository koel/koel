<template>
  <article :class="mode" class="artist-info" data-testid="artist-info">
    <h1 class="name">
      <span>{{ artist.name }}</span>
      <button :title="`Play all songs by ${artist.name}`" class="play control" @click.prevent="play">
        <i class="fa fa-play"/>
      </button>
    </h1>

    <main v-if="artist.info">
      <ArtistThumbnail :entity="artist"/>

      <template v-if="artist.info">
        <div v-if="artist.info.bio?.summary" class="bio">
          <div v-if="showSummary" class="summary" v-html="artist.info.bio.summary"/>
          <div v-if="showFull" class="full" v-html="artist.info.bio.full"/>

          <button v-show="showSummary" class="more" data-testid="more-btn" @click.prevent="showingFullBio = true">
            Full Bio
          </button>
        </div>

        <footer v-if="useLastfm">
          Data &copy;
          <a :href="artist.info.url" rel="openener" target="_blank">Last.fm</a>
        </footer>
      </template>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { playbackService } from '@/services'
import { useThirdPartyServices } from '@/composables'
import { songStore } from '@/stores'

type DisplayMode = 'aside' | 'full'

const ArtistThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))

const props = withDefaults(defineProps<{ artist: Artist, mode?: DisplayMode }>(), { mode: 'aside' })
const { artist, mode } = toRefs(props)

const showingFullBio = ref(false)

const { useLastfm } = useThirdPartyServices()

watch(artist, () => (showingFullBio.value = false))

const showSummary = computed(() => mode.value !== 'full' && !showingFullBio.value)
const showFull = computed(() => !showSummary.value)

const play = async () => playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value))
</script>

<style lang="scss">
.artist-info {
  @include artist-album-info();

  .none {
    margin-top: 1rem;
  }
}
</style>
