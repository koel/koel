import isMobile from 'ismobilejs'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { canUploadFromThisDevice } from './uploadAccess'

describe('canUploadFromThisDevice', () => {
  const h = createHarness()

  it('allows a user with upload permission on a computer', () => {
    h.actingAsAdmin()

    expect(canUploadFromThisDevice()).toBe(true)
  })

  it('refuses on a phone or tablet', () => {
    h.actingAsAdmin()
    isMobile.any = true

    expect(canUploadFromThisDevice()).toBe(false)
  })

  it('refuses a user without upload permission', () => {
    h.actingAsUser()

    expect(canUploadFromThisDevice()).toBe(false)
  })
})
