import { trim, unionBy } from 'lodash'
import { http } from '@/services/http'
import { songStore } from '@/stores/songStore'
import { commonStore } from '@/stores/commonStore'
import { cache } from '@/services/cache'
import { md5 } from '@/utils/crypto'

export type ResolvableData = Pick<Folder, 'type' | 'path'> | Pick<Song, 'type' | 'id'>

export const mediaBrowser = {
  async browse (path: string | null, page = 1) {
    const [folders, paginator] = await Promise.all([
      cache.remember<Folder[]>(
        ['folder', path, 'folders'],
        () => http.get<Folder[]>(`browse/folders/?path=${path ?? ''}`),
      ),
      cache.remember<PaginatorResource<Song>>(
        ['folder', path, 'songs', page],
        () => http.get<PaginatorResource<Song>>(`browse/songs/?path=${path ?? ''}&page=${page}`),
      ),
    ])

    return {
      subfolders: folders,
      songs: songStore.syncWithVault(paginator.data) as Song[],
      nextPage: paginator.links.next ? ++paginator.meta.current_page : null,
    }
  },

  getBreadcrumbs (path: string | null) {
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

    const breadcrumbs = this.getBreadcrumbs(path)

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

  async resolveSongsFromIdsAndFolderPaths (data: Array<ResolvableData>, shuffle = false) {
    const songData = data.filter(item => item.type === 'songs') as Array<Pick<Song, 'type' | 'id'>>
    const songs = songStore.byIds(songData.map(item => item.id)) as Song[]

    const folderData = data.filter(item => item.type === 'folders') as Array<Pick<Folder, 'type' | 'path'>>

    if (!folderData.length) {
      return songs
    }

    const folderPaths = folderData.map(item => item.path).sort()

    // since paths can be long, we use a hash instead
    const cacheKey = ['folders', md5(folderPaths.join(','))]

    const fetcher = () => http.post<Song[]>(`songs/by-folders?shuffle=${shuffle}`, { paths: folderPaths })

    const songsFromFolders = songStore.syncWithVault(
      shuffle ? await fetcher() : await cache.remember<Song[]>(cacheKey, async () => await fetcher()),
    )

    return unionBy(songs, songsFromFolders as Song[], 'id')
  },
}
