import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './Breadcrumbs.vue'

new class extends UnitTestCase {
  protected test () {
    it.each([[''], ['/var'], ['/var/media/'], ['/var/media/deep/nested/path']])('renders', path => {
      const { html } = this.render(Component, {
        props: {
          path,
        },
      })

      expect(html()).toMatchSnapshot()
    })
  }
}
