require('chai').should();

import albumStore from '../../stores/album';
import artists from '../blobs/media';

describe('stores/album', () => {
    beforeEach(() => albumStore.init(artists));

    describe('#init', () => {
        it('correctly gathers albums', () => {
            albumStore.state.albums.length.should.equal(7);
        });

        it('correctly sets albums length', () => {
            albumStore.state.albums[0].length.should.equal(259.92);
        });

        it('correctly sets album artists', () => {
            albumStore.state.albums[0].artist.id.should.equal(1);
        });
    });

    describe('#all', () => {
        it('correctly returns all songs', () => {
            albumStore.all().length.should.equal(7);
        });
    });

    describe('#getLength', () => {
        it('correctly calculates an album’s length', () => {
            albumStore.getLength(albumStore.state.albums[6]);
            albumStore.state.albums[6].length.should.equal(1940.42); // I'm sorry…
        });
    });
});
