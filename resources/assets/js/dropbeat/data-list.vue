<template>
<div class="song-list-wrap main-scroll-wrap {{ type }}"
    v-el:wrapper
    tabindex="1"
    @scroll="scrolling">
    <div id="tmpl-search-results">
        <table class="search-results">
            <tbody>
                <tr class="a-addable-music" v-for="item in items" data-music-id="{{ item.id }}" data-music-type="{{ item.type }}" data-music-title="{{ item.title }}">
                    <td>
                        <a class="play-music">
                            <span class="music-index">{{ $index + 1 }}</span>
                            <span class="music-title">{{ item.title }}</span>
                        </a>
                    </td>
                    <td class="music-add">
                        <a class="add-to-playlist">+</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</template>
<script>
import _ from 'lodash';
import infiniteScroll from '../mixins/infinite-scroll';
import musicUpdate from '../dropbeat/musicupdate';
import utils from '../services/utils';

export default{
    props: ['items', 'type'],
    mixins: [infiniteScroll],


    ready: function () {
        if (this.type === 'dropbeat' && this.items.length >= this.numOfItems) {
            this.displayMore();
        }

    },

    methods: {

    },



};
</script>

<style>
.search-results{
    width: 100%;
}
.search-results .a-addable-music{
    border-top:1px solid #232323;
    color:white;
    height:45px;
    line-height:44px;
    font-size:12px;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    -ms-box-sizing: border-box;
    -o-box-sizing: border-box;
    box-sizing: border-box;
    padding-top:1px;
}

</style>
