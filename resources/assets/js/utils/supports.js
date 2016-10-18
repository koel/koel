import isMobile from 'ismobilejs';
import Vue from 'vue'

/**
 * Check if AudioContext is supported by the current browser.
 *
 * @return {Boolean}
 */
export function isAudioContextSupported() {
  // Apple device just doesn't love AudioContext that much.
  if (isMobile.apple.device) {
    return false;
  }

  const AudioContext = (window.AudioContext ||
    window.webkitAudioContext ||
    window.mozAudioContext ||
    window.oAudioContext ||
    window.msAudioContext);

  if (!AudioContext) {
    return false;
  }

  // Safari (MacOS & iOS alike) has webkitAudioContext, but is buggy.
  // @link http://caniuse.com/#search=audiocontext
  if (!(new AudioContext()).createMediaElementSource) {
    return false;
  }

  return true;
};

/**
 * Checks if HTML5 clipboard can be used.
 *
 * @return {Boolean}
 */
export function isClipboardSupported() {
  return 'execCommand' in document;
};

/**
 * A simple event bus.
 *
 * @type {Object}
 */
const event = {
  bus: null,

  init() {
    if (!this.bus) {
      this.bus = new Vue();
    }

    return this;
  },

  emit(name, ...args) {
    this.bus.$emit(name, ...args);
    return this;
  },

  on() {
    if (arguments.length === 2) {
      this.bus.$on(arguments[0], arguments[1]);
    } else {
      Object.keys(arguments[0]).forEach(key => {
        this.bus.$on(key, arguments[0][key]);
      });
    }

    return this;
  },
}

export { event };
