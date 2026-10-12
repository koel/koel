import type { Route } from '@/router'
import { cache } from '@/services/cache'
import { canUploadFromThisDevice } from '@/utils/uploadAccess'

const UUID_REGEX = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'
const ULID_REGEX = '[0-9A-Za-z]{26}'

export const routes = [
  {
    name: 'home',
    path: '/home',
    screen: 'Home',
    title: 'Home',
  },
  {
    name: '404',
    path: '/404',
    screen: '404',
    title: 'Not Found',
    meta: {
      public: true,
    },
  },
  {
    name: 'queue',
    path: '/queue',
    screen: 'Queue',
    title: 'Current Queue',
  },
  {
    name: 'songs.index',
    path: '/songs',
    screen: 'Songs',
    title: 'All Songs',
  },
  {
    name: 'albums.index',
    path: '/albums',
    screen: 'Albums',
    title: 'Albums',
  },
  {
    name: 'artists.index',
    path: '/artists',
    screen: 'Artists',
    title: 'Artists',
  },
  {
    name: 'favorites',
    path: '/favorites',
    screen: 'Favorites',
    title: 'Your Favorites',
  },
  {
    name: 'recently-played',
    path: '/recently-played',
    screen: 'RecentlyPlayed',
    title: 'Recently Played',
  },
  {
    name: 'statistics',
    path: '/statistics',
    screen: 'Statistics',
    title: 'Listening Statistics',
  },
  {
    name: 'offline-songs',
    path: '/offline-songs',
    screen: 'OfflineSongs',
    title: 'Available Offline',
  },
  {
    name: 'search',
    path: '/search',
    screen: 'Search.Excerpt',
    title: 'Search',
  },
  {
    name: 'search.playables',
    path: '/search/songs',
    screen: 'Search.Playables',
    title: 'Search',
  },
  {
    name: 'upload',
    path: '/upload',
    screen: 'Upload',
    title: 'Upload Media',
    meta: {
      guard: canUploadFromThisDevice,
    },
  },
  {
    name: 'settings',
    path: '/settings/:section?',
    screen: 'Settings',
    title: 'Settings',
  },
  {
    name: 'users.index',
    path: '/users',
    screen: 'Settings',
    meta: {
      redirect: () => 'settings/users',
    },
  },
  {
    name: 'youtube',
    path: '/youtube',
    screen: 'YouTube',
    title: 'YouTube',
  },
  {
    name: 'profile',
    path: '/profile',
    screen: 'Settings',
    meta: {
      redirect: () => 'settings/profile',
    },
  },
  {
    name: 'visualizer',
    path: 'visualizer',
    screen: 'Visualizer',
    title: 'Visualizer',
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
    title: 'Genres',
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
    title: 'Podcasts',
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
    title: 'Radio Stations',
  },
  {
    name: 'visualizer',
    path: '/visualizer',
    screen: 'Visualizer',
    title: 'Visualizer',
  },
  {
    name: 'songs.queue',
    path: '/songs/:id',
    screen: 'Queue',
    title: 'Current Queue',
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
    name: 'email-change.confirm',
    path: '/email-change/:payload',
    screen: 'EmailChange.Confirm',
    meta: {
      public: true,
      layout: 'email-change',
    },
    constraints: {
      payload: '[a-zA-Z0-9\\+/=]+',
    },
  },
  {
    name: 'ai',
    path: '/ai',
    screen: 'AI',
    title: 'AI Assistant',
  },
  {
    name: 'media-browser',
    path: '/browse/:folder?',
    screen: 'MediaBrowser',
    title: 'Media Browser',
    constraints: {
      folder: UUID_REGEX,
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

export type RouteName = (typeof routes)[number]['name'] | keyof RouteNames
