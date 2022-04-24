import Component from '@/components/ui/ViewModeSwitch.vue'
import { shallow } from '@/__tests__/adapter'

describe('components/ui/ViewModeSwitch', () => {
  it.each([['thumbnails'], ['list']])('emits the "%s" mode value', mode => {
    const wrapper = shallow(Component, {
      propsData: {
        value: 'list'
      }
    })
    expect(wrapper.input(`input[value=${mode}]`).hasEmitted('input', mode)).toBe(true)
  })
})
