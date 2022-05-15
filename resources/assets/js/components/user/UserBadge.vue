<template>
  <span class="profile" id="userBadge" v-if="currentUser">
    <a class="view-profile" data-testid="view-profile-link" href="/#!/profile" title="View/edit user profile">
      <img :alt="`Avatar of ${currentUser.name}`" :src="currentUser.avatar" class="avatar"/>
      <span class="name">{{ currentUser.name }}</span>
    </a>

    <a
      title="Log out"
      class="logout control"
      data-testid="btn-logout"
      href
      role="button"
      @click.prevent="logout"
    >
      <i class="fa fa-sign-out"></i>
    </a>
  </span>
</template>

<script lang="ts" setup>
import { eventBus } from '@/utils'
import { useAuthorization } from '@/composables'

const { currentUser } = useAuthorization()

const logout = () => eventBus.emit('LOG_OUT')
</script>

<style lang="scss">
#userBadge {
  @include vertical-center();

  justify-content: flex-end;
  flex: 0 0 var(--extra-panel-width);
  text-align: right;
  height: 100%;
  position: relative;

  .avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: .5rem;
  }

  .view-profile {
    height: 100%;
    @include vertical-center();
  }

  .logout {
    height: 100%;
    padding: 0 1.25rem;
    @include vertical-center();
  }

  @media only screen and (max-width: 667px) {
    flex: 0 0 96px;
    margin-right: 0;
    padding-right: 0;
    align-content: stretch;

    .name {
      display: none;
    }

    .view-profile, .logout {
      flex: 0 0 40px;
      font-size: 1.4rem;
      margin-right: 0;

      @include vertical-center();
    }
  }
}
</style>
