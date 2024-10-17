import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import Component from './EpisodeProgress.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      const { html } = this.render(Component, {
        props: {
          episode: factory('episode', {
            length: 300,
          }),
          position: 60,
        },
      })

      expect(html()).toMatchSnapshot()
    })
  }
}
