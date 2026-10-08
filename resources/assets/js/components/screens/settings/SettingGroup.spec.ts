import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SettingGroup.vue'

describe('settingGroup.vue', () => {
  const h = createHarness()

  it('renders the title and footer when given', () => {
    h.render(Component, {
      slots: {
        title: 'Media Path',
        default: 'Main content',
        footer: '<button>Save</button>',
      },
    })

    screen.getByRole('heading')
    screen.getByRole('button')
  })

  it('leaves out the header and footer when not given', () => {
    const { container } = h.render(Component, {
      slots: {
        default: 'Main content',
      },
    })

    expect(container.querySelector('header')).toBeNull()
    expect(container.querySelector('footer')).toBeNull()
  })
})
