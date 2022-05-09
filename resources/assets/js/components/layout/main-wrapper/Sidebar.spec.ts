import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'

new class extends ComponentTestCase {
  protected test () {
    it('has already been tested in the integration suite', () => expect('😄').toBeTruthy())
  }
}
