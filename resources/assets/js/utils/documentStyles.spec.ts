import { afterEach, describe, expect, it } from 'vite-plus/test'
import { copyStylesheetsToDocument, copyThemeToDocument } from './documentStyles'

describe('documentStyles', () => {
  afterEach(() => {
    document.head.innerHTML = ''
    document.documentElement.className = ''
    delete document.documentElement.dataset.theme
    document.body.style.cssText = ''
  })

  it('copies the stylesheets to another document', () => {
    document.head.innerHTML = '<link rel="stylesheet" href="/app.css"><style>body { color: red }</style>'
    const target = document.implementation.createHTMLDocument()

    copyStylesheetsToDocument(target)

    expect(target.head.querySelector('link[rel="stylesheet"]')!.getAttribute('href')).toBe('/app.css')
    expect(target.head.querySelector('style')!.textContent).toBe('body { color: red }')
  })

  it('copies the theme to another document', () => {
    document.documentElement.className = 'mac'
    document.documentElement.dataset.theme = 'violet'
    document.body.style.setProperty('--color-bg', 'black')
    const target = document.implementation.createHTMLDocument()

    copyThemeToDocument(target)

    expect(target.documentElement.className).toBe('mac')
    expect(target.documentElement.dataset.theme).toBe('violet')
    expect(target.body.style.getPropertyValue('--color-bg')).toBe('black')
  })
})
