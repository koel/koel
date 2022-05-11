import isMobile from 'ismobilejs'
import { expect, it } from 'vitest'
import { fireEvent, queryAllByTestId } from '@testing-library/vue'
import { eventBus } from '@/utils'
import factory from '@/__tests__/factory'
import compareVersions from 'compare-versions'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import AppHeader from './AppHeader.vue'
import SearchForm from '@/components/ui/SearchForm.vue'

new class extends ComponentTestCase {
  protected test () {
    it('toggles sidebar (mobile only)', async () => {
      isMobile.any = true
      const { getByTitle } = this.render(AppHeader)
      const mock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByTitle('Show or hide the sidebar'))

      expect(mock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
    })

    it('toggles search form (mobile only)', async () => {
      isMobile.any = true

      const { getByTitle, getByTestId, queryByTestId } = this.render(AppHeader, {
        global: {
          stubs: {
            SearchForm
          }
        }
      })

      expect(await queryByTestId('search-form')).toBeNull()

      await fireEvent.click(getByTitle('Show or hide the search form'))
      await this.tick()

      getByTestId('search-form')
    })

    it.each([[true, true, true], [false, true, false], [true, false, false], [false, false, false]])(
      'announces a new version if applicable',
      async (hasNewVersion, isAdmin, announcing) => {
        this.mock(compareVersions, 'compare', hasNewVersion)

        const { queryAllByTestId } = this.actingAs(factory<User>('user', { is_admin: isAdmin })).render(AppHeader)

        expect(await queryAllByTestId('new-version')).toHaveLength(announcing ? 1 : 0)
      }
    )
  }
}

