<template>
  <section id="mainContent">
    <!--
      Most of the views are render-expensive and have their own UI states (viewport/scroll position), e.g. the song
      lists), so we use v-show.
      For those that don't need to maintain their own UI state, we use v-if and enjoy some code-splitting juice.
    -->
    <Visualizer v-if="showingVisualizer"/>
    <AlbumArtOverlay :song="currentSong" v-if="preferences.showAlbumArtOverlay"/>

    <HomeScreen v-show="view === 'Home'"/>
    <QueueScreen v-show="view === 'Queue'"/>
    <AllSongsScreen v-show="view === 'Songs'"/>
    <AlbumListScreen v-show="view === 'Albums'"/>
    <ArtistListScreen v-show="view === 'Artists'"/>
    <PlaylistScreen v-show="view === 'Playlist'"/>
    <FavoritesScreen v-show="view === 'Favorites'"/>
    <RecentlyPlayedScreen v-show="view === 'RecentlyPlayed'"/>
    <UploadScreen v-show="view === 'Upload'"/>
    <SearchExcerptsScreen v-show="view === 'Search.Excerpt'"/>

    <SearchSongResultsScreen v-if="view === 'Search.Songs'" :q="screenProps"/>
    <AlbumScreen v-if="view === 'Album'" :album="screenProps"/>
    <ArtistScreen v-if="view === 'Artist'" :artist="screenProps"/>
    <SettingsScreen v-if="view === 'Settings'"/>
    <ProfileScreen v-if="view === 'Profile'"/>
    <UserListScreen v-if="view === 'Users'"/>
    <YoutubeScreen v-if="sharedState.useYouTube" v-show="view === 'YouTube'"/>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, reactive, ref } from 'vue'
import { eventBus } from '@/utils'
import { preferenceStore, sharedStore } from '@/stores'
import HomeScreen from '@/components/screens/home.vue'
import QueueScreen from '@/components/screens/queue.vue'
import AlbumListScreen from '@/components/screens/album-list.vue'
import ArtistListScreen from '@/components/screens/artist-list.vue'
import AllSongsScreen from '@/components/screens/all-songs.vue'
import PlaylistScreen from '@/components/screens/playlist.vue'
import FavoritesScreen from '@/components/screens/favorites.vue'

const RecentlyPlayedScreen = defineAsyncComponent(() => import('@/components/screens/recently-played.vue'))
const UserListScreen = defineAsyncComponent(() => import('@/components/screens/user-list.vue'))
const AlbumArtOverlay = defineAsyncComponent(() => import('@/components/ui/album-art-overlay.vue'))
const AlbumScreen = defineAsyncComponent(() => import('@/components/screens/album.vue'))
const ArtistScreen = defineAsyncComponent(() => import('@/components/screens/artist.vue'))
const SettingsScreen = defineAsyncComponent(() => import('@/components/screens/settings.vue'))
const ProfileScreen = defineAsyncComponent(() => import('@/components/screens/profile.vue'))
const YoutubeScreen = defineAsyncComponent(() => import('@/components/screens/youtube.vue'))
const UploadScreen = defineAsyncComponent(() => import('@/components/screens/upload.vue'))
const SearchExcerptsScreen = defineAsyncComponent(() => import('@/components/screens/search/excerpts.vue'))
const SearchSongResultsScreen = defineAsyncComponent(() => import('@/components/screens/search/song-results.vue'))
const Visualizer = defineAsyncComponent(() => import('@/components/ui/Visualizer.vue'))

const preferences = reactive(preferenceStore.state)
const sharedState = reactive(sharedStore.state)
const showingVisualizer = ref(false)
const screenProps = ref<any>(null)
const view = ref<MainViewName>('Home')
const currentSong = ref<Song | null>(null)

eventBus.on({
  LOAD_MAIN_CONTENT (_view: MainViewName, data: any) {
    screenProps.value = data
    view.value = _view
  },

  'TOGGLE_VISUALIZER': () => (showingVisualizer.value = !showingVisualizer.value),
  'SONG_STARTED': (song: Song) => (currentSong.value = song)
})
</script>

<style lang="scss">
#mainContent {
  flex: 1;
  position: relative;
  overflow: hidden;

  > section {
    position: absolute;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    backface-visibility: hidden;

    .main-scroll-wrap {
      padding: 24px 24px 48px;
      overflow: auto;
      flex: 1;
      -ms-overflow-style: -ms-autohiding-scrollbar;
      place-content: start;

      @media (hover: none) {
        // Enable scroll with momentum on touch devices
        overflow-y: scroll;
        -webkit-overflow-scrolling: touch;
      }
    }
  }

  @media only screen and (max-width: 375px) {
    > section {
      // Leave some space for the "Back to top" button
      .main-scroll-wrap {
        padding-bottom: 64px;
      }
    }
  }
}
</style>
