import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { dbToGain } from './audioService'

describe('audioService', () => {
  createHarness()

  describe('dbToGain', () => {
    it('converts 0 dB to gain of 1', () => {
      expect(dbToGain(0)).toBe(1)
    })

    it('converts positive dB to gain > 1', () => {
      expect(dbToGain(20)).toBeCloseTo(10)
    })

    it('converts negative dB to gain < 1', () => {
      expect(dbToGain(-20)).toBeCloseTo(0.1)
    })

    it('handles -Infinity as 0 gain', () => {
      expect(dbToGain(-Infinity)).toBe(0)
    })
  })
})
