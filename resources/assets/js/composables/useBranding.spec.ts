import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useBranding } from './useBranding'

describe('useBranding', () => {
  createHarness()

  it('returns branding name from window', () => {
    const { name } = useBranding()
    expect(name).toBe(window.BRANDING.name)
  })

  it('falls back to default logo when none set', () => {
    const originalLogo = window.BRANDING.logo
    window.BRANDING.logo = ''

    const { logo, koelBirdLogo, isKoelBirdLogo } = useBranding()
    expect(logo).toBe(koelBirdLogo)
    expect(isKoelBirdLogo(logo)).toBe(true)

    window.BRANDING.logo = originalLogo
  })

  it('falls back to default cover when none set', () => {
    const originalCover = window.BRANDING.cover
    window.BRANDING.cover = ''

    const { cover, koelBirdCover, isKoelBirdCover } = useBranding()
    expect(cover).toBe(koelBirdCover)
    expect(isKoelBirdCover(cover)).toBe(true)

    window.BRANDING.cover = originalCover
  })

  it('detects custom branding when logo is custom', () => {
    const originalLogo = window.BRANDING.logo
    window.BRANDING.logo = 'https://example.com/custom-logo.png'

    const { hasCustomBranding } = useBranding()
    expect(hasCustomBranding).toBe(true)

    window.BRANDING.logo = originalLogo
  })

  it('detects no custom branding with defaults', () => {
    const originalLogo = window.BRANDING.logo
    const originalCover = window.BRANDING.cover
    const originalName = window.BRANDING.name
    window.BRANDING.logo = ''
    window.BRANDING.cover = ''
    window.BRANDING.name = 'Koel'

    const { hasCustomBranding } = useBranding()
    expect(hasCustomBranding).toBe(false)

    window.BRANDING.logo = originalLogo
    window.BRANDING.cover = originalCover
    window.BRANDING.name = originalName
  })

  it('identifies koel bird logo correctly', () => {
    const { koelBirdLogo, isKoelBirdLogo } = useBranding()
    expect(isKoelBirdLogo(koelBirdLogo)).toBe(true)
    expect(isKoelBirdLogo('https://example.com/other.png')).toBe(false)
  })
})
