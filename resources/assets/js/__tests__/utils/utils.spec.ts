import { secondsToHis, parseValidationError, ServerValidationError } from '@/utils'

describe('services/utils', () => {
  describe('#secondsToHis', () => {
    it('formats a duration to H:i:s', () => expect(secondsToHis(7547)).toBe('02:05:47'))
    it('omits hours from short duration when formats to H:i:s', () => expect(secondsToHis(314)).toBe('05:14'))
  })

  describe('#parseValidationError', () => {
    it('parses validation error', () => {
      const error: ServerValidationError = {
        message: 'The given data was invalid',
        errors: {
          email: [
            'The email has already been taken',
            'The domain is blacklisted'
          ],
          name: [
            'The name is required'
          ]
        }
      }

      expect(parseValidationError(error)).toEqual([
        'The email has already been taken',
        'The domain is blacklisted',
        'The name is required'
      ])
    })
  })
})
