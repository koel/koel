import Vue from 'vue';

/**
 * A simple directive to set focus into an input field when it's shown.
 */
export default {
    update(el, { value }) {
        if (!value) {
            return;
        }

        Vue.nextTick(() => el.focus());
    },
};
