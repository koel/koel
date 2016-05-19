<template>
    <section>
    <div class="jumbotron hero-technology">
        <h1 class="hero-title">Dropbeat is the easiest<br> way to enjoy streaming music </h1>
        <p class="hero-subtitle">Why are you suffering from poor interface of Youtube, Soundcloud? <br> Just start Dropbeat. </p>
        <input id="search-input" class="input-lg" type="text" size="40" placeholder="Search Music">
        <button id="search-button" class="btn btn-default btn-lg" type="button"><strong>Search</strong> </button>
    </div>
    <!-- <div class="search-result-section"></div> -->
    <div class="external-player-section">
        <div id="external-player">
            <div id="youtube-player"></div>
            <div id="soundcloud-player"></div>
        </div>
    </div>

    <!-- <div class="plyr">
            <video controls crossorigin></video>
    </div> -->

    <data-list class="search-result-section" v-show="state.results.length" :items="state.results"></data-list>
    </section>
</template>



<script>
import dropbeat from '../../../dropbeat/dropbeat';
import feature from '../../../dropbeat/feature';
import dataList from '../../../dropbeat/data-list.vue';


import $ from 'jquery';
import _ from 'lodash';

export default {
    components: { dataList },

    data: function() {
        return {
            state: feature.state
        };
    },


    ready() {
        dropbeat.initialize();
        dropbeat.onYouTubeIframeAPIReady();
    },



};

</script>

<style>

input.input-lg{
  margin:20px;
  border-radius:30px;
  border: 0;
  outline: 0;
  display: inline-block;
}
button{
    border: 0;
    outline: 0;
}

div.jumbotron.hero-technology{
  text-align:center;
  background:url('');
  background-size:cover;
  background-position:center;
  padding-top:20px;
  padding-bottom:10px;
}

h1.hero-title, h2.hero-title, h3.hero-title{
  text-shadow:0 2px 2px rgba(0,0,0,0.3);
  color:#fff;
  font-weight:500;
}

p.hero-subtitle{
  max-width:600px;
  margin:10px auto;
  background-color:rgba(0,0,0,0.1);
  color:#fff;
  text-shadow:0 1px 1px rgba(0,0,0,0.7);
  font-weight:500;
}
button .btn{
    border: 0;
    outline: 0;
}

a.btn.btn-primary.btn-lg.hero-button{

}

.external-player-section{
    height: 0%;
    visibility:hidden;
}
</style>
