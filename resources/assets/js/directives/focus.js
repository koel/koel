import Vue from 'vue';

/**
 * A simple directive to set focus into an input field when it's shown.
 */
export default function (value) {
    if (!value) {
        return;
    }

    Vue.nextTick(() => this.el.focus());
};
