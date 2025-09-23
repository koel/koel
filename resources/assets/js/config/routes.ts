import type { Route } from '@/router'
import { cache } from '@/services/cache'
import { usePolicies } from '@/composables/usePolicies'

const UUID_REGEX = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'
const ULID_REGEX = '[0-9A-Za-z]{26}'

export const routes = [
  {
    name: 'home',
    path: '/home',
    screen: 'Home',
  },
  {
    name: '404',
    path: '/404',
    screen: '404',
    meta: {
      public: true,
    },
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
    name: 'search.playables',
    path: '/search/songs',
    screen: 'Search.Playables',
  },
  {
    name: 'upload',
    path: '/upload',
    screen: 'Upload',
    meta: {
      guard: () => usePolicies().currentUserCan.uploadSongs(),
    },
  },
  {
    name: 'settings',
    path: '/settings',
    screen: 'Settings',
    meta: {
      guard: () => usePolicies().currentUserCan.manageSettings(),
    },
  },
  {
    name: 'users.index',
    path: '/users',
    screen: 'Users',
    meta: {
      guard: () => usePolicies().currentUserCan.manageUsers(),
    },
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
    path: '/albums/:id/:tab?',
    screen: 'Album',
    constraints: {
      id: ULID_REGEX,
      tab: '(songs|other-albums|information)',
    },
  },
  {
    name: 'artists.show',
    path: '/artists/:id/:tab?',
    screen: 'Artist',
    constraints: {
      id: ULID_REGEX,
      tab: '(songs|albums|information|events)',
    },
  },
  {
    name: 'playlists.show',
    path: '/playlists/:id',
    screen: 'Playlist',
    constraints: {
      id: UUID_REGEX,
    },
  },
  {
    name: 'playlist.collaborate',
    path: '/playlist/collaborate/:id',
    screen: 'Playlist.Collaborate',
    constraints: {
      id: UUID_REGEX,
    },
  },
  {
    name: 'genres.index',
    path: '/genres',
    screen: 'Genres',
  },
  {
    name: 'genres.show',
    path: '/genres/:id',
    screen: 'Genre',
  },
  {
    name: 'podcasts.index',
    path: '/podcasts',
    screen: 'Podcasts',
  },
  {
    name: 'podcasts.show',
    path: '/podcasts/:id',
    screen: 'Podcast',
    constraints: {
      id: UUID_REGEX,
    },
  },
  {
    name: 'episodes.show',
    path: '/episodes/:id',
    screen: 'Episode',
  },
  {
    name: 'radio-stations.index',
    path: '/radio/stations',
    screen: 'Radio.Stations',
  },
  {
    name: 'visualizer',
    path: '/visualizer',
    screen: 'Visualizer',
  },
  {
    name: 'songs.queue',
    path: '/songs/:id',
    screen: 'Queue',
    constraints: {
      id: UUID_REGEX,
    },
    meta: {
      redirect: () => 'queue',
      onResolved: params => cache.set('playable-to-queue', params.id),
    },
  },
  {
    name: 'invitation.accept',
    path: '/invitation/accept/:token',
    screen: 'Invitation.Accept',
    meta: {
      layout: 'invitation',
      public: true,
    },
    constraints: {
      token: UUID_REGEX,
    },
  },
  {
    name: 'password.reset',
    path: '/reset-password/:payload',
    screen: 'Password.Reset',
    meta: {
      public: true,
      layout: 'reset-password',
    },
    constraints: {
      payload: '[a-zA-Z0-9\\+/=]+',
    },
  },
  {
    name: 'media-browser',
    path: '/browse/:path?',
    screen: 'MediaBrowser',
    constraints: {
      path: '.+',
    },
  },
  {
    name: 'embed',
    path: '/embed/:id/:options',
    screen: 'Embed',
    meta: {
      public: true,
      layout: 'embed',
    },
    constraints: {
      id: ULID_REGEX,
    },
  },
] as const satisfies Route[]

export type RouteName = typeof routes[number]['name']
