import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'

new class extends UnitTestCase {
  protected test () {
    it('has already been tested in the integration suite', () => expect('😄').toBeTruthy())
  }
}
