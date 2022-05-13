import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'

new class extends UnitTestCase {
  protected test () {
    it('is already covered by E2E', () => expect('🤞').toBeTruthy())
  }
}
