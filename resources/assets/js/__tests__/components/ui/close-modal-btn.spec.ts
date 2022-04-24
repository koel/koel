import Component from '@/components/ui/BtnCloseModal.vue'
import { mount } from '@/__tests__/adapter'

describe('components/ui/BtnCloseModal', () => {
  it('emits a click event', () => {
    const wrapper = mount(Component)
    wrapper.click('button')
    expect(wrapper.hasEmitted('click'))
  })
})
