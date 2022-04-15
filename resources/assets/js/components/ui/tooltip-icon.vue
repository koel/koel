<template>
  <i class="fa fa-question-circle help-trigger text-blue" :title="title"/>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = defineProps<{ title: string }>()
const { title } = toRefs(props)
</script>
