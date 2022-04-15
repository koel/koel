<template>
  <i class="fa fa-question-circle help-trigger text-blue" :title="title"/>
</template>

<script lang="ts">
import Vue from 'vue'

export default Vue.extend({
  props: {
    title: {
      type: String,
      required: true
    }
  }
})
</script>
