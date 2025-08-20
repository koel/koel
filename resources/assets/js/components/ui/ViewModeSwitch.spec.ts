import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './ViewModeSwitch.vue'

new class extends UnitTestCase {
  protected test () {
    it.each<[ViewMode]>([['thumbnails'], ['list']])('renders %s mode', mode => {
      const { html } = this.render(Component, {
        props: {
          modelValue: mode,
        },
      })

      expect(html()).toMatchSnapshot()
    })

    it('emits the correct event', async () => {
      const { emitted } = this.render(Component, {
        props: {
          modelValue: 'thumbnails',
        },
      })

      await this.user.click(screen.getByTestId('view-mode-list'))
      expect(emitted()['update:modelValue'][0]).toEqual(['list'])

      await this.user.click(screen.getByTestId('view-mode-thumbnails'))
      expect(emitted()['update:modelValue'][1]).toEqual(['thumbnails'])
    })
  }
}
