import Component from '@/components/ui/view-mode-switch.vue'
import { shallow } from '@/__tests__/adapter'

describe('components/ui/view-mode-switch', () => {
  it.each([['thumbnails'], ['list']])('emits the "%s" mode value', mode => {
    const wrapper = shallow(Component, {
      propsData: {
        value: 'list'
      }
    })
    expect(wrapper.input(`input[value=${mode}]`).hasEmitted('input', mode)).toBe(true)
  })
})
