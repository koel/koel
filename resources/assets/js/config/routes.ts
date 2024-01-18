import { Route } from '@/router'
import { userStore } from '@/stores'
import { localStorageService, playlistCollaborationService } from '@/services'
import { useUpload } from '@/composables'
import { forceReloadWindow, logger } from '@/utils'
import Router from '@/router'

const UUID_REGEX = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'

export const routes: Route[] = [
  {
    path: '/home',
    screen: 'Home'
  },
  {
    path: '/404',
    screen: '404'
  },
  {
    path: '/queue',
    screen: 'Queue'
  },
  {
    path: '/songs',
    screen: 'Songs'
  },
  {
    path: '/albums',
    screen: 'Albums'
  },
  {
    path: '/artists',
    screen: 'Artists'
  },
  {
    path: '/favorites',
    screen: 'Favorites'
  },
  {
    path: '/recently-played',
    screen: 'RecentlyPlayed'
  },
  {
    path: '/search',
    screen: 'Search.Excerpt'
  },
  {
    path: '/search/songs',
    screen: 'Search.Songs'
  },
  {
    path: '/upload',
    screen: 'Upload',
    onResolve: () => useUpload().allowsUpload.value
  },
  {
    path: '/settings',
    screen: 'Settings',
    onResolve: () => userStore.current?.is_admin
  },
  {
    path: '/users',
    screen: 'Users',
    onResolve: () => userStore.current?.is_admin
  },
  {
    path: '/youtube',
    screen: 'YouTube'
  },
  {
    path: '/profile',
    screen: 'Profile'
  },
  {
    path: 'visualizer',
    screen: 'Visualizer'
  },
  {
    path: '/album/(?<id>\\d+)',
    screen: 'Album'
  },
  {
    path: '/artist/(?<id>\\d+)',
    screen: 'Artist'
  },
  {
    path: `/playlist/(?<id>${UUID_REGEX})`,
    screen: 'Playlist'
  },
  {
    path: `/playlist/collaborate/(?<id>${UUID_REGEX})`,
    screen: 'Blank',
    onResolve: async params => {
      try {
        const playlist = await playlistCollaborationService.acceptInvite(params.id)
        Router.go(`/playlist/${playlist.id}`, true)
        return true
      } catch (e) {
        logger.error(e)
        return false
      }
    }
  },
  {
    path: '/genres',
    screen: 'Genres'
  },
  {
    path: '/genres/(?<name>\.+)',
    screen: 'Genre'
  },
  {
    path: '/visualizer',
    screen: 'Visualizer'
  },
  {
    path: `/song/(?<id>${UUID_REGEX})`,
    screen: 'Queue',
    redirect: () => 'queue',
    onResolve: params => {
      localStorageService.set('song-to-queue', params.id)
      return true
    }
  },
  {
    path: `/invitation/accept/(?<token>${UUID_REGEX})`,
    screen: 'Invitation.Accept'
  }
]
