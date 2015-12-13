require('chai').should();

import localStorage from 'local-storage';
import preferenceStore from '../../stores/preference';

let user = { id: 0 };
let preferences = {
    volume: 8,
    notify: false,
};

describe('stores/preference', () => {
    beforeEach(() => {
        localStorage.set(`preferences_${user.id}`, preferences);
        preferenceStore.init(user);
    });

    describe("#set", () => {
        it('correctly sets preferences', () => {
            preferenceStore.set('volume', 5);
            localStorage.get(`preferences_${user.id}`).volume.should.equal(5);
        });
    });

    describe("#get", () => {
        it('returns correct preference values', () => {
            preferenceStore.get('volume').should.equal(8);
        });
    });
});
