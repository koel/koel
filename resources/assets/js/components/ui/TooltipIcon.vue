<template>
  <i :title="title" class="fa fa-question-circle help-trigger text-blue"/>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = defineProps<{ title: string }>()
const { title } = toRefs(props)
</script>
