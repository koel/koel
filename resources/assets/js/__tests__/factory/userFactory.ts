import { faker } from '@faker-js/faker'
import { equalizerPresets } from '@/config/audio'

export default (): User => ({
  type: 'users',
  id: faker.string.uuid(),
  name: faker.person.fullName(),
  email: faker.internet.email(),
  password: faker.internet.password(),
  is_prospect: false,
  is_admin: false,
  avatar: 'https://gravatar.com/foo',
  preferences: {
    volume: 0.7,
    show_now_playing_notification: false,
    repeat_mode: 'NO_REPEAT',
    confirm_before_closing: false,
    continuous_playback: false,
    equalizer: faker.helpers.arrayElement(equalizerPresets),
    artists_view_mode: 'list',
    albums_view_mode: 'thumbnails',
    albums_sort_field: 'name',
    albums_sort_order: 'asc',
    transcode_on_mobile: false,
    transcode_quality: 128,
    support_bar_no_bugging: true,
    show_album_art_overlay: true,
    lyrics_zoom_level: 1,
    theme: null,
    visualizer: null,
    active_extra_panel_tab: null,
    make_uploads_public: false,
    lastfm_session_key: 'fake-session-key',
  },
  sso_provider: null,
  sso_id: null,
})

export const states: Record<string, Omit<Partial<User>, 'type'>> = {
  admin: {
    is_admin: true,
  },
  prospect: {
    is_prospect: true,
  },
}
