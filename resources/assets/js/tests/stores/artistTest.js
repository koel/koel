require('chai').should();
import _ from 'lodash';

import artistStore from '../../stores/artist';
import { default as artists, singleAlbum, singleArtist } from '../blobs/media';

describe('stores/artist', () => {
    beforeEach(() => artistStore.init(_.cloneDeep(artists)));
    afterEach(() => artistStore.state.artists = []);

    describe('#init', () => {
        it('correctly gathers artists', () => {
            artistStore.state.artists.length.should.equal(3);
        });

        it('correctly gets artist images', () => {
            artistStore.state.artists[0].image.should.equal('/public/img/covers/565c0f7067425.jpeg');
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

    describe('#getImage', () => {
        it('correctly gets an artist’s image', () => {
            artistStore.getImage(artistStore.state.artists[0]).should.equal('/public/img/covers/565c0f7067425.jpeg');
        });
    });

    describe('#append', () => {
        beforeEach(() => artistStore.append(_.cloneDeep(singleArtist)));

        it('correctly appends an artist', () => {
            _.last(artistStore.state.artists).name.should.equal('John Cena');
        });
    });

    describe('#remove', () => {
        beforeEach(() => artistStore.remove(artistStore.state.artists[0]));

        it('correctly removes an artist', () => {
            artistStore.state.artists.length.should.equal(2);
            artistStore.state.artists[0].name.should.equal('Bob Dylan');
        });
    });

    describe('#addAlbumsIntoArtist', () => {
        beforeEach(() => {
            artistStore.addAlbumsIntoArtist(artistStore.state.artists[0], _.cloneDeep(singleAlbum));
        });

        it('correctly adds albums into an artist', () => {
            artistStore.state.artists[0].albums.length.should.equal(4);
        });

        it('correctly sets the album artist', () => {
            var addedAlbum = _.last(artistStore.state.artists[0].albums);
            addedAlbum.artist.should.equal(artistStore.state.artists[0]);
            addedAlbum.artist_id.should.equal(artistStore.state.artists[0].id);
        });
    });

    describe('#removeAlbumsFromArtist', () => {
        beforeEach(() => {
            artistStore.removeAlbumsFromArtist(artistStore.state.artists[0], artistStore.state.artists[0].albums[0]);
        });

        it('correctly removes an album from an artist', () => {
            artistStore.state.artists[0].albums.length.should.equal(2);
        });
    });
});
