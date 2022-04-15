import Component from '@/components/ui/close-modal-btn.vue'
import { mount } from '@/__tests__/adapter'

describe('components/ui/close-modal-btn', () => {
  it('emits a click event', () => {
    const wrapper = mount(Component)
    wrapper.click('button')
    expect(wrapper.hasEmitted('click'))
  })
})
