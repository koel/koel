import { secondsToHis, parseValidationError } from '../../utils'

describe('services/utils', () => {
  describe('#secondsToHis', () => {
    it('correctly formats a duration to H:i:s', () => {
      secondsToHis(7547).should.equal('02:05:47')
    })

    it('ommits hours from short duration when formats to H:i:s', () => {
      secondsToHis(314).should.equal('05:14')
    })
  })

  describe('#parseValidationError', () => {
    it('correctly parses single-level validation error', () => {
      const error = {
        err_1: ['Foo']
      }

      parseValidationError(error).should.eql(['Foo'])
    })

    it('correctly parses multi-level validation error', () => {
      const error = {
        err_1: ['Foo', 'Bar'],
        err_2: ['Baz', 'Qux']
      }

      parseValidationError(error).should.eql(['Foo', 'Bar', 'Baz', 'Qux'])
    })
  })
})
