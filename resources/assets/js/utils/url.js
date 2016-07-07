/**
 * URL-related helpers
 * @type {Object}
 */
export const url = {
  /**
   * Parse the song ID from a hash.
   *
   * @param  {string} hash
   *
   * @return {string|boolean}
   */
  parseSongId(hash = null) {
    if (!hash) {
      hash = window.location.hash;
    }

    const matches = hash.match(/#!\/song\/([a-f0-9]{32}$)/);
    return matches ? matches[1] : false;
  },
};
