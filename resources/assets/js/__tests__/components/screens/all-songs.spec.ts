import Component from '@/components/screens/all-songs.vue'
import SongList from '@/components/song/list.vue'
import factory from '@/__tests__/factory'
import { songStore } from '@/stores'
import { mount } from '@/__tests__/adapter'

describe('components/screens/all-songs', () => {
  it('renders properly', async () => {
    songStore.all = factory<Song>('song', 10)
    const wrapper = mount(Component)

    await wrapper.vm.$nextTick()
    expect(wrapper.has(SongList)).toBe(true)
  })
})
