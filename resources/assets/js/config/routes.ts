import { eventBus } from '@/utils'
import { Route } from '@/router'
import { userStore } from '@/stores'

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
    onBeforeEnter: () => userStore.current.is_admin
  },
  {
    path: '/settings',
    screen: 'Settings',
    onBeforeEnter: () => userStore.current.is_admin
  },
  {
    path: '/users',
    screen: 'Users',
    onBeforeEnter: () => userStore.current.is_admin
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
    path: '/song/(?<id>[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})',
    screen: 'Queue',
    redirect: () => 'queue',
    onBeforeEnter: params => eventBus.emit('SONG_QUEUED_FROM_ROUTE', params.id)
  }
]
