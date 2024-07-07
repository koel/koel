import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { podcastStore } from '@/stores'
import Component from './AddPodcastForm.vue'
import factory from '@/__tests__/factory'

new class extends UnitTestCase {
  protected test () {
    it('adds a new podcast', async() => {
      const storeMock = this.mock(podcastStore, 'store').mockResolvedValue(factory('podcast'))
      this.render(Component)
      await this.type(screen.getByPlaceholderText('https://example.com/feed.xml'), 'https://foo.bar/feed.xml')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))
      expect(storeMock).toHaveBeenCalledWith('https://foo.bar/feed.xml')
    })
  }
}
