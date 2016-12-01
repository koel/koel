require('chai').should()

import localStorage from 'local-storage'
import { ls } from '../../services'

describe('services/ls', () => {
  beforeEach(() => localStorage.remove('foo'))

  describe('#get', () => {
    it('correctly gets an existing item from local storage', () => {
      localStorage('foo', 'bar')
      ls.get('foo').should.equal('bar')
    })

    it('correctly returns the default value for a non exising item', () => {
      ls.get('baz', 'qux').should.equal('qux')
    })
  })

  describe('#set', () => {
    it('correctly sets an item into local storage', () => {
      ls.set('foo', 'bar')
      localStorage('foo').should.equal('bar')
    })
  })

  describe('#remove', () => {
    it('correctly removes an item from local storage', () => {
      localStorage('foo', 'bar')
      ls.remove('foo')
      var result = localStorage('foo') === null
      result.should.be.true
    })
  })
})
