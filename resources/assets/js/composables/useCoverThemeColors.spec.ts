import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { effectScope, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import { themeStore } from '@/stores/themeStore'
import * as coverColor from '@/utils/coverColor'
import { useCoverThemeColors } from './useCoverThemeColors'

describe('useCoverThemeColors', () => {
  const h = createHarness()
  const colors = { background: 'hsl(24 35% 10%)', highlight: 'hsl(24 87% 55%)' }

  let scope = effectScope()

  const useWithStreamable = (streamable?: Streamable) => scope.run(() => useCoverThemeColors(ref(streamable)))

  beforeEach(() => {
    scope = effectScope()
    vi.restoreAllMocks()
    vi.spyOn(coverColor, 'getCoverThemeColors').mockResolvedValue(colors)
  })

  afterEach(() => scope.stop())

  it('tints the app with the playing cover on the Kameleon theme', async () => {
    preferenceStore.state.theme = 'kameleon'
    const setCoverColors = vi.spyOn(themeStore, 'setCoverColors')

    useWithStreamable(h.factory('song').make({ album_cover: 'https://example.test/cover.jpg' }))
    await h.tick(2)

    expect(coverColor.getCoverThemeColors).toHaveBeenCalledWith('https://example.test/cover.jpg')
    expect(setCoverColors).toHaveBeenCalledWith(colors)
  })

  it('uses the episode image for a podcast episode', async () => {
    preferenceStore.state.theme = 'kameleon'

    useWithStreamable(h.factory('episode').make({ episode_image: 'https://example.test/episode.jpg' }))
    await h.tick(2)

    expect(coverColor.getCoverThemeColors).toHaveBeenCalledWith('https://example.test/episode.jpg')
  })

  it('uses the logo for a radio station', async () => {
    preferenceStore.state.theme = 'kameleon'

    useWithStreamable(h.factory('radio-station').make({ logo: 'https://example.test/logo.png' }))
    await h.tick(2)

    expect(coverColor.getCoverThemeColors).toHaveBeenCalledWith('https://example.test/logo.png')
  })

  it('drops colors that arrive after the playing cover has changed', async () => {
    preferenceStore.state.theme = 'kameleon'
    let resolveFirstCover: (themeColors: typeof colors) => void = () => {}

    vi.spyOn(coverColor, 'getCoverThemeColors')
      .mockImplementationOnce(() => new Promise(resolve => (resolveFirstCover = resolve)))
      .mockResolvedValue(colors)

    const setCoverColors = vi.spyOn(themeStore, 'setCoverColors')
    const streamable = ref<Streamable>(h.factory('song').make({ album_cover: 'https://example.test/first.jpg' }))

    scope.run(() => useCoverThemeColors(streamable))
    streamable.value = h.factory('song').make({ album_cover: 'https://example.test/second.jpg' })
    await h.tick(2)

    resolveFirstCover({ background: 'hsl(0 0% 10%)', highlight: 'hsl(0 0% 55%)' })
    await h.tick(2)

    expect(setCoverColors).toHaveBeenCalledTimes(1)
    expect(setCoverColors).toHaveBeenCalledWith(colors)
  })

  it('goes back to the plain colors when nothing with a cover is playing', async () => {
    preferenceStore.state.theme = 'kameleon'
    const setCoverColors = vi.spyOn(themeStore, 'setCoverColors')

    useWithStreamable()
    await h.tick(2)

    expect(setCoverColors).toHaveBeenCalledWith(null)
  })

  it('leaves other themes alone', async () => {
    preferenceStore.state.theme = 'violet'
    const setCoverColors = vi.spyOn(themeStore, 'setCoverColors')

    useWithStreamable(h.factory('song').make({ album_cover: 'https://example.test/cover.jpg' }))
    await h.tick(2)

    expect(setCoverColors).not.toHaveBeenCalled()
  })
})
