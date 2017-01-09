<template>
  <thead>
    <tr>
      <th @click="sort('track')" class="track-number">#
        <i class="fa fa-angle-down" v-show="sortKey === 'track' && order > 0"/>
        <i class="fa fa-angle-up" v-show="sortKey === 'track' && order < 0"/>
      </th>
      <th @click="sort('title')" class="title">Title
        <i class="fa fa-angle-down" v-show="sortKey === 'title' && order > 0"/>
        <i class="fa fa-angle-up" v-show="sortKey === 'title' && order < 0"/>
      </th>
      <th @click="sort(['album.artist.name', 'album.name', 'track'])" class="artist">Artist
        <i class="fa fa-angle-down" v-show="sortingByArtist && order > 0"/>
        <i class="fa fa-angle-up" v-show="sortingByArtist && order < 0"/>
      </th>
      <th @click="sort(['album.name', 'track'])" class="album">Album
        <i class="fa fa-angle-down" v-show="sortingByAlbum && order > 0"/>
        <i class="fa fa-angle-up" v-show="sortingByAlbum && order < 0"/>
      </th>
      <th @click="sort('length')" class="time">Time
        <i class="fa fa-angle-down" v-show="sortKey === 'length' && order > 0"/>
        <i class="fa fa-angle-up" v-show="sortKey === 'length' && order < 0"/>
      </th>
      <th class="play"></th>
    </tr>
  </thead>
</template>

<script>
export default {
  data () {
    return {
      sortKey: '',
      order: 1
    }
  },

  methods: {
    sort (key) {
      console.log(this.$parent.$parent)
    }
  }
}
</script>
