import { http, albumInfo, artistInfo } from '..';

export const songInfo = {
  /**
   * Get extra song information (lyrics, artist info, album info).
   *
   * @param  {Object}   song
   */
  fetch(song) {
    return new Promise((resolve, reject) => {
      // Check if the song's info has been retrieved before.
      if (song.infoRetrieved) {
        resolve(song);
        return;
      }

      http.get(`${song.id}/info`, data => {
        song.lyrics = data.lyrics;
        data.artist_info && artistInfo.merge(song.artist, data.artist_info);
        data.album_info && albumInfo.merge(song.album, data.album_info);
        song.youtube = data.youtube;
        song.infoRetrieved = true;
        resolve(song);
      }, r => reject(r));
    });
  },
};
