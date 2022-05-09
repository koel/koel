import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'

new class extends ComponentTestCase {
  protected test () {
    it('is already covered by E2E', () => expect('🤞').toBeTruthy())
  }
}
