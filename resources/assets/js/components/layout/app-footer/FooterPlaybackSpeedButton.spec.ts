import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { playback } from '@/services/playbackManager'
import Component from './FooterPlaybackSpeedButton.vue'

describe('footerPlaybackSpeedButton.vue', () => {
  const h = createHarness({
    beforeEach: () => h.createAudioPlayer(),
  })

  it('changes the podcast speed while an episode plays', async () => {
    const media = playback().media

    h.render(Component, {
      props: {
        playable: h.factory('episode').make(),
      },
    })

    await h.user.click(screen.getByRole('button', { name: /Playback speed/ }))
    await h.user.click(screen.getAllByRole('listitem')[3])

    expect(preferences.podcast_playback_speed).toBe(1.5)
    expect(preferences.music_playback_speed).toBe(1)
    expect(media.playbackRate).toBe(1.5)

    preferences.temporary.podcast_playback_speed = 1
    media.playbackRate = 1
  })
})
