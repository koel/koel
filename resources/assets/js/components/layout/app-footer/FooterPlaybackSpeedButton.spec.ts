import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { playback } from '@/services/playbackManager'
import { crossfadeService } from '@/services/crossfadeService'
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
  it('changes the speed from the keyboard', async () => {
    h.render(Component, {
      props: {
        playable: h.factory('song').make(),
      },
    })

    await h.user.click(screen.getByRole('button', { name: /Playback speed/ }))
    screen.getAllByRole('listitem')[2].focus()
    await h.user.keyboard('{Enter}')

    expect(preferences.music_playback_speed).toBe(1.25)

    preferences.temporary.music_playback_speed = 1
    playback().media.playbackRate = 1
  })

  it('leaves the outgoing song speed alone while crossfading into an episode', async () => {
    const outgoingMedia = playback().media
    const incomingAudio = document.createElement('audio')
    const episode = h.factory('episode').make()

    crossfadeService.state = {
      incomingAudio,
      playable: episode,
      fadesThroughAudioGraph: false,
      originalVolume: 7,
    }

    h.render(Component, {
      props: {
        playable: episode,
      },
    })

    await h.user.click(screen.getByRole('button', { name: /Playback speed/ }))
    await h.user.click(screen.getAllByRole('listitem')[5])

    expect(incomingAudio.playbackRate).toBe(2)
    expect(outgoingMedia.playbackRate).toBe(1)

    crossfadeService.state = null
    preferences.temporary.podcast_playback_speed = 1
  })
})
