import { eventBus, loadMainView, use } from '@/utils'
import { playlistStore, userStore } from '@/stores'

class Router {
  routes: Record<string, Closure>
  paths: string[]

  constructor () {
    this.routes = {
      '/home': () => loadMainView('Home'),
      '/queue': () => loadMainView('Queue'),
      '/songs': () => loadMainView('Songs'),
      '/albums': () => loadMainView('Albums'),
      '/artists': () => loadMainView('Artists'),
      '/favorites': () => loadMainView('Favorites'),
      '/recently-played': () => loadMainView('RecentlyPlayed'),
      '/search': () => loadMainView('Search.Excerpt'),
      '/search/songs/(.+)': (q: string) => loadMainView('Search.Songs', q),
      '/upload': () => userStore.current.is_admin && loadMainView('Upload'),
      '/settings': () => userStore.current.is_admin && loadMainView('Settings'),
      '/users': () => userStore.current.is_admin && loadMainView('Users'),
      '/youtube': () => loadMainView('YouTube'),
      '/visualizer': () => loadMainView('Visualizer'),
      '/profile': () => loadMainView('Profile'),
      '/album/(\\d+)': (id: string) => loadMainView('Album', parseInt(id)),
      '/artist/(\\d+)': (id: string) => loadMainView('Artist', parseInt(id)),
      '/playlist/(\\d+)': (id: string) => use(playlistStore.byId(~~id), playlist => loadMainView('Playlist', playlist)),
      '/song/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})': (id: string) => {
        eventBus.emit('SONG_QUEUED_FROM_ROUTE', id)
        loadMainView('Queue')
      }
    }

    this.paths = Object.keys(this.routes)

    addEventListener('popstate', () => this.resolveRoute(), true)
  }

  public resolveRoute () {
    if (!location.hash) {
      return this.go('home')
    }

    for (let i = 0; i < this.paths.length; i++) {
      const matches = location.hash.match(new RegExp(`^#!${this.paths[i]}$`))

      if (matches) {
        const [, ...params] = matches
        this.routes[this.paths[i]](...params)
        return
      }
    }

    loadMainView('404')
  }

  /**
   * Navigate to a (relative, hash-bang'ed) path.
   */
  public go (path: string | number) {
    if (typeof path === 'number') {
      history.go(path)
      return
    }

    if (!path.startsWith('/')) {
      path = `/${path}`
    }

    if (!path.startsWith('/#!')) {
      path = `/#!${path}`
    }

    path = path.substring(1, path.length)
    location.assign(`${location.origin}${location.pathname}${path}`)
  }
}

export default new Router()
