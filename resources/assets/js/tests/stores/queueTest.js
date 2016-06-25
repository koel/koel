require('chai').should();

import { queueStore } from '../../stores';
import artists from '../blobs/media';

const songs = artists[2].albums[0].songs;

describe('stores/queue', () => {
  beforeEach(() => {
    queueStore.state.songs = songs;
    queueStore.state.current = songs[1];
  });

  describe('#all', () => {
    it('correctly returns all queued songs', () => {
      queueStore.all.should.equal(songs);
    });
  });

  describe('#first', () => {
    it('correctly returns the first queued song', () => {
      queueStore.first.title.should.equal('No bravery');
    });
  });

  describe('#last', () => {
    it('correctly returns the last queued song', () => {
      queueStore.last.title.should.equal('Tears and rain');
    });
  });

  describe('#queue', () => {
    beforeEach(() => queueStore.state.songs = songs);

    const song = artists[0].albums[0].songs[0];

    it('correctly appends a song to end of the queue', () => {
      queueStore.queue(song);
      queueStore.last.title.should.equal('I Swear');
    });

    it('correctly prepends a song to top of the queue', () => {
      queueStore.queue(song, false, true);
      queueStore.first.title.should.equal('I Swear');
    });

    it('correctly replaces the whole queue', () => {
      queueStore.queue(song, true);
      queueStore.all.length.should.equal(1);
      queueStore.first.title.should.equal('I Swear');
    });
  });

  describe('#unqueue', () => {
    beforeEach(() => queueStore.state.songs = songs);

    it('correctly removes a song from queue', () => {
      queueStore.unqueue(queueStore.state.songs[0]);
      queueStore.first.title.should.equal('So long, Jimmy'); // Oh the irony.
    });

    it('correctly removes mutiple songs from queue', () => {
      queueStore.unqueue([queueStore.state.songs[0], queueStore.state.songs[1]]);
      queueStore.first.title.should.equal('Wisemen');
    });
  });

  describe('#clear', () => {
    it('correctly clears all songs from queue', () => {
      queueStore.clear();
      queueStore.state.songs.length.should.equal(0);
    });
  });

  describe('#current', () => {
    it('returns the correct current song', () => {
      queueStore.current.title.should.equal('So long, Jimmy');
    });

    it('successfully sets the current song', () => {
      queueStore.current = queueStore.state.songs[0];
      queueStore.current.title.should.equal('No bravery');
    });
  });

  describe('#getNextSong', () => {
    it('correctly gets the next song in queue', () => {
      queueStore.next.title.should.equal('Wisemen');
    });

    it('correctly returns null if at end of queue', () => {
      queueStore.current = queueStore.state.songs[queueStore.state.songs.length - 1];
      (queueStore.next === null).should.be.true;
    });
  });

  describe('#getPrevSong', () => {
    it('correctly gets the previous song in queue', () => {
      queueStore.previous.title.should.equal('No bravery');
    });

    it('correctly returns null if at end of queue', () => {
      queueStore.current = queueStore.state.songs[0];
      (queueStore.previous === null).should.be.true;
    });
  });
});
