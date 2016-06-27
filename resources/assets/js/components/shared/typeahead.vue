<template>
  <div>
    <input type="text"
      :placeholder="options.placeholder || 'No change'"
      v-model="mutatedValue"
      @keydown.down.prevent="down"
      @keydown.up.prevent="up"
      @keydown.enter.prevent.stop="enter"
      @keydown.tab="enter"
      @keyup="keyup"
      @click="showingResult = true"
      @blur="apply"
      v-koel-clickaway="hideResults"
    >
    <ul class="result" v-show="showingResult">
      <li v-for="item in displayedItems" @click.prevent="resultClick($event)">
        {{ item[options.displayKey] }}
      </li>
    </ul>
  </div>
</template>

<script>
import $ from 'jquery';
import { filterBy } from '../../utils';

export default {
  props: ['options', 'value', 'items'],

  data() {
    return {
      filter: '',
      showingResult: false,
      mutatedValue: this.value,
    };
  },

  computed: {
    displayedItems() {
      return filterBy(this.items, this.filter, this.options.filterKey);
    },
  },

  methods: {
    /**
     * Navigate down the result list.
     */
    down(e) {
      const selected = $(this.$el).find('.result li.selected');

      if (!selected.length || !selected.removeClass('selected').next('li').addClass('selected').length) {
        $(this.$el).find('.result li:first').addClass('selected');
      }

      this.scrollSelectedIntoView(false);
      this.apply();
    },

    /**
     * Navigate up the result list.
     */
    up(e) {
      const selected = $(this.$el).find('.result li.selected');

      if (!selected.length || !selected.removeClass('selected').prev('li').addClass('selected').length) {
        $(this.$el).find('.result li:last').addClass('selected');
      }

      this.scrollSelectedIntoView(true);
      this.apply();
    },

    /**
     * Handle ENTER or TAB keydown events.
     */
    enter() {
      this.apply();
      this.showingResult = false;
    },

    keyup(e) {
      /**
       * If it's an UP or DOWN arrow key, stop event bubbling.
       * The actually result navigation is handled by this.up() and this.down().
       */
      if (e.keyCode === 38 || e.keyCode === 40) {
        e.stopPropagation();
        e.preventDefault();

        return;
      }

      // If it's an ENTER or TAB key, don't do anything.
      // We've handled ENTER & TAB on keydown.
      if (e.keyCode === 13 || e.keyCode === 9) {
        return;
      }

      // Hide the typeahead results and reset the value if ESC is pressed.
      if (e.keyCode === 27) {
        this.showingResult = false;

        return;
      }

      this.filter = this.mutatedValue;
      this.showingResult = true;
    },

    resultClick(e) {
      $(this.$el).find('.result li.selected').removeClass('selected');
      $(e.target).addClass('selected');

      this.apply();
      this.showingResult = false;
    },

    apply() {
      this.mutatedValue = $(this.$el).find('.result li.selected').text().trim() || this.mutatedValue;
      // In Vue 2.0, we can use v-model on custom components like this.
      // $emit an 'input' event in this format, and we're set.
      this.$emit('input', { target: { value: this.mutatedValue } });
    },

    /**
     * Scroll the selected item into the view.
     *
     * @param  {boolean} alignTop Whether the item should be aligned to top of its container.
     */
    scrollSelectedIntoView(alignTop) {
      const elem = $(this.$el).find('.result li.selected')[0];
      if (!elem) {
        return;
      }

      const elemRect = elem.getBoundingClientRect();
      const containerRect = elem.offsetParent.getBoundingClientRect();

      if (elemRect.bottom > containerRect.bottom || elemRect.top < containerRect.top) {
        elem.scrollIntoView(alignTop);
      }
    },

    hideResults() {
      this.showingResult = false;
    },
  },
};
</script>

<style lang="sass" scoped>
@import   "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.result {
  position: absolute;
  background: #f2f2f2;
  max-height: 96px;
  border-radius: 0 0 3px 3px;
  width: 100%;
  overflow-y: scroll;
  z-index: 1;

  li {
    padding: 2px 8px;

    &.selected, &:hover {
    background: $colorHighlight;
    color: #fff;
    }
  }
}
</style>
