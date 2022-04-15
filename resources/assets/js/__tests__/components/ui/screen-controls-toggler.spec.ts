import Component from '@/components/ui/screen-controls-toggler.vue'
import isMobile from 'ismobilejs'
import { shallow } from '@/__tests__/adapter'

describe('components/song/list-controls-toggler', () => {
  beforeEach(() => (isMobile.phone = true))
  afterEach(() => (isMobile.phone = false))

  it('renders properly', () => {
    expect(shallow(Component)).toMatchSnapshot()
  })

  it.each([[true], [false]])('shows/hides properly', shouldShow => {
    const wrapper = shallow(Component, { propsData: { showingControls: shouldShow } })
    expect(wrapper.has('.toggler.fa-angle-up')).toBe(shouldShow)
  })

  it('emits event', () => {
    const wrapper = shallow(Component)
    wrapper.click('.controls-toggler')
    expect(wrapper.hasEmitted('toggleControls')).toBe(true)
  })
})
