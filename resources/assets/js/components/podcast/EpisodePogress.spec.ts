import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './EpisodeProgress.vue'
import factory from '@/__tests__/factory'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      const { html } = this.render(Component, {
        props: {
          episode: factory('episode', {
            length: 300
          }),
          position: 60
        }
      })

      expect(html()).toMatchSnapshot()
    })
  }
}
