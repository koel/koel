import { faker } from '@faker-js/faker'
import { equalizerPresets } from '@/config/audio'

const preferences = {
  volume: 0.7,
  show_now_playing_notification: false,
  repeat_mode: 'NO_REPEAT',
  confirm_before_closing: false,
  continuous_playback: false,
  equalizer: faker.helpers.arrayElement(equalizerPresets),
  artists_view_mode: 'thumbnails',
  albums_view_mode: 'thumbnails',
  radio_stations_view_mode: 'thumbnails',
  albums_sort_field: 'name',
  albums_sort_order: 'asc',
  albums_favorites_only: false,
  artists_sort_field: 'name',
  artists_sort_order: 'asc',
  artists_favorites_only: false,
  genres_sort_field: 'name',
  genres_sort_order: 'asc',
  podcasts_sort_order: 'asc',
  podcasts_sort_field: 'title',
  podcasts_favorites_only: false,
  radio_stations_sort_field: 'name',
  radio_stations_sort_order: 'asc',
  radio_stations_favorites_only: false,
  transcode_on_mobile: false,
  transcode_quality: 128,
  support_bar_no_bugging: true,
  show_album_art_overlay: true,
  lyrics_zoom_level: 1,
  theme: null,
  visualizer: null,
  active_extra_panel_tab: null,
  make_uploads_public: false,
  include_public_media: true,
  lastfm_session_key: 'fake-session-key',
}

export default (): User => ({
  type: 'users',
  id: faker.string.uuid(),
  name: faker.person.fullName(),
  email: faker.internet.email(),
  password: faker.internet.password(),
  is_prospect: false,
  role: 'user',
  avatar: 'https://gravatar.com/foo',
  sso_provider: null,
  sso_id: null,
})

export const states: Record<string, Omit<Partial<User>, 'type'>> = {
  admin: {
    role: 'admin',
    preferences,
    permissions: ['manage settings', 'manage users', 'manage songs', 'manage podcasts', 'manage radio stations'],
  },
  manager: {
    role: 'manager',
  },
  prospect: {
    is_prospect: true,
  },
  current: {
    preferences,
    permissions: [],
  },
}
