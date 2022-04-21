<template>
  <section id="extra" :class="{ showing: state.showExtraPanel }" class="text-secondary" data-testid="extra-panel">
    <div class="tabs">
      <div class="clear" role="tablist">
        <button
          :aria-selected="currentTab === 'Lyrics'"
          @click.prevent="currentTab = 'Lyrics'"
          aria-controls="extraPanelLyrics"
          id="extraTabLyrics"
          role="tab"
        >
          Lyrics
        </button>
        <button
          :aria-selected="currentTab === 'Artist'"
          @click.prevent="currentTab = 'Artist'"
          aria-controls="extraPanelArtist"
          id="extraTabArtist"
          role="tab"
        >
          Artist
        </button>
        <button
          :aria-selected="currentTab === 'Album'"
          @click.prevent="currentTab = 'Album'"
          aria-controls="extraPanelAlbum"
          id="extraTabAlbum"
          role="tab"
        >
          Album
        </button>
        <button
          :aria-selected="currentTab === 'YouTube'"
          @click.prevent="currentTab = 'YouTube'"
          aria-controls="extraPanelYouTube"
          id="extraTabYouTube"
          role="tab"
          title="YouTube"
          v-if="sharedState.useYouTube"
        >
          <i class="fa fa-youtube-play"></i>
        </button>
      </div>

      <div class="panes">
        <div
          aria-labelledby="extraTabLyrics"
          id="extraPanelLyrics"
          role="tabpanel"
          tabindex="0"
          v-show="currentTab === 'Lyrics'"
        >
          <lyrics-pane :song="song"/>
        </div>

        <div
          aria-labelledby="extraTabArtist"
          id="extraPanelArtist"
          role="tabpanel"
          tabindex="0"
          v-show="currentTab === 'Artist'"
        >
          <ArtistInfo v-if="artist" :artist="artist" mode="sidebar"/>
        </div>

        <div
          aria-labelledby="extraTabAlbum"
          id="extraPanelAlbum"
          role="tabpanel"
          tabindex="0"
          v-show="currentTab === 'Album'"
        >
          <AlbumInfo v-if="album" :album="album" mode="sidebar"/>
        </div>

        <div
          aria-labelledby="extraTabYouTube"
          id="extraPanelYouTube"
          role="tabpanel"
          tabindex="0"
          v-show="currentTab === 'YouTube'"
        >
          <YouTubeVideoList v-if="sharedState.useYouTube && song" :song="song"/>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { computed, defineAsyncComponent, reactive, ref, watch } from 'vue'
import { $, eventBus } from '@/utils'
import { preferenceStore as preferences, sharedStore, songStore } from '@/stores'
import { songInfo } from '@/services'

type Tab = 'Lyrics' | 'Artist' | 'Album' | 'YouTube'
const defaultTab: Tab = 'Lyrics'

const LyricsPane = defineAsyncComponent(() => import('@/components/ui/LyricsPane.vue'))
const ArtistInfo = defineAsyncComponent(() => import('@/components/artist/info.vue'))
const AlbumInfo = defineAsyncComponent(() => import('@/components/album/AlbumInfo.vue'))
const YouTubeVideoList = defineAsyncComponent(() => import('@/components/ui/youtube-video-list.vue'))

const song = ref<Song | null>(null)
const state = reactive(preferences.state)
const sharedState = reactive(sharedStore.state)
const currentTab = ref(defaultTab)

const artist = computed(() => song.value?.artist)
const album = computed(() => song.value?.album)

watch(() => state.showExtraPanel, (showingExtraPanel) => {
  if (showingExtraPanel && !isMobile.any) {
    $.addClass(document.documentElement, 'with-extra-panel')
  } else {
    $.removeClass(document.documentElement, 'with-extra-panel')
  }
})

const resetState = () => {
  currentTab.value = defaultTab
  song.value = songStore.stub
}

const fetchSongInfo = async (_song: Song) =>{
  try {
    song.value = await songInfo.fetch(_song)
  } catch (err) {
    song.value = _song
    throw err
  }
}

eventBus.on({
  'SONG_STARTED': async (song: Song): Promise<void> => await fetchSongInfo(song),
  'LOAD_MAIN_CONTENT': (): void => {
    // On ready, add 'with-extra-panel' class.
    isMobile.any || $.addClass(document.documentElement, 'with-extra-panel')

    // Hide the extra panel if on mobile
    isMobile.phone && (state.showExtraPanel = false)
  }
})
</script>

<style lang="scss">
#extra {
  flex: 0 0 var(--extra-panel-width);
  padding-top: 2.3rem;
  background: var(--color-bg-secondary);
  display: none;
  overflow: auto;
  -ms-overflow-style: -ms-autohiding-scrollbar;

  @media (hover: none) {
    // Enable scroll with momentum on touch devices
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
  }

  &.showing {
    display: block;
  }

  h1 {
    font-weight: var(--font-weight-thin);
    font-size: 2.2rem;
    margin-bottom: 1.25rem;
    line-height: 2.8rem;
  }

  @media only screen and (max-width: 1024px) {
    position: fixed;
    height: calc(100vh - var(--header-height));
    width: var(--extra-panel-width);
    z-index: 5;
    top: var(--header-height);
    right: -100%;
    transition: right .3s ease-in;

    &.showing {
      right: 0;
    }
  }

  @media only screen and (max-width: 667px) {
    @include themed-background();

    width: 100%;

    [role=tabpanel] {
      padding-bottom: calc(var(--footer-height-mobile) + 1rem)
    }
  }
}
</style>
