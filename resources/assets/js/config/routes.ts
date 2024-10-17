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
    path: '/home',
    screen: 'Home',
  },
  {
    path: '/404',
    screen: '404',
  },
  {
    path: '/queue',
    screen: 'Queue',
  },
  {
    path: '/songs',
    screen: 'Songs',
  },
  {
    path: '/albums',
    screen: 'Albums',
  },
  {
    path: '/artists',
    screen: 'Artists',
  },
  {
    path: '/favorites',
    screen: 'Favorites',
  },
  {
    path: '/recently-played',
    screen: 'RecentlyPlayed',
  },
  {
    path: '/search',
    screen: 'Search.Excerpt',
  },
  {
    path: '/search/songs',
    screen: 'Search.Songs',
  },
  {
    path: '/upload',
    screen: 'Upload',
    onResolve: () => useUpload().allowsUpload.value,
  },
  {
    path: '/settings',
    screen: 'Settings',
    onResolve: () => userStore.current?.is_admin,
  },
  {
    path: '/users',
    screen: 'Users',
    onResolve: () => userStore.current?.is_admin,
  },
  {
    path: '/youtube',
    screen: 'YouTube',
  },
  {
    path: '/profile',
    screen: 'Profile',
  },
  {
    path: 'visualizer',
    screen: 'Visualizer',
  },
  {
    path: '/album/(?<id>\\d+)',
    screen: 'Album',
  },
  {
    path: '/artist/(?<id>\\d+)',
    screen: 'Artist',
  },
  {
    path: `/playlist/(?<id>${UUID_REGEX})`,
    screen: 'Playlist',
  },
  {
    path: `/playlist/collaborate/(?<id>${UUID_REGEX})`,
    screen: 'Blank',
    onResolve: async params => {
      try {
        const playlist = await playlistCollaborationService.acceptInvite(params.id)
        Router.go(`/playlist/${playlist.id}`, true)
        return true
      } catch (error: unknown) {
        logger.error(error)
        return false
      }
    },
  },
  {
    path: '/genres',
    screen: 'Genres',
  },
  {
    path: '/genres/(?<name>\.+)',
    screen: 'Genre',
  },
  {
    path: '/podcasts',
    screen: 'Podcasts',
  },
  {
    path: `/podcasts/(?<id>${UUID_REGEX})`,
    screen: 'Podcast',
  },
  {
    path: '/episodes/(?<id>\.+)',
    screen: 'Episode',
  },
  {
    path: '/visualizer',
    screen: 'Visualizer',
  },
  {
    path: `/song/(?<id>${UUID_REGEX})`,
    screen: 'Queue',
    redirect: () => 'queue',
    onResolve: params => {
      cache.set('song-to-queue', params.id)
      return true
    },
  },
  {
    path: `/invitation/accept/(?<token>${UUID_REGEX})`,
    screen: 'Invitation.Accept',
  },
  {
    path: `/reset-password/(?<payload>[a-zA-Z0-9\\+/=]+)`,
    screen: 'Password.Reset',
  },
]
