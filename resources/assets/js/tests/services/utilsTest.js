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
});
