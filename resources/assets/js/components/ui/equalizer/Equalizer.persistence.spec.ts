import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import { equalizerPresets } from '@/config/audio'
import { audioService } from '@/services/audioService'
import Component from './Equalizer.vue'

type SliderInstance = {
  el: any
  on: (event: string, cb: Function) => void
  set: (val: number) => void
  handlers: Map<string, Function>
}

const sliders: SliderInstance[] = []

vi.mock('nouislider', () => ({
  default: {
    create: vi.fn((el: any) => {
      const handlers = new Map<string, Function>()
      el.noUiSlider = {
        on: (event: string, cb: Function) => {
          handlers.set(event, cb)
        },
        set: vi.fn(),
      }
      sliders.push({ el, on: el.noUiSlider.on, set: el.noUiSlider.set, handlers })
    }),
  },
}))

vi.mock('@/services/audioService', () => ({
  audioService: {
    bands: Array.from({ length: 10 }, (_, index) => ({
      label: `band-${index}`,
      db: 0,
      node: {},
    })),
    changePreampGain: vi.fn(),
    changeFilterGain: vi.fn(),
  },
}))

const fireSlide = (sliderIndex: number, value: number) => {
  const handler = sliders[sliderIndex].handlers.get('slide')
  handler?.([String(value)], 0)
}

const fireChange = (sliderIndex: number) => {
  const handler = sliders[sliderIndex].handlers.get('change')
  handler?.()
}

describe('Equalizer persistence', () => {
  const h = createHarness()

  beforeEach(() => {
    sliders.length = 0
    preferenceStore.current_equalizer_preset = {
      id: '01KR9JKWWQDDJZ5HT6DBY9DH3Y',
      name: 'Default',
      preamp: 0,
      gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    }
    preferenceStore.equalizer_presets = []
    audioService.bands.forEach(band => (band.db = 0))
  })

  afterEach(() => {
    sliders.length = 0
  })

  const ROCK_ID = '01KR9JKWWQDDJZ5HT6DBY9DH49'

  it('persists slider customization after picking a preset and reopening', async () => {
    // Step 1: open the equalizer.
    const first = h.render(Component)
    await nextTick()

    // Step 2: pick Rock from the preset dropdown.
    const select = first.container.querySelector<HTMLSelectElement>('select')!
    await h.user.selectOptions(select, ROCK_ID)
    await nextTick()
    await nextTick()

    // Sanity: Rock should be persisted as the current preset.
    const rock = equalizerPresets.find(preset => preset.id === ROCK_ID)!
    expect(preferenceStore.current_equalizer_preset.id).toBe(ROCK_ID)

    // The first slider element is the preamp; the next 10 are bands.
    // Band 0's slider is sliders[1] (index 0 = preamp, index 1 = band 0).
    const BAND_0_SLIDER = 1

    // Step 3: simulate dragging band 0's slider to -10.
    fireSlide(BAND_0_SLIDER, -10)
    await nextTick()
    // Step 3b: release the slider.
    fireChange(BAND_0_SLIDER)
    await nextTick()

    // The customized state must be persisted to preferences.
    expect(preferenceStore.current_equalizer_preset.id).toBeUndefined()
    expect(preferenceStore.current_equalizer_preset.name).toBeNull()
    expect(preferenceStore.current_equalizer_preset.gains[0]).toBe(-10)
    // The other bands should still reflect Rock's gains.
    expect(preferenceStore.current_equalizer_preset.gains[1]).toBe(rock.gains[1])

    // Step 4: close (unmount).
    first.unmount()

    // Step 5: reopen.
    sliders.length = 0
    h.render(Component)
    await nextTick()
    await nextTick()

    // After reopen, band 0's slider must reflect the customization (-10),
    // not Rock's value (8). Find the last `set` call for band 0's slider.
    const band0SetMock = sliders[BAND_0_SLIDER].set as unknown as ReturnType<typeof vi.fn>
    const lastCall = band0SetMock.mock.calls.at(-1)
    expect(lastCall?.[0]).toBe(-10)
  })

  it('persists preamp customization after picking a preset and reopening', async () => {
    // Step 1: open the equalizer.
    const first = h.render(Component)
    await nextTick()

    // Step 2: pick Rock from the preset dropdown.
    const select = first.container.querySelector<HTMLSelectElement>('select')!
    await h.user.selectOptions(select, ROCK_ID)
    await nextTick()
    await nextTick()

    expect(preferenceStore.current_equalizer_preset.id).toBe(ROCK_ID)

    // The leftmost slider in the bands strip is the preamp (sliders[0]).
    const PREAMP_SLIDER = 0

    // Step 3: simulate dragging the preamp slider to -3, then releasing.
    // In a real drag, 'slide' and 'change' fire back-to-back synchronously
    // when the user lets go — no microtask between them. The bug surfaces
    // when user-change is emitted only via the asynchronous watch on
    // preampGain: selectedId is still 'rock' at save() time and the
    // customized preamp is overwritten with Rock's.
    fireSlide(PREAMP_SLIDER, -3)
    fireChange(PREAMP_SLIDER)
    await nextTick()

    // The customized preamp must be persisted to preferences.
    expect(preferenceStore.current_equalizer_preset.id).toBeUndefined()
    expect(preferenceStore.current_equalizer_preset.name).toBeNull()
    expect(preferenceStore.current_equalizer_preset.preamp).toBe(-3)

    // Step 4: close (unmount).
    first.unmount()

    // Step 5: reopen and confirm the preamp slider is restored to -3, not Rock's.
    sliders.length = 0
    h.render(Component)
    await nextTick()
    await nextTick()

    const preampSetMock = sliders[PREAMP_SLIDER].set as unknown as ReturnType<typeof vi.fn>
    const lastCall = preampSetMock.mock.calls.at(-1)
    expect(lastCall?.[0]).toBe(-3)
  })
})
