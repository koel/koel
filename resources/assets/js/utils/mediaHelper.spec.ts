import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'

vi.hoisted(() => {
  window.ACCEPTED_AUDIO_EXTENSIONS = ['mp3', 'flac', 'ogg', 'wav']
})

import { acceptsFile } from './mediaHelper'

describe('mediaHelper', () => {
  createHarness()

  it('accepts files with valid audio extensions', () => {
    const file = new File([''], 'song.mp3', { type: 'audio/mpeg' })
    expect(acceptsFile(file)).toBe(true)
  })

  it('rejects files with invalid extensions', () => {
    const file = new File([''], 'document.pdf', { type: 'application/pdf' })
    expect(acceptsFile(file)).toBe(false)
  })

  it('is case insensitive', () => {
    const file = new File([''], 'SONG.MP3', { type: 'audio/mpeg' })
    expect(acceptsFile(file)).toBe(true)
  })

  it('rejects files with no extension', () => {
    const file = new File([''], 'noextension', { type: '' })
    expect(acceptsFile(file)).toBe(false)
  })

  it('handles flac extension', () => {
    const file = new File([''], 'album.flac', { type: 'audio/flac' })
    expect(acceptsFile(file)).toBe(true)
  })
})
