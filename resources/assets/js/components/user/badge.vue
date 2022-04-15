<template>
  <span class="profile" id="userBadge">
    <a class="view-profile" href="/#!/profile" title="View/edit user profile" data-testid="view-profile-link">
      <img class="avatar" :src="state.current.avatar" :alt="`Avatar of ${state.current.name}`"/>
      <span class="name">{{ state.current.name }}</span>
    </a>

    <a
      :title="`Log ${state.current.name} out`"
      @click.prevent="logout"
      class="logout control"
      data-testid="btn-logout"
      href
      role="button"
    >
      <i class="fa fa-sign-out"></i>
    </a>
  </span>
</template>

<script lang="ts">
import Vue from 'vue'
import { userStore } from '@/stores'
import { eventBus } from '@/utils'

export default Vue.extend({
  data: () => ({
    state: userStore.state
  }),

  methods: {
    logout: (): void => {
      eventBus.emit('LOG_OUT')
    }
  }
})
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

  @media only screen and (max-width : 667px) {
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
