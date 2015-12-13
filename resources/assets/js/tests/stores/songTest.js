require('chai').should();

import songStore from '../../stores/song';
import albumStore from '../../stores/album';
import artists from '../blobs/media';
import interactions from '../blobs/interactions';

describe('stores/song', () => {
    beforeEach(() => {
        // This is ugly and not very "unit," but anyway.
        albumStore.init(artists);
        songStore.init(albumStore.all(), interactions);
    });

    describe('#init', () => {
        it('correctly gathers all songs', () => {
            songStore.state.songs.length.should.equal(14);
        });

        it ('coverts lengths to formatted lengths', () => {
            songStore.state.songs[0].fmtLength.should.be.a.string;
        });

        it('correctly sets albums', () => {
            songStore.state.songs[0].album.id.should.equal(1193);
        });
    });

    describe('#all', () => {
        it('correctly returns all songs', () => {
            songStore.all().length.should.equal(14);
        });
    });

    describe('#byId', () => {
        it('correctly gets a song by ID', () => {
            songStore.byId('e6d3977f3ffa147801ca5d1fdf6fa55e').title.should.equal('Like a rolling stone');
        });
    });

    describe('#byIds', () => {
        it('correctly gets multiple songs by IDs', () => {
            let songs = songStore.byIds(['e6d3977f3ffa147801ca5d1fdf6fa55e', 'aa16bbef6a9710eb9a0f41ecc534fad5']);
            songs[0].title.should.equal('Like a rolling stone');
            songs[1].title.should.equal("Knockin' on heaven's door");
        });
    });

    describe('#setInteractionStats', () => {
        it('correctly sets interaction status', () => {
            let song = songStore.byId('cb7edeac1f097143e65b1b2cde102482');
            song.liked.should.be.true;
            song.playCount.should.equal(3);
        });
    });
});
