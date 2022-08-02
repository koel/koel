import isMobile from 'ismobilejs'
import { expect, it } from 'vitest'
import { fireEvent, waitFor } from '@testing-library/vue'
import { eventBus } from '@/utils'
import compareVersions from 'compare-versions'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AppHeader from './AppHeader.vue'
import SearchForm from '@/components/ui/SearchForm.vue'

new class extends UnitTestCase {
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

      const { getByTitle, getByRole, queryByRole } = this.render(AppHeader, {
        global: {
          stubs: {
            SearchForm
          }
        }
      })

      expect(await queryByRole('search')).toBeNull()

      await fireEvent.click(getByTitle('Show or hide the search form'))
      await waitFor(() => getByRole('search'))
    })

    it.each([[true, true, true], [false, true, false], [true, false, false], [false, false, false]])(
      'announces a new version has new version: %s, is admin: %s, should announce: %s',
      async (hasNewVersion, isAdmin, announcing) => {
        this.mock(compareVersions, 'compare', hasNewVersion)

        if (isAdmin) {
          this.actingAsAdmin()
        } else {
          this.actingAs()
        }

        const { queryAllByTestId } = this.render(AppHeader)

        expect(queryAllByTestId('new-version')).toHaveLength(announcing ? 1 : 0)
      }
    )
  }
}

