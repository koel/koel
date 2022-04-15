<template>
  <section id="mainContent">
    <!--
      Most of the views are render-expensive and have their own UI states (viewport/scroll position), e.g. the song
      lists), so we use v-show.
      For those that don't need to maintain their own UI state, we use v-if and enjoy some codesplitting juice.
    -->
    <visualizer v-if="showingVisualizer"/>
    <album-art-overlay :song="currentSong" v-if="preferences.showAlbumArtOverlay"/>

    <home-screen v-show="view === 'Home'"/>
    <queue-screen v-show="view === 'Queue'"/>
    <all-songs-screen v-show="view === 'Songs'"/>
    <album-list-screen v-show="view === 'Albums'"/>
    <artist-list-screen v-show="view === 'Artists'"/>
    <playlist-screen v-show="view === 'Playlist'"/>
    <favorites-screen v-show="view === 'Favorites'"/>
    <recently-played-screen v-show="view === 'RecentlyPlayed'"/>
    <upload-screen v-show="view === 'Upload'"/>
    <search-excerpts-screen v-show="view === 'Search.Excerpt'"/>

    <search-song-results-screen v-if="view === 'Search.Songs'" :q="screenProps" />
    <album-screen v-if="view === 'Album'" :album="screenProps"/>
    <artist-screen v-if="view === 'Artist'" :artist="screenProps"/>
    <settings-screen v-if="view === 'Settings'"/>
    <profile-screen v-if="view === 'Profile'"/>
    <user-list-screen v-if="view === 'Users'"/>
    <youtube-screen v-if="sharedState.useYouTube" v-show="view === 'YouTube'"/>
  </section>
</template>

<script lang="ts">
import Vue from 'vue'
import { eventBus } from '@/utils'
import { preferenceStore, sharedStore } from '@/stores'
import HomeScreen from '@/components/screens/home.vue'
import QueueScreen from '@/components/screens/queue.vue'
import AlbumListScreen from '@/components/screens/album-list.vue'
import ArtistListScreen from '@/components/screens/artist-list.vue'
import AllSongsScreen from '@/components/screens/all-songs.vue'
import PlaylistScreen from '@/components/screens/playlist.vue'
import FavoritesScreen from '@/components/screens/favorites.vue'

export default Vue.extend({
  components: {
    HomeScreen,
    QueueScreen,
    AllSongsScreen,
    AlbumListScreen,
    ArtistListScreen,
    PlaylistScreen,
    FavoritesScreen,
    RecentlyPlayedScreen: () => import('@/components/screens/recently-played.vue'),
    UserListScreen: () => import('@/components/screens/user-list.vue'),
    AlbumArtOverlay: () => import('@/components/ui/album-art-overlay.vue'),
    AlbumScreen: () => import('@/components/screens/album.vue'),
    ArtistScreen: () => import('@/components/screens/artist.vue'),
    SettingsScreen: () => import('@/components/screens/settings.vue'),
    ProfileScreen: () => import('@/components/screens/profile.vue'),
    YoutubeScreen: () => import('@/components/screens/youtube.vue'),
    UploadScreen: () => import('@/components/screens/upload.vue'),
    SearchExcerptsScreen: () => import('@/components/screens/search/excerpts.vue'),
    SearchSongResultsScreen: () => import('@/components/screens/search/song-results.vue'),
    Visualizer: () => import('@/components/ui/visualizer.vue')
  },

  data: () => ({
    preferences: preferenceStore.state,
    sharedState: sharedStore.state,
    showingVisualizer: false,
    screenProps: null,
    view: 'Home' as MainViewName,
    currentSong: null as Song | null
  }),

  created (): void {
    eventBus.on({
      'LOAD_MAIN_CONTENT': (view: MainViewName, data: any): void => {
        this.screenProps = data
        this.view = view
      },

      'TOGGLE_VISUALIZER': (): void => {
        this.showingVisualizer = !this.showingVisualizer
      },

      'SONG_STARTED': (song: Song): void => {
        this.currentSong = song
      }
    })
  }
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
