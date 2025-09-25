<template>
  <section
    id="mainContent"
    class="flex-1 relative overflow-hidden"
  >
    <!--
      Most of the views are render-expensive and have their own UI states (viewport/scroll position), e.g. the playable
      lists), so we use v-show.
      For those that don't need to maintain their own UI state, we use v-if to avoid rendering them when not needed.
    -->
    <VisualizerScreen v-if="screen === 'Visualizer'" />
    <ArtOverlay v-if="showArtOverlay" :album="(currentPlayingItem as Song).album_id" />

    <HomeScreen v-if="screenLoaded('Home')" v-show="screen === 'Home'" />
    <QueueScreen v-if="screenLoaded('Queue')" v-show="screen === 'Queue'" />
    <AllSongsScreen v-if="screenLoaded('Songs')" v-show="screen === 'Songs'" />
    <AlbumListScreen v-if="screenLoaded('Albums')" v-show="screen === 'Albums'" />
    <ArtistListScreen v-if="screenLoaded('Artists')" v-show="screen === 'Artists'" />
    <PlaylistScreen v-if="screenLoaded('Playlist')" v-show="screen === 'Playlist'" />
    <FavoritesScreen v-if="screenLoaded('Favorites')" v-show="screen === 'Favorites'" />
    <RecentlyPlayedScreen v-if="screenLoaded('RecentlyPlayed')" v-show="screen === 'RecentlyPlayed'" />
    <UploadScreen v-if="screenLoaded('Upload')" v-show="screen === 'Upload'" />
    <SearchExcerptsScreen v-if="screenLoaded('Search.Excerpt')" v-show="screen === 'Search.Excerpt'" />
    <GenreScreen v-if="screenLoaded('Genre')" v-show="screen === 'Genre'" />
    <PodcastListScreen v-if="screenLoaded('Podcasts')" v-show="screen === 'Podcasts'" />
    <RadioStationListScreen v-if="screenLoaded('Radio.Stations')" v-show="screen === 'Radio.Stations'" />
    <MediaBrowser v-if="useMediaBrowser && screenLoaded('MediaBrowser')" v-show="screen === 'MediaBrowser'" />
    <GenreListScreen v-if="screenLoaded('Genres')" v-show="screen === 'Genres'" />

    <SearchSongResultsScreen v-if="screen === 'Search.Playables'" />
    <AlbumScreen v-if="screen === 'Album'" />
    <ArtistScreen v-if="screen === 'Artist'" />
    <SettingsScreen v-if="screen === 'Settings'" />
    <ProfileScreen v-if="screen === 'Profile'" />
    <PodcastScreen v-if="screen === 'Podcast'" />
    <EpisodeScreen v-if="screen === 'Episode'" />
    <UserListScreen v-if="screen === 'Users'" />
    <YouTubeScreen v-if="useYouTube" v-show="screen === 'YouTube'" />
    <NotFoundScreen v-if="screen === '404'" />
    <AcceptPlaylistCollaborationInvite v-if="screen === 'Playlist.Collaborate'" />
  </section>
</template>

<script lang="ts" setup>
import { computed, onMounted, reactive, ref, toRef } from 'vue'
import { isSong } from '@/utils/typeGuards'
import { defineAsyncComponent, requireInjection } from '@/utils/helpers'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useRouter } from '@/composables/useRouter'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { CurrentStreamableKey } from '@/symbols'
import { commonStore } from '@/stores/commonStore'

const AcceptPlaylistCollaborationInvite = defineAsyncComponent(
  () => import('@/components/screens/AcceptPlaylistCollaborationInvite.vue'),
)

const ArtOverlay = defineAsyncComponent(() => import('@/components/ui/AlbumArtOverlay.vue'))
const AlbumListScreen = defineAsyncComponent(() => import('@/components/screens/AlbumListScreen.vue'))
const AlbumScreen = defineAsyncComponent(() => import('@/components/screens/AlbumScreen.vue'))
const AllSongsScreen = defineAsyncComponent(() => import('@/components/screens/AllSongsScreen.vue'))
const ArtistListScreen = defineAsyncComponent(() => import('@/components/screens/ArtistListScreen.vue'))
const ArtistScreen = defineAsyncComponent(() => import('@/components/screens/ArtistScreen.vue'))
const EpisodeScreen = defineAsyncComponent(() => import('@/components/screens/EpisodeScreen.vue'))
const FavoritesScreen = defineAsyncComponent(() => import('@/components/screens/FavoritesScreen.vue'))
const GenreListScreen = defineAsyncComponent(() => import('@/components/screens/GenreListScreen.vue'))
const GenreScreen = defineAsyncComponent(() => import('@/components/screens/GenreScreen.vue'))
const HomeScreen = defineAsyncComponent(() => import('@/components/screens/HomeScreen.vue'))
const MediaBrowser = defineAsyncComponent(() => import('@/components/screens/MediaBrowserScreen.vue'))
const NotFoundScreen = defineAsyncComponent(() => import('@/components/screens/NotFoundScreen.vue'))
const PlaylistScreen = defineAsyncComponent(() => import('@/components/screens/PlaylistScreen.vue'))
const PodcastListScreen = defineAsyncComponent(() => import('@/components/screens/PodcastListScreen.vue'))
const PodcastScreen = defineAsyncComponent(() => import('@/components/screens/PodcastScreen.vue'))
const ProfileScreen = defineAsyncComponent(() => import('@/components/screens/ProfileScreen.vue'))
const QueueScreen = defineAsyncComponent(() => import('@/components/screens/QueueScreen.vue'))
const RadioStationListScreen = defineAsyncComponent(() => import('@/components/screens/RadioStationListScreen.vue'))
const RecentlyPlayedScreen = defineAsyncComponent(() => import('@/components/screens/RecentlyPlayedScreen.vue'))
const SearchExcerptsScreen = defineAsyncComponent(() => import('@/components/screens/search/SearchExcerptsScreen.vue'))
const SearchSongResultsScreen = defineAsyncComponent(() => import('@/components/screens/search/SearchPlayableResultsScreen.vue'))
const SettingsScreen = defineAsyncComponent(() => import('@/components/screens/SettingsScreen.vue'))
const UploadScreen = defineAsyncComponent(() => import('@/components/screens/UploadScreen.vue'))
const UserListScreen = defineAsyncComponent(() => import('@/components/screens/UserListScreen.vue'))
const VisualizerScreen = defineAsyncComponent(() => import('@/components/screens/VisualizerScreen.vue'))
const YouTubeScreen = defineAsyncComponent(() => import('@/components/screens/YouTubeScreen.vue'))

const { useYouTube } = useThirdPartyServices()
const { onRouteChanged, getCurrentScreen } = useRouter()

const currentPlayingItem = requireInjection(CurrentStreamableKey, ref())

const showArtOverlay = computed(() => {
  if (!preferences.show_album_art_overlay) {
    return false
  }

  return currentPlayingItem.value && isSong(currentPlayingItem.value)
})

const screen = ref<ScreenName>('Home')
const loadedScreens = reactive<ScreenName[]>([])
const useMediaBrowser = toRef(commonStore.state, 'uses_media_browser')

onRouteChanged(route => {
  if (!loadedScreens.includes(route.screen)) {
    loadedScreens.push(route.screen)
  }

  screen.value = route.screen
})

const screenLoaded = (screenName: ScreenName) => loadedScreens.includes(screenName)

onMounted(() => {
  screen.value = getCurrentScreen()
  loadedScreens.push(screen.value)
})
</script>
