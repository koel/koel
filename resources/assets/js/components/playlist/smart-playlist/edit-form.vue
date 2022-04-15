<template>
  <form-base>
    <div @keydown.esc="maybeClose">
      <sound-bar v-if="meta.loading"/>
      <form @submit.prevent="submit" v-else data-testid="edit-smart-playlist-form">
        <header>
          <h1>Edit Smart Playlist</h1>
        </header>

        <div>
          <div class="form-row">
            <label>Name</label>
            <input type="text" v-model="mutatedPlaylist.name" name="name" v-koel-focus required>
          </div>

          <div class="form-row rules">
            <rule-group
              v-for="(group, index) in mutatedPlaylist.rules"
              :isFirstGroup="index === 0"
              :key="group.id"
              :group="group"
              @input="onGroupChanged"
            />
            <btn @click.prevent="addGroup" class="btn-add-group" green small uppercase>
              <i class="fa fa-plus"></i> Group
            </btn>
          </div>
        </div>

        <footer>
          <btn type="submit">Save</btn>
          <btn white class="btn-cancel" @click.prevent="maybeClose">Cancel</btn>
        </footer>
      </form>
    </div>
  </form-base>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { playlistStore } from '@/stores'
import { alerts } from '@/utils'

export default Vue.extend({
  components: {
    FormBase: () => import('@/components/playlist/smart-playlist/form-base.vue'),
    RuleGroup: () => import('@/components/playlist/smart-playlist/rule-group.vue'),
    SoundBar: () => import('@/components/ui/sound-bar.vue'),
    Btn: () => import('@/components/ui/btn.vue')
  },

  props: {
    playlist: {
      required: true,
      type: Object
    } as PropOptions<Playlist>
  },

  data: () => ({
    meta: {
      loading: false
    },
    mutatedPlaylist: null as unknown as Playlist
  }),

  methods: {
    addGroup (): void {
      this.mutatedPlaylist.rules.push(this.createGroup())
    },

    onGroupChanged (data: SmartPlaylistRuleGroup): void {
      const changedGroup = Object.assign(this.mutatedPlaylist.rules.find(g => g.id === data.id), data)

      // Remove empty group
      if (changedGroup.rules.length === 0) {
        this.mutatedPlaylist.rules = this.mutatedPlaylist.rules.filter(group => group.id !== changedGroup.id)
      }
    },

    close (): void {
      this.$emit('close')
    },

    maybeClose (): void {
      if (isEqual(this.playlist, this.mutatedPlaylist)) {
        this.close()
        return
      }

      alerts.confirm('Discard all changes?', () => this.close())
    },

    async submit (): Promise<void> {
      this.meta.loading = true
      await playlistStore.update(this.mutatedPlaylist)
      Object.assign(this.playlist, this.mutatedPlaylist)
      this.meta.loading = false
      this.close()
      await playlistStore.fetchSongs(this.playlist)
    },

    createGroup: (): SmartPlaylistRuleGroup => playlistStore.createEmptySmartPlaylistRuleGroup()
  },

  created (): void {
    // use cloneDeep instead of Object.assign because we don't want references to playlist's rules
    this.mutatedPlaylist = cloneDeep(this.playlist)
  }
})
</script>
