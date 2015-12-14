require('chai').should();

import artistStore from '../../stores/artist';
import artists from '../blobs/media';

describe('stores/artist', () => {
    beforeEach(() => artistStore.init(artists));

    describe('#init', () => {
        it('correctly gathers artists', () => {
            artistStore.state.artists.length.should.equal(3);
        });

        it('correctly gets artists’ covers', () => {
            artistStore.state.artists[0].cover.should.equal('/public/img/covers/565c0f7067425.jpeg');
        });

        it('correctly counts songs by artists', () => {
            artistStore.state.artists[0].songCount = 3;
        });
    });

    describe('#getSongsByArtist', () => {
        it('correctly gathers all songs by artist', () => {
            artistStore.getSongsByArtist(artistStore.state.artists[0]).length.should.equal(3);
        });
    });

    describe('#getCover', () => {
        it('correctly gets an artist’s cover', () => {
            artistStore.getCover(artistStore.state.artists[0]).should.equal('/public/img/covers/565c0f7067425.jpeg');
        });
    });
});
