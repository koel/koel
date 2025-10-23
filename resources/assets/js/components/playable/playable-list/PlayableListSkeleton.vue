<template>
  <div class="skeleton">
    <div class="song-list-header h-[35px] flex bg-k-fg-5 gap-4 px-4">
      <span class="title">
        <span class="text" />
      </span>
      <span class="album">
        <span class="text" />
      </span>
      <span class="time">
        <span class="text" />
      </span>
    </div>
    <div v-for="key in 40" :key class="flex gap-4 px-4 py-3 border-b border-k-fg-10">
      <div class="title flex gap-3">
        <div class="thumbnail block h-[48px] aspect-square rounded pulse" />
        <div class="flex-1 flex-col space-y-2 content-center">
          <div class="block h-5 rounded-full w-4/5 pulse" />
          <div class="block h-5 rounded-full w-2/5 pulse" />
        </div>
      </div>
      <div class="album flex-col content-center">
        <div class="block h-5 w-3/5 rounded-full pulse" />
      </div>
      <div class="time flex-col content-center">
        <div class="block h-5 w-3/5 rounded-full pulse" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
</script>

<style lang="postcss" scoped>
.song-list-header {
  @apply h-[35px] flex bg-k-fg-5;
}

.song-list-header > span {
  @apply flex items-center;
}

.song-list-header span span {
  @apply block h-[1.2rem] rounded-full bg-k-fg-10 w-2/5;
}

.title {
  @apply flex-1;
}

.album {
  @apply basis-[27%];
}

.time {
  @apply basis-[96px];
}

@media screen and (max-width: 768px) {
  span {
    @apply hidden;
  }

  .title,
  .album {
    @apply flex;
  }

  .song-list-header {
    @apply py-0 px-[16px];
  }
}
</style>
