require('chai').should();

import utils from '../../services/utils';

describe('services/utils', () => {
    describe('#secondsToHis', () => {
        it('correctly formats a duration to H:i:s', () => {
            utils.secondsToHis(7547).should.equal('02:05:47');
        });

        it('ommits hours from short duration when formats to H:i:s', () => {
            utils.secondsToHis(314).should.equal('05:14');
        });
    });

    describe('#parseValidationError', () => {
        it('correctly parses single-level validation error', () => {
            let error = {
                err_1: ['Foo'],
            };

            utils.parseValidationError(error).should.eql(['Foo']);
        });

        it('correctly parses multi-level validation error', () => {
            let error = {
                err_1: ['Foo', 'Bar'],
                err_2: ['Baz', 'Qux'],
            };

            utils.parseValidationError(error).should.eql(['Foo', 'Bar', 'Baz', 'Qux']);
        });
    });
});
