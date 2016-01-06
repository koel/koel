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

    methods: {
        scrolling(e) {
            var $wrapper = $(this.$els.wrapper);

            // Here we check if the user has scrolled to the end of the wrapper (or 32px to the end).
            // If that's true, load more items.
            if ($wrapper.scrollTop() + $wrapper.innerHeight() >= $wrapper[0].scrollHeight - 32) {
                this.displayMore();
            }
        },

        displayMore() {
            this.numOfItems += this.perPage;

            if (this.numOfItems > this.items.length) {
                this.numOfItems = this.items.length;
            }
        },
    },

    events: {
        'addToMenu:close': function () {
            this.showingAddToMenu = false;
        },
    },
};
