import Component from '@/components/ui/overlay.vue'
import { mount } from '@/__tests__/adapter'

describe('components/shared/overlay', () => {
  it('shows with default options', async () => {
    const wrapper = mount(Component)
    ;(wrapper.vm as any).show()

    await wrapper.vm.$nextTick()
    expect(wrapper).toMatchSnapshot()
  })

  it('allows option overriding', async () => {
    const wrapper = mount(Component)
    ;(wrapper.vm as any).show({
      dismissible: true,
      type: 'warning',
      message: 'Foo'
    })

    await wrapper.vm.$nextTick()
    expect(wrapper).toMatchSnapshot()
  })

  it.each([['show'], ['hide']])('%ss', methodName => {
    const wrapper = mount(Component)
    ;(wrapper.vm as any)[methodName]()
    expect(wrapper).toMatchSnapshot()
  })

  it('dismisses', () => {
    const wrapper = mount(Component)
    ;(wrapper.vm as any).show({ dismissible: true })
    expect(wrapper.has('.display')).toBe(true)
    wrapper.click('button.btn-dismiss')
    expect(wrapper.has('.display')).toBe(false)
  })
})
