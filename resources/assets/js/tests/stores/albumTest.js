require('chai').should();
import { cloneDeep, last } from 'lodash';

import { albumStore, artistStore, genreStore } from '../../stores';
import { default as artists, singleAlbum, singleSong, genres } from '../blobs/media';

describe('stores/album', () => {
  beforeEach(() => { genreStore.init(cloneDeep(genres)); albumStore.init(cloneDeep(artists)); });

  afterEach(() => { genreStore.state.genres = []; albumStore.state.albums = []; });

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
      albumStore.all.length.should.equal(7);
    });
  });

  describe('#getLength', () => {
    it('correctly calculates an album’s length', () => {
      albumStore.getLength(albumStore.state.albums[6]);
      albumStore.state.albums[6].length.should.equal(1940.42); // I'm sorry…
    });
  });

  describe('#add', () => {
    beforeEach(() => {
      albumStore.add(cloneDeep(singleAlbum));
    });

    it('correctly adds a new album into the state', () => {
      last(albumStore.state.albums).id.should.equal(9999);
    });

    it('correctly recalculates the length', () => {
      last(albumStore.state.albums).length.should.equal(300);
    });

    it('correctly recalculates the play count', () => {
      last(albumStore.state.albums).playCount.should.equal(11);
    });
  });

  describe('#remove', () => {
    beforeEach(() => {
      albumStore.remove(albumStore.state.albums[0]); // ID 1193
    });

    it('correctly removes an album', () => {
      albumStore.state.albums.length.should.equal(6);
    });
  });

  describe('#addSongsIntoAlbum', () => {
    beforeEach(() => {
      albumStore.addSongsIntoAlbum(albumStore.state.albums[0], cloneDeep(singleSong));
    });

    it('correctly adds a song into an album', () => {
      albumStore.state.albums[0].songs.length.should.equal(2);
    });

    it('correctly recalculates the play count', () => {
      albumStore.state.albums[0].playCount.should.equal(4);
    });

    it ('correctly recalculates album length', () => {
      albumStore.state.albums[0].length.should.equal(359.92);
    });
  });

  describe('#removeSongsFromAlbum', () => {
    beforeEach(() => {
      albumStore.removeSongsFromAlbum(albumStore.state.albums[0], albumStore.state.albums[0].songs[0]);
    });

    it('correctly removes a song from an album', () => {
      albumStore.state.albums[0].songs.length.should.equal(0);
    });

    it('correctly recalculates the play count', () => {
      albumStore.state.albums[0].playCount.should.equal(0);
    });

    it('correctly recalculates the length', () => {
      albumStore.state.albums[0].length.should.equal(0);
    });
  });
});
