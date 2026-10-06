import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { canUploadFromThisDevice } from './uploadAccess'

const device = vi.hoisted(() => ({ any: false }))
const permission = vi.hoisted(() => ({ uploadSongs: true }))

vi.mock('ismobilejs', () => ({ default: device }))

vi.mock('@/composables/usePolicies', () => ({
  usePolicies: () => ({ currentUserCan: { uploadSongs: () => permission.uploadSongs } }),
}))

describe('canUploadFromThisDevice', () => {
  afterEach(() => {
    device.any = false
    permission.uploadSongs = true
  })

  it('allows a user with upload permission on a computer', () => {
    expect(canUploadFromThisDevice()).toBe(true)
  })

  it('refuses on a phone or tablet', () => {
    device.any = true

    expect(canUploadFromThisDevice()).toBe(false)
  })

  it('refuses a user without upload permission', () => {
    permission.uploadSongs = false

    expect(canUploadFromThisDevice()).toBe(false)
  })
})
