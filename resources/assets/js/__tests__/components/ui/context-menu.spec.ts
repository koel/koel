import Component from '@/components/ui/ContextMenuBase.vue'
import { mount } from '@/__tests__/adapter'

declare const global: any

describe('components/ui/ContextMenuBase', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders', () => {
    expect(mount(Component)).toMatchSnapshot()
  })

  it('renders extra CSS classes', async () => {
    const wrapper = mount(Component, {
      propsData: {
        extraClass: 'foo'
      }
    })
    await (wrapper.vm as any).open(0, 0)
    expect(wrapper.find('.menu').hasClass('foo')).toBe(true)
  })

  it('opens', () => {
    const wrapper = mount(Component)
    ;(wrapper.vm as any).open(42, 128)
    expect(wrapper.find('.menu').element.style.top).toBe('42px')
    expect(wrapper.find('.menu').element.style.left).toBe('128px')
    expect(global.getComputedStyle(wrapper.find('.menu').element).display).toBe('block')
  })

  it('closes', async () => {
    const wrapper = mount(Component)
    await (wrapper.vm as any).open(42, 128)
    ;(wrapper.vm as any).close()
    expect(wrapper.html()).toBeUndefined()
  })
})
