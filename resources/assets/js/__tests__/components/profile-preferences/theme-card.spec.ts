import { shallow } from '@/__tests__/adapter'
import Component from '@/components/profile-preferences/theme-card.vue'

describe('profile-preferences/profile-form', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  const theme: Theme = {
    id: 'sample',
    thumbnailColor: '#f00'
  }

  it('renders', () => {
    expect(shallow(Component, {
      propsData: {
        theme
      }
    })).toMatchSnapshot()
  })

  it('emits an event when theme is selected', () => {
    const wrapper = shallow(Component, {
      propsData: {
        theme
      }
    })

    wrapper.click('[data-testid=theme-card-sample]')
    expect(wrapper.hasEmitted('selected', theme)).toBe(true)
  })
})
