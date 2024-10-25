import type { Route } from '@/router'
import Router from '@/router'
import { userStore } from '@/stores/userStore'
import { cache } from '@/services/cache'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import { useUpload } from '@/composables/useUpload'
import { logger } from '@/utils/logger'

const UUID_REGEX = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'

export const routes: Route[] = [
  {
    name: 'home',
    path: '/home',
    screen: 'Home',
  },
  {
    name: '404',
    path: '/404',
    screen: '404',
  },
  {
    name: 'queue',
    path: '/queue',
    screen: 'Queue',
  },
  {
    name: 'songs.index',
    path: '/songs',
    screen: 'Songs',
  },
  {
    name: 'albums.index',
    path: '/albums',
    screen: 'Albums',
  },
  {
    name: 'artists.index',
    path: '/artists',
    screen: 'Artists',
  },
  {
    name: 'favorites',
    path: '/favorites',
    screen: 'Favorites',
  },
  {
    name: 'recently-played',
    path: '/recently-played',
    screen: 'RecentlyPlayed',
  },
  {
    name: 'search',
    path: '/search',
    screen: 'Search.Excerpt',
  },
  {
    name: 'search.songs',
    path: '/search/songs',
    screen: 'Search.Songs',
  },
  {
    name: 'upload',
    path: '/upload',
    screen: 'Upload',
    onResolve: () => useUpload().allowsUpload.value,
  },
  {
    name: 'settings',
    path: '/settings',
    screen: 'Settings',
    onResolve: () => userStore.current?.is_admin,
  },
  {
    name: 'users.index',
    path: '/users',
    screen: 'Users',
    onResolve: () => userStore.current?.is_admin,
  },
  {
    name: 'youtube',
    path: '/youtube',
    screen: 'YouTube',
  },
  {
    name: 'profile',
    path: '/profile',
    screen: 'Profile',
  },
  {
    name: 'visualizer',
    path: 'visualizer',
    screen: 'Visualizer',
  },
  {
    name: 'albums.show',
    path: '/albums/(?<id>\\d+)',
    screen: 'Album',
  },
  {
    name: 'artists.show',
    path: '/artists/(?<id>\\d+)',
    screen: 'Artist',
  },
  {
    name: 'playlists.show',
    path: `/playlists/(?<id>${UUID_REGEX})`,
    screen: 'Playlist',
  },
  {
    name: 'playlist.collaborate',
    path: `/playlist/collaborate/(?<id>${UUID_REGEX})`,
    screen: 'Blank',
    onResolve: async params => {
      try {
        const playlist = await playlistCollaborationService.acceptInvite(params.id)
        Router.go(Router.url('playlists.show', { id: playlist.id }), true)
        return true
      } catch (error: unknown) {
        logger.error(error)
        return false
      }
    },
  },
  {
    name: 'genres.index',
    path: '/genres',
    screen: 'Genres',
  },
  {
    name: 'genres.show',
    path: '/genres/(?<name>\.+)',
    screen: 'Genre',
  },
  {
    name: 'podcasts.index',
    path: '/podcasts',
    screen: 'Podcasts',
  },
  {
    name: 'podcasts.show',
    path: `/podcasts/(?<id>${UUID_REGEX})`,
    screen: 'Podcast',
  },
  {
    name: 'episodes.show',
    path: '/episodes/(?<id>\.+)',
    screen: 'Episode',
  },
  {
    name: 'visualizer',
    path: '/visualizer',
    screen: 'Visualizer',
  },
  {
    name: 'songs.queue',
    path: `/songs/(?<id>${UUID_REGEX})`,
    screen: 'Queue',
    redirect: () => 'queue',
    onResolve: params => {
      cache.set('song-to-queue', params.id)
      return true
    },
  },
  {
    name: 'invitation.accept',
    path: `/invitation/accept/(?<token>${UUID_REGEX})`,
    screen: 'Invitation.Accept',
  },
  {
    name: 'password.reset',
    path: `/reset-password/(?<payload>[a-zA-Z0-9\\+/=]+)`,
    screen: 'Password.Reset',
  },
]
