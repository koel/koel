import { trim } from 'lodash'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { commonStore } from '@/stores/commonStore'
import { cache } from '@/services/cache'

export const mediaBrowser = {
  async browse (path: string | null, page = 1, forceRefresh = false) {
    if (forceRefresh) {
      cache.remove(['folder', path, 'folders'])
      cache.remove(['folder', path, 'songs', page])
    }

    const [folders, paginator] = await Promise.all([
      cache.remember(
        ['folder', path, 'folders'],
        () => http.get<Folder[]>(`browse/folders?path=${path ?? ''}`),
      ),
      cache.remember(
        ['folder', path, 'songs', page],
        () => http.get<PaginatorResource<Song>>(`browse/songs?path=${path ?? ''}&page=${page}`),
      ),
    ])

    return {
      subfolders: folders,
      songs: playableStore.syncWithVault(paginator.data) as Song[],
      nextPage: paginator.links.next ? ++paginator.meta.current_page : null,
    }
  },

  generateBreadcrumbs (path: string | null) {
    const sep = commonStore.state.dir_separator
    path = path || ''

    const results = [{
      name: 'Library',
      path: '',
    }]

    const segments = path.split(sep).filter(Boolean)

    for (let i = 0, j = segments.length; i < j; i++) {
      const partial = sep + segments.slice(0, i + 1).join(sep)

      results.push({
        name: decodeURI(segments[i]),
        path: trim(partial, sep),
      })
    }

    return results
  },

  getParentFolder (path: string | null): Folder | null {
    path = path || ''

    if (!path) {
      return null
    }

    const breadcrumbs = this.generateBreadcrumbs(path)

    if (breadcrumbs.length < 2) {
      return null
    }

    // This is strictly not a "folder" type, but a faux type, since we don't know
    // (nor care for) the actual parent folder's details apart from its path.
    return {
      type: 'folders',
      id: '..',
      parent_id: '..',
      name: '..',
      path: breadcrumbs[breadcrumbs.length - 2].path,
    }
  },

  extractMediaReferences (items: (Song | Folder)[]) {
    return items.map<MediaReference>(item => {
      if (item.type === 'songs') {
        return {
          type: item.type,
          id: item.id,
        }
      }

      return {
        type: item.type,
        path: item.path,
      }
    })
  },
}
