import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { equalizerStore } from '@/stores/equalizerStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { audioService } from '@/services/audioService'
import Component from './Equalizer.vue'

type SliderInstance = {
  options: any
  handlers: Map<string, Function>
}

const sliders: SliderInstance[] = []

vi.mock('nouislider', () => ({
  default: {
    create: vi.fn((el: any, options: any) => {
      const handlers = new Map<string, Function>()
      el.noUiSlider = {
        on: (event: string, cb: Function) => handlers.set(event, cb),
        set: vi.fn(),
      }
      sliders.push({ options, handlers })
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
    preamp: 0,
    changePreampGain: vi.fn(),
    changeFilterGain: vi.fn(),
  },
}))

describe('equalizer.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      sliders.length = 0
      h.mock(equalizerStore, 'init')
      h.mock(equalizerStore, 'getConfig').mockReturnValue({
        id: undefined,
        name: 'Default',
        preamp: 0,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      })
    },
  })

  it('wires header (preset dropdown) and bands (sliders) together', () => {
    const { container } = h.render(Component)

    screen.getByText('Default')
    screen.getByText('Rock')
    screen.getByText('Preamp')
    screen.getByText('Close')
    expect(container.querySelectorAll('.slider').length).toBeGreaterThan(0)
  })

  it('emits close when the Close button is clicked', async () => {
    const { emitted } = h.render(Component)

    await fireEvent.click(screen.getByText('Close'))

    expect(emitted().close).toHaveLength(1)
  })
})

describe('equalizer.vue preamp slider', () => {
  const h = createHarness({
    beforeEach: () => {
      sliders.length = 0
      audioService.preamp = 0
      preferenceStore.current_equalizer_preset = {
        id: '01KR9JKWWQDDJZ5HT6DBY9DH3Y',
        name: 'Default',
        preamp: 0,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      }
      preferenceStore.equalizer_presets = []
    },
  })

  const ROCK_ID = '01KR9JKWWQDDJZ5HT6DBY9DH49'
  const PREAMP = 0

  it('mounts at the saved preamp gain and persists in-modal adjustments', async () => {
    // The saved customization. Without the audioService.preamp seam, the
    // slider would mount at 0 and animate to -7 via noUiSlider.set().
    audioService.preamp = -7

    const { container } = h.render(Component)
    await nextTick()

    expect(sliders[PREAMP].options.start).toBe(-7)

    // Pick Rock so a built-in preset is selected before the drag.
    const select = container.querySelector<HTMLSelectElement>('select')!
    await h.user.selectOptions(select, ROCK_ID)
    await nextTick()
    await nextTick()

    // 'slide' and 'change' fire back-to-back synchronously on a real release.
    // Without the synchronous @update:model-value emit, selectedId would
    // still be 'rock' when save() runs and Rock would overwrite the drag.
    sliders[PREAMP].handlers.get('slide')?.(['-3'], 0)
    sliders[PREAMP].handlers.get('change')?.()
    await nextTick()

    expect(preferenceStore.current_equalizer_preset.id).toBeUndefined()
    expect(preferenceStore.current_equalizer_preset.preamp).toBe(-3)
  })
})
