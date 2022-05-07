import { beforeEach, expect, it } from 'vitest'
import { mockHelper, render } from '@/__tests__/__helpers__'
import { cleanup, fireEvent, queryAllByTestId } from '@testing-library/vue'
import { eventBus } from '@/utils'
import { nextTick } from 'vue'
import isMobile from 'ismobilejs'
import AppHeader from './AppHeader.vue'
import SearchForm from '@/components/ui/SearchForm.vue'
import compareVersions from 'compare-versions'
import { userStore } from '@/stores'
import factory from '@/__tests__/factory'

beforeEach(() => {
  cleanup()
  mockHelper.restoreAllMocks()
  isMobile.any = false
})

it('toggles sidebar (mobile only)', async () => {
  isMobile.any = true
  const { getByTitle } = render(AppHeader)
  const mock = mockHelper.mock(eventBus, 'emit')

  await fireEvent.click(getByTitle('Show or hide the sidebar'))

  expect(mock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
})

it('toggles search form (mobile only)', async () => {
  isMobile.any = true

  const { getByTitle, getByTestId, queryByTestId } = render(AppHeader, {
    global: {
      stubs: {
        SearchForm
      }
    }
  })

  expect(await queryByTestId('search-form')).toBe(null)

  await fireEvent.click(getByTitle('Show or hide the search form'))
  await nextTick()

  getByTestId('search-form')
})

it.each([[true, true, true], [false, true, false], [true, false, false], [false, false, false]])(
  'announces a new version if applicable',
  async (hasNewVersion, isAdmin, announcing) => {
    mockHelper.mock(compareVersions, 'compare', hasNewVersion)

    userStore.state.current = factory<User>('user', {
      is_admin: isAdmin
    })

    const { queryAllByTestId } = render(AppHeader)

    expect(await queryAllByTestId('new-version')).toHaveLength(announcing ? 1 : 0)
  }
)
