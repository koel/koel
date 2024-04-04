<template>
  <section
    id="mainContent"
    class="flex-1 relative overflow-hidden"
  >
    <!--
      Most of the views are render-expensive and have their own UI states (viewport/scroll position), e.g. the song
      lists), so we use v-show.
      For those that don't need to maintain their own UI state, we use v-if and enjoy some code-splitting juice.
    -->
    <VisualizerScreen v-if="screen === 'Visualizer'" />
    <AlbumArtOverlay v-if="showAlbumArtOverlay && currentSong" :album="currentSong?.album_id" />

    <HomeScreen v-show="screen === 'Home'" />
    <QueueScreen v-show="screen === 'Queue'" />
    <AllSongsScreen v-show="screen === 'Songs'" />
    <AlbumListScreen v-show="screen === 'Albums'" />
    <ArtistListScreen v-show="screen === 'Artists'" />
    <PlaylistScreen v-show="screen === 'Playlist'" />
    <FavoritesScreen v-show="screen === 'Favorites'" />
    <RecentlyPlayedScreen v-show="screen === 'RecentlyPlayed'" />
    <UploadScreen v-show="screen === 'Upload'" />
    <SearchExcerptsScreen v-show="screen === 'Search.Excerpt'" />
    <GenreScreen v-show="screen === 'Genre'" />

    <GenreListScreen v-if="screen === 'Genres'" />
    <SearchSongResultsScreen v-if="screen === 'Search.Songs'" />
    <AlbumScreen v-if="screen === 'Album'" />
    <ArtistScreen v-if="screen === 'Artist'" />
    <SettingsScreen v-if="screen === 'Settings'" />
    <ProfileScreen v-if="screen === 'Profile'" />
    <UserListScreen v-if="screen === 'Users'" />
    <YoutubeScreen v-if="useYouTube" v-show="screen === 'YouTube'" />
    <NotFoundScreen v-if="screen === '404'" />
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref, toRef } from 'vue'
import { requireInjection } from '@/utils'
import { preferenceStore } from '@/stores'
import { useRouter, useThirdPartyServices } from '@/composables'
import { CurrentSongKey } from '@/symbols'

import HomeScreen from '@/components/screens/HomeScreen.vue'
import QueueScreen from '@/components/screens/QueueScreen.vue'
import AlbumListScreen from '@/components/screens/AlbumListScreen.vue'
import ArtistListScreen from '@/components/screens/ArtistListScreen.vue'
import GenreListScreen from '@/components/screens/GenreListScreen.vue'
import AllSongsScreen from '@/components/screens/AllSongsScreen.vue'
import PlaylistScreen from '@/components/screens/PlaylistScreen.vue'
import FavoritesScreen from '@/components/screens/FavoritesScreen.vue'
import RecentlyPlayedScreen from '@/components/screens/RecentlyPlayedScreen.vue'
import UploadScreen from '@/components/screens/UploadScreen.vue'
import SearchExcerptsScreen from '@/components/screens/search/SearchExcerptsScreen.vue'

const UserListScreen = defineAsyncComponent(() => import('@/components/screens/UserListScreen.vue'))
const AlbumArtOverlay = defineAsyncComponent(() => import('@/components/ui/AlbumArtOverlay.vue'))
const AlbumScreen = defineAsyncComponent(() => import('@/components/screens/AlbumScreen.vue'))
const ArtistScreen = defineAsyncComponent(() => import('@/components/screens/ArtistScreen.vue'))
const GenreScreen = defineAsyncComponent(() => import('@/components/screens/GenreScreen.vue'))
const SettingsScreen = defineAsyncComponent(() => import('@/components/screens/SettingsScreen.vue'))
const ProfileScreen = defineAsyncComponent(() => import('@/components/screens/ProfileScreen.vue'))
const YoutubeScreen = defineAsyncComponent(() => import('@/components/screens/YouTubeScreen.vue'))
const SearchSongResultsScreen = defineAsyncComponent(() => import('@/components/screens/search/SearchSongResultsScreen.vue'))
const NotFoundScreen = defineAsyncComponent(() => import('@/components/screens/NotFoundScreen.vue'))
const VisualizerScreen = defineAsyncComponent(() => import('@/components/screens/VisualizerScreen.vue'))

const { useYouTube } = useThirdPartyServices()
const { onRouteChanged, getCurrentScreen } = useRouter()

const currentSong = requireInjection(CurrentSongKey, ref(undefined))

const showAlbumArtOverlay = toRef(preferenceStore.state, 'show_album_art_overlay')
const screen = ref<ScreenName>('Home')

onRouteChanged(route => (screen.value = route.screen))

onMounted(() => (screen.value = getCurrentScreen()))
</script>
