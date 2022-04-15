import Component from '@/components/layout/main-wrapper/index.vue'
import { mount } from '@/__tests__/adapter'

describe('component/layout/main-wrapper/index', () => {
  it('renders properly', async () => {
    const wrapper = mount(Component)
    await wrapper.vm.$nextTick()
    expect(wrapper).toMatchSnapshot()
  })
})
