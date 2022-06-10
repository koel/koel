<template>
  <article :class="mode" class="album-info" data-testid="album-info">
    <h1 class="name">
      <span>{{ album.name }}</span>
      <button :title="`Play all songs in ${album.name}`" class="control play" @click.prevent="play">
        <i class="fa fa-play"/>
      </button>
    </h1>

    <main>
      <AlbumThumbnail :entity="album"/>

      <template v-if="album.info">
        <div v-if="album.info.wiki?.summary" class="wiki">
          <div v-if="showSummary" class="summary" v-html="album.info.wiki.summary"/>
          <div v-if="showFull" class="full" v-html="album.info.wiki.full"/>

          <button v-if="showSummary" class="more" data-testid="more-btn" @click.prevent="showingFullWiki = true">
            Full Wiki
          </button>
        </div>

        <TrackList v-if="album.info.tracks?.length" :album="album" data-testid="album-info-tracks"/>

        <footer v-if="useLastfm">
          Data &copy;
          <a :href="album.info.url" rel="noopener" target="_blank">Last.fm</a>
        </footer>
      </template>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRefs, watch } from 'vue'
import { useThirdPartyServices } from '@/composables'
import { songStore } from '@/stores'
import { playbackService } from '@/services'

const TrackList = defineAsyncComponent(() => import('./AlbumTrackList.vue'))
const AlbumThumbnail = defineAsyncComponent(() => import('@/components/ui/AlbumArtistThumbnail.vue'))

type DisplayMode = 'aside' | 'full'

const props = withDefaults(defineProps<{ album: Album, mode?: DisplayMode }>(), { mode: 'aside' })
const { album, mode } = toRefs(props)

const showingFullWiki = ref(false)

const { useLastfm } = useThirdPartyServices()

/**
 * Whenever a new album is loaded into this component, we reset the "full wiki" state.
 */
watch(album, () => (showingFullWiki.value = false))

const showSummary = computed(() => mode.value !== 'full' && !showingFullWiki.value)
const showFull = computed(() => !showSummary.value)

const play = async () => playbackService.queueAndPlay(await songStore.fetchForAlbum(album.value))
</script>

<style lang="scss">
.album-info {
  @include artist-album-info();
}
</style>
