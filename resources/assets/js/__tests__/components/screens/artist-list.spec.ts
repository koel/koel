import List from '@/components/screens/artist-list.vue'
import factory from '@/__tests__/factory'
import { mount } from '@/__tests__/adapter'

describe('components/screens/artist-list', () => {
  it('displays a list of artists', async () => {
    const wrapper = mount(List, {
      sync: false, // https://github.com/vuejs/vue-test-utils/issues/673
      stubs: ['artist-card'],
      data: () => ({
        artists: factory<Artist>('artist', 5)
      })
    })

    await wrapper.vm.$nextTick()
    expect(wrapper.findAll('artist-card-stub')).toHaveLength(5)
  })
})
