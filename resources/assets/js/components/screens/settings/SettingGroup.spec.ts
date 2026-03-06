import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SettingGroup.vue'

describe('settingGroup.vue', () => {
  const h = createHarness()

  it('renders slots', () => {
    const { html } = h.render(Component, {
      slots: {
        title: 'Media Path',
        default: 'Main content',
        footer: 'Save button',
      },
    })

    expect(html()).toMatchSnapshot()
  })
})
