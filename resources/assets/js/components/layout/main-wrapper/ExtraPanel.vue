<template>
  <section id="extra" :class="{ showing }" class="text-secondary" data-testid="extra-panel">
    <div class="tabs">
      <div class="clear" role="tablist">
        <button
          id="extraTabLyrics"
          :aria-selected="currentTab === 'Lyrics'"
          aria-controls="extraPanelLyrics"
          data-testid="extra-tab-lyrics"
          role="tab"
          type="button"
          @click.prevent="currentTab = 'Lyrics'"
        >
          Lyrics
        </button>
        <button
          id="extraTabArtist"
          :aria-selected="currentTab === 'Artist'"
          aria-controls="extraPanelArtist"
          data-testid="extra-tab-artist"
          role="tab"
          type="button"
          @click.prevent="currentTab = 'Artist'"
        >
          Artist
        </button>
        <button
          id="extraTabAlbum"
          :aria-selected="currentTab === 'Album'"
          aria-controls="extraPanelAlbum"
          data-testid="extra-tab-album"
          role="tab"
          type="button"
          @click.prevent="currentTab = 'Album'"
        >
          Album
        </button>
        <button
          v-if="useYouTube"
          id="extraTabYouTube"
          :aria-selected="currentTab === 'YouTube'"
          aria-controls="extraPanelYouTube"
          data-testid="extra-tab-youtube"
          role="tab"
          title="YouTube"
          type="button"
          @click.prevent="currentTab = 'YouTube'"
        >
          <icon :icon="faYoutube"/>
        </button>
      </div>

      <div class="panes">
        <div
          v-show="currentTab === 'Lyrics'"
          id="extraPanelLyrics"
          aria-labelledby="extraTabLyrics"
          role="tabpanel"
          tabindex="0"
        >
          <LyricsPane :song="song"/>
        </div>

        <div
          v-show="currentTab === 'Artist'"
          id="extraPanelArtist"
          aria-labelledby="extraTabArtist"
          role="tabpanel"
          tabindex="0"
        >
          <ArtistInfo v-if="artist" :artist="artist" mode="aside"/>
        </div>

        <div
          v-show="currentTab === 'Album'"
          id="extraPanelAlbum"
          aria-labelledby="extraTabAlbum"
          role="tabpanel"
          tabindex="0"
        >
          <AlbumInfo v-if="album" :album="album" mode="aside"/>
        </div>

        <div
          v-show="currentTab === 'YouTube'"
          id="extraPanelYouTube"
          aria-labelledby="extraTabYouTube"
          role="tabpanel"
          tabindex="0"
        >
          <YouTubeVideoList v-if="useYouTube && song" :song="song"/>
        </div>
      </div>
    </div>
  </section>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { ref, toRef, watch } from 'vue'
import { eventBus } from '@/utils'
import { albumStore, artistStore, preferenceStore as preferences } from '@/stores'
import { useThirdPartyServices } from '@/composables'

import LyricsPane from '@/components/ui/LyricsPane.vue'
import ArtistInfo from '@/components/artist/ArtistInfo.vue'
import AlbumInfo from '@/components/album/AlbumInfo.vue'
import YouTubeVideoList from '@/components/ui/YouTubeVideoList.vue'

type Tab = 'Lyrics' | 'Artist' | 'Album' | 'YouTube'
const defaultTab: Tab = 'Lyrics'

const song = ref<Song | null>(null)
const showing = toRef(preferences.state, 'showExtraPanel')
const currentTab = ref<Tab>(defaultTab)

const { useYouTube } = useThirdPartyServices()

const artist = ref<Artist>()
const album = ref<Album>()

watch(showing, (showingExtraPanel) => {
  if (showingExtraPanel && !isMobile.any) {
    document.documentElement.classList.add('with-extra-panel')
  } else {
    document.documentElement.classList.remove('with-extra-panel')
  }
})

const fetchSongInfo = async (_song: Song) => {
  try {
    song.value = _song
    artist.value = await artistStore.resolve(_song.artist_id)
    album.value = await albumStore.resolve(_song.album_id)
  } catch (err) {
    throw err
  }
}

eventBus.on({
  SONG_STARTED: async (song: Song) => await fetchSongInfo(song),
  KOEL_READY: () => {
    // On ready, add 'with-extra-panel' class.
    isMobile.any || document.documentElement.classList.add('with-extra-panel')

    // Hide the extra panel if on mobile
    isMobile.phone && (showing.value = false)
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
    z-index: 9;
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
