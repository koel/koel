import isMobile from 'ismobilejs';

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
