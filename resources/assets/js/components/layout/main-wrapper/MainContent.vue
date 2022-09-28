<template>
  <section id="mainContent">
    <!--
      Most of the views are render-expensive and have their own UI states (viewport/scroll position), e.g. the song
      lists), so we use v-show.
      For those that don't need to maintain their own UI state, we use v-if and enjoy some code-splitting juice.
    -->
    <Visualizer v-if="showingVisualizer"/>
    <AlbumArtOverlay v-if="showAlbumArtOverlay && currentSong" :album="currentSong?.album_id"/>

    <HomeScreen v-show="screen === 'Home'"/>
    <QueueScreen v-show="screen === 'Queue'"/>
    <AllSongsScreen v-show="screen === 'Songs'"/>
    <AlbumListScreen v-show="screen === 'Albums'"/>
    <ArtistListScreen v-show="screen === 'Artists'"/>
    <PlaylistScreen v-show="screen === 'Playlist'"/>
    <FavoritesScreen v-show="screen === 'Favorites'"/>
    <RecentlyPlayedScreen v-show="screen === 'RecentlyPlayed'"/>
    <UploadScreen v-show="screen === 'Upload'"/>
    <SearchExcerptsScreen v-show="screen === 'Search.Excerpt'"/>

    <SearchSongResultsScreen v-if="screen === 'Search.Songs'" :q="screenProps"/>
    <AlbumScreen v-if="screen === 'Album'" :album="screenProps"/>
    <ArtistScreen v-if="screen === 'Artist'" :artist="screenProps"/>
    <SettingsScreen v-if="screen === 'Settings'"/>
    <ProfileScreen v-if="screen === 'Profile'"/>
    <UserListScreen v-if="screen === 'Users'"/>
    <YoutubeScreen v-if="useYouTube" v-show="screen === 'YouTube'"/>
    <NotFoundScreen v-if="screen === '404'"/>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref, toRef } from 'vue'
import { eventBus } from '@/utils'
import { preferenceStore } from '@/stores'
import { useThirdPartyServices } from '@/composables'

import HomeScreen from '@/components/screens/HomeScreen.vue'
import QueueScreen from '@/components/screens/QueueScreen.vue'
import AlbumListScreen from '@/components/screens/AlbumListScreen.vue'
import ArtistListScreen from '@/components/screens/ArtistListScreen.vue'
import AllSongsScreen from '@/components/screens/AllSongsScreen.vue'
import PlaylistScreen from '@/components/screens/PlaylistScreen.vue'
import FavoritesScreen from '@/components/screens/FavoritesScreen.vue'
import RecentlyPlayedScreen from '@/components/screens/RecentlyPlayedScreen.vue'
import UploadScreen from '@/components/screens/UploadScreen.vue'
import SearchExcerptsScreen from '@/components/screens/search/SearchExcerptsScreen.vue'
import router from '@/router'

const UserListScreen = defineAsyncComponent(() => import('@/components/screens/UserListScreen.vue'))
const AlbumArtOverlay = defineAsyncComponent(() => import('@/components/ui/AlbumArtOverlay.vue'))
const AlbumScreen = defineAsyncComponent(() => import('@/components/screens/AlbumScreen.vue'))
const ArtistScreen = defineAsyncComponent(() => import('@/components/screens/ArtistScreen.vue'))
const SettingsScreen = defineAsyncComponent(() => import('@/components/screens/SettingsScreen.vue'))
const ProfileScreen = defineAsyncComponent(() => import('@/components/screens/ProfileScreen.vue'))
const YoutubeScreen = defineAsyncComponent(() => import('@/components/screens/YouTubeScreen.vue'))
const SearchSongResultsScreen = defineAsyncComponent(() => import('@/components/screens/search/SearchSongResultsScreen.vue'))
const NotFoundScreen = defineAsyncComponent(() => import('@/components/screens/NotFoundScreen.vue'))
const Visualizer = defineAsyncComponent(() => import('@/components/ui/Visualizer.vue'))

const { useYouTube } = useThirdPartyServices()

const showAlbumArtOverlay = toRef(preferenceStore.state, 'showAlbumArtOverlay')
const showingVisualizer = ref(false)
const screenProps = ref<any>(null)
const screen = ref<ScreenName>('Home')
const currentSong = ref<Song | null>(null)

eventBus.on({
  ACTIVATE_SCREEN (screenName: ScreenName, data: any) {
    screenProps.value = data
    screen.value = screenName
  },

  TOGGLE_VISUALIZER: () => (showingVisualizer.value = !showingVisualizer.value),
  SONG_STARTED: (song: Song) => (currentSong.value = song)
})

onMounted(() => router.resolveRoute())
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
      &:not(.song-list-wrap) {
        padding: 24px 24px 48px;
      }

      overflow: scroll;

      @supports (scrollbar-gutter: stable) {
        overflow: auto;
        scrollbar-gutter: stable;

        @media (hover: none) {
          scrollbar-gutter: auto;
        }
      }

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
