import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useBranding } from './useBranding'

describe('useBranding', () => {
  createHarness()

  it('returns branding name from window', () => {
    const { name } = useBranding()
    expect(name).toBe(window.KOEL.branding.name)
  })

  it('falls back to default logo when none set', () => {
    const originalLogo = window.KOEL.branding.logo
    window.KOEL.branding.logo = ''

    const { logo, koelBirdLogo, isKoelBirdLogo } = useBranding()
    expect(logo).toBe(koelBirdLogo)
    expect(isKoelBirdLogo(logo)).toBe(true)

    window.KOEL.branding.logo = originalLogo
  })

  it('falls back to default cover when none set', () => {
    const originalCover = window.KOEL.branding.cover
    window.KOEL.branding.cover = ''

    const { cover, koelBirdCover, isKoelBirdCover } = useBranding()
    expect(cover).toBe(koelBirdCover)
    expect(isKoelBirdCover(cover)).toBe(true)

    window.KOEL.branding.cover = originalCover
  })

  it('detects custom branding when logo is custom', () => {
    const originalLogo = window.KOEL.branding.logo
    window.KOEL.branding.logo = 'https://example.com/custom-logo.png'

    const { hasCustomBranding } = useBranding()
    expect(hasCustomBranding).toBe(true)

    window.KOEL.branding.logo = originalLogo
  })

  it('detects no custom branding with defaults', () => {
    const originalLogo = window.KOEL.branding.logo
    const originalCover = window.KOEL.branding.cover
    const originalName = window.KOEL.branding.name
    window.KOEL.branding.logo = ''
    window.KOEL.branding.cover = ''
    window.KOEL.branding.name = 'Koel'

    const { hasCustomBranding } = useBranding()
    expect(hasCustomBranding).toBe(false)

    window.KOEL.branding.logo = originalLogo
    window.KOEL.branding.cover = originalCover
    window.KOEL.branding.name = originalName
  })

  it('identifies koel bird logo correctly', () => {
    const { koelBirdLogo, isKoelBirdLogo } = useBranding()
    expect(isKoelBirdLogo(koelBirdLogo)).toBe(true)
    expect(isKoelBirdLogo('https://example.com/other.png')).toBe(false)
  })
})
