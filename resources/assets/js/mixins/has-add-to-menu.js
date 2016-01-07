/**
 * Add necessary functionalities into a (song-list type) component.
 */

import addToMenu from '../components/shared/add-to-menu.vue';

export default {
    components: { addToMenu },

    data() {
        return {
            showingAddToMenu: false,
        };
    },

    events: {
        'add-to-menu:close': function () {
            this.showingAddToMenu = false;
        },
    },
};
