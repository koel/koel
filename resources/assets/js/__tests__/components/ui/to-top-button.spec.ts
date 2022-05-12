import Component from '../../../components/ui/BtnScrollToTop.vue'
import { $ } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'

describe('components/ui/ScrollToTopButton', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', () => {
    expect(shallow(Component)).toMatchSnapshot()
  })

  it('scrolls to top', () => {
    const m = mock($, 'scrollTo')
    shallow(Component).click('button')
    expect(m).toHaveBeenCalled()
  })
})
