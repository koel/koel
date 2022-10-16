<template>
  <a class="view-profile" data-testid="view-profile-link" href="/#/profile" title="View/edit user profile">
    <img :alt="`Avatar of ${currentUser.name}`" :src="currentUser.avatar"/>
  </a>
</template>

<script lang="ts" setup>
import { useAuthorization } from '@/composables'

const { currentUser } = useAuthorization()
</script>

<style lang="scss" scoped>
img {
  display: block;
  width: 39px;
  aspect-ratio: 1/1;
  border-radius: 50%;
  padding: 2px;
  border: 1px solid rgba(255, 255, 255, .1);
  transition: border .2s ease-in-out;

  &:hover {
    border-color: rgba(255, 255, 255, .3);
  }

  &:active {
    transform: scale(.95);
  }
}
</style>
