import Component from '@/components/song/like-button.vue'
import { favoriteStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import factory from '@/__tests__/factory'
import { shallow } from '@/__tests__/adapter'

describe('components/song/like-button', () => {
  it('toggles', () => {
    const m = mock(favoriteStore, 'toggleOne')
    const song = factory<Song>('song')
    shallow(Component, { propsData: { song }}).click('button')
    expect(m).toHaveBeenCalled()
  })
})
