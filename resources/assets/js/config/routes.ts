import { Route } from '@/router'
import { userStore } from '@/stores'
import { localStorageService } from '@/services'
import { useUpload } from '@/composables'

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
    path: '/playlist/(?<id>\\d+)',
    screen: 'Playlist'
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
    path: '/song/(?<id>[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})',
    screen: 'Queue',
    redirect: () => 'queue',
    onResolve: params => {
      localStorageService.set('song-to-queue', params.id)
      return true
    }
  },
  {
    path: '/invitation/accept/(?<token>[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})',
    screen: 'Invitation.Accept'
  }
]
