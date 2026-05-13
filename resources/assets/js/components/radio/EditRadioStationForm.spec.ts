import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { radioStationStore } from '@/stores/radioStationStore'
import { playbackService as radioPlaybackService } from '@/services/RadioPlaybackService'
import type { Reactive } from 'vue'
import { reactive } from 'vue'
import Component from './EditRadioStationForm.vue'

describe('editRadioStationForm.vue', () => {
  const h = createHarness()

  const renderComponent = (station?: RadioStation | Reactive<RadioStation>) => {
    station = station ?? h.factory('radio-station').make()

    const rendered = h.render(Component, {
      props: {
        station,
      },
    })

    return {
      ...rendered,
      station,
    }
  }

  it('edits a radio station without logo changing', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const { station } = renderComponent(reactive(h.factory('radio-station').make({ is_public: false })))

    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      is_public: false,
    })
  })

  it('edits a radio station, removing the logo', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const { station } = renderComponent(reactive(h.factory('radio-station').make({ is_public: false })))

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      logo: '',
      is_public: false,
    })
  })

  it('edits a radio station, replacing the logo', async () => {
    const updateMock = h.mock(radioStationStore, 'update')
    const { station } = renderComponent(reactive(h.factory('radio-station').make({ is_public: false })))

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    await h.user.upload(
      screen.getByLabelText('Pick or paste a logo (optional)'),
      new File(['bytes'], 'logo.png', { type: 'image/png' }),
    )

    await waitFor(() => screen.getByRole('img'))

    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'Beethoven Goes Pop')
    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://beet.stream/pop')
    await h.type(screen.getByPlaceholderText('A short description of the station'), 'Poppy af')

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(station, {
      name: 'Beethoven Goes Pop',
      url: 'https://beet.stream/pop',
      description: 'Poppy af',
      logo: 'data:image/png;base64,Ynl0ZXM=',
      is_public: false,
    })
  })

  const renderForOnAirStation = (overrides: Partial<RadioStation>) => {
    h.createAudioPlayer()

    const station = reactive(
      h.factory('radio-station').make({
        url: 'https://old.example.com/stream',
        playback_state: 'Playing',
        ...overrides,
      }),
    )
    radioStationStore.state.stations = [station]

    h.mock(radioStationStore, 'update', async (target: Reactive<RadioStation>, data: any) => {
      Object.assign(target, data)
      return target
    })

    return {
      playMock: h.mock(radioPlaybackService, 'play').mockResolvedValue(undefined),
      ...renderComponent(station),
    }
  }

  it('restarts playback when the on-air station has its URL changed', async () => {
    const { station, playMock } = renderForOnAirStation({})

    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://new.example.com/stream')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => expect(playMock).toHaveBeenCalledWith(station))
  })

  it('does not restart playback when only the name changed', async () => {
    const { playMock } = renderForOnAirStation({})

    await h.type(screen.getByPlaceholderText('My Favorite Radio Station'), 'A Brand New Name')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => expect(radioStationStore.update).toHaveBeenCalled())
    expect(playMock).not.toHaveBeenCalled()
  })

  it('does not restart playback when the on-air station is paused', async () => {
    const { playMock } = renderForOnAirStation({ playback_state: 'Paused' })

    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://new.example.com/stream')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => expect(radioStationStore.update).toHaveBeenCalled())
    expect(playMock).not.toHaveBeenCalled()
  })

  it('does not restart playback when the edited station is not on air', async () => {
    h.createAudioPlayer()

    const onAir = reactive(h.factory('radio-station').make({ playback_state: 'Playing' }))
    const editing = reactive(
      h.factory('radio-station').make({
        url: 'https://old.example.com/stream',
        playback_state: 'Stopped',
      }),
    )
    radioStationStore.state.stations = [onAir, editing]

    h.mock(radioStationStore, 'update', async (target: Reactive<RadioStation>, data: any) => {
      Object.assign(target, data)
      return target
    })
    const playMock = h.mock(radioPlaybackService, 'play').mockResolvedValue(undefined)

    renderComponent(editing)

    await h.type(screen.getByPlaceholderText('https://radio.example.com/stream'), 'https://new.example.com/stream')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => expect(radioStationStore.update).toHaveBeenCalled())
    expect(playMock).not.toHaveBeenCalled()
  })
})
