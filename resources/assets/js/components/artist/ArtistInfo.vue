<template>
  <article :class="mode" class="artist-info" data-testid="artist-info">
    <h1 class="name">
      <span>{{ artist.name }}</span>
      <button :title="`Play all songs by ${artist.name}`" class="play control" type="button" @click.prevent="play">
        <i class="fa fa-play"/>
      </button>
    </h1>

    <main>
      <ArtistThumbnail :entity="artist"/>

      <template v-if="info">
        <div v-if="info.bio?.summary" class="bio">
          <div v-if="showSummary" class="summary" data-testid="summary" v-html="info.bio.summary"/>
          <div v-if="showFull" class="full" data-testid="full" v-html="info.bio.full"/>

          <button v-if="showSummary" class="more" data-testid="more-btn" @click.prevent="showingFullBio = true">
            Full Bio
          </button>
        </div>

        <footer>
          Data &copy;
          <a :href="info.url" rel="openener" target="_blank">Last.fm</a>
        </footer>
      </template>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, ref, toRefs, watch } from 'vue'
import { playbackService } from '@/services'
import { useThirdPartyServices } from '@/composables'
import { songStore } from '@/stores'
import { mediaInfoService } from '@/services/mediaInfoService'

import ArtistThumbnail from '@/components/ui/AlbumArtistThumbnail.vue'

const props = withDefaults(defineProps<{ artist: Artist, mode?: MediaInfoDisplayMode }>(), { mode: 'aside' })
const { artist, mode } = toRefs(props)

const { useLastfm } = useThirdPartyServices()

const info = ref<ArtistInfo | null>(null)
const showingFullBio = ref(false)

watch(artist, async () => {
  showingFullBio.value = false
  info.value = null
  useLastfm.value && (info.value = await mediaInfoService.fetchForArtist(artist.value))
}, { immediate: true })

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
