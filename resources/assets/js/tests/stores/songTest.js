require('chai').should();
import { cloneDeep, last } from 'lodash';

import { songStore, albumStore, artistStore } from '../../stores';
import artists from '../blobs/media';
import interactions from '../blobs/interactions';

describe('stores/song', () => {
  beforeEach(() => {
    artistStore.init(artists);
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
      songStore.all.length.should.equal(14);
    });
  });

  describe('#byId', () => {
    it('correctly gets a song by ID', () => {
      songStore.byId('e6d3977f3ffa147801ca5d1fdf6fa55e').title.should.equal('Like a rolling stone');
    });
  });

  describe('#byIds', () => {
    it('correctly gets multiple songs by IDs', () => {
      const songs = songStore.byIds(['e6d3977f3ffa147801ca5d1fdf6fa55e', 'aa16bbef6a9710eb9a0f41ecc534fad5']);
      songs[0].title.should.equal('Like a rolling stone');
      songs[1].title.should.equal("Knockin' on heaven's door");
    });
  });

  describe('#initInteractions', () => {
    beforeEach(() => songStore.initInteractions(interactions));

    it('correctly sets interaction status', () => {
      const song = songStore.byId('cb7edeac1f097143e65b1b2cde102482');
      song.liked.should.be.true;
      song.playCount.should.equal(3);
    });
  });

  describe('#syncUpdatedSong', () => {
    beforeEach(() => artistStore.init(artists));

    const updatedSong = {
      id: "39189f4545f9d5671fb3dc964f0080a0",
      album_id: 1193,
      title: "I Swear A Lot",
      album: {
        id: 1193,
        arist_id: 1,
        artist: {
          id: 1,
          name: 'All-4-One',
        },
      },
    };

    it ('correctly syncs an updated song with no album changes', () => {
      songStore.syncUpdatedSong(cloneDeep(updatedSong));
      songStore.byId(updatedSong.id).title.should.equal('I Swear A Lot');
    });

    it ('correctly syncs an updated song into an existing album of same artist', () => {
      const song = cloneDeep(updatedSong);
      song.album_id = 1194;
      song.album = {
        id: 1194,
        artist_id: 1,
        artist: {
          id: 1,
          name: 'All-4-One',
        },
      };

      songStore.syncUpdatedSong(song);
      songStore.byId(song.id).album.name.should.equal('And The Music Speaks');
    });

    it ('correctly syncs an updated song into a new album of same artist', () => {
      const song = cloneDeep(updatedSong);
      song.album_id = 9999;
      song.album = {
        id: 9999,
        artist_id: 1,
        name: 'Brand New Album from All-4-One',
        artist: {
          id: 1,
          name: 'All-4-One',
        },
      };

      songStore.syncUpdatedSong(song);

      // A new album should be created...
      last(albumStore.all).name.should.equal('Brand New Album from All-4-One');

      // ...and assigned with the song.
      songStore.byId(song.id).album.name.should.equal('Brand New Album from All-4-One');
    });

    it ('correctly syncs an updated song into a new album of a new artist', () => {
      const song = cloneDeep(updatedSong);
      song.album_id = 10000;
      song.album = {
        id: 10000,
        name: "It's... John Cena!!!",
        artist_id: 10000,
        artist: {
          id: 10000,
          name: 'John Cena',
        },
      };

      songStore.syncUpdatedSong(song);

      // A new artist should be created...
      const lastArtist = last(artistStore.all);
      lastArtist.name.should.equal('John Cena');

      // A new album should be created
      const lastAlbum = last(albumStore.all);
      lastAlbum.name.should.equal("It's... John Cena!!!");

      // The album must belong to John Cena of course!
      last(lastArtist.albums).should.equal(lastAlbum);

      // And the song belongs to the album.
      songStore.byId(song.id).album.should.equal(lastAlbum);
    });
  });
});
