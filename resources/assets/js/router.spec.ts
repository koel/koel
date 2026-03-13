import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import Router from './router'

describe('Router', () => {
  let router: Router

  beforeEach(() => {
    location.hash = ''
    router = new Router()
  })

  describe('compileRoute', () => {
    it('compiles a static route', () => {
      const compiled = router.compileRoute({ path: '/home', screen: 'Home' })

      expect(compiled.regex.test('/home')).toBe(true)
      expect(compiled.regex.test('/home/')).toBe(true)
      expect(compiled.regex.test('/other')).toBe(false)
      expect(compiled.paramNames).toEqual([])
    })

    it('compiles a route with a required param', () => {
      const compiled = router.compileRoute({ path: '/albums/:id', screen: 'Album' })

      expect(compiled.regex.test('/albums/123')).toBe(true)
      expect(compiled.regex.test('/albums')).toBe(false)
      expect(compiled.paramNames).toEqual(['id'])
    })

    it('compiles a route with an optional param', () => {
      const compiled = router.compileRoute({ path: '/albums/:id/:tab?', screen: 'Album' })

      expect(compiled.regex.test('/albums/123')).toBe(true)
      expect(compiled.regex.test('/albums/123/songs')).toBe(true)
      expect(compiled.paramNames).toEqual(['id', 'tab'])
    })

    it('compiles a route with constraints', () => {
      const compiled = router.compileRoute({
        path: '/albums/:id/:tab?',
        screen: 'Album',
        constraints: {
          id: '[0-9]+',
          tab: '(songs|information)',
        },
      })

      expect(compiled.regex.test('/albums/123')).toBe(true)
      expect(compiled.regex.test('/albums/123/songs')).toBe(true)
      expect(compiled.regex.test('/albums/abc')).toBe(false)
      expect(compiled.regex.test('/albums/123/invalid')).toBe(false)
    })
  })

  describe('resolve', () => {
    it('redirects empty hashes to home', () => {
      const goSpy = vi.spyOn(Router, 'go').mockImplementation(() => {})

      for (const hash of ['', '#/', '#!/']) {
        router.resolve(hash)
        expect(goSpy).toHaveBeenCalledWith('/home')
      }
    })

    it('resolves a matching route', () => {
      const route = router.resolve('#/songs')

      expect(route).not.toBeNull()
      expect(route!.screen).toBe('Songs')
    })

    it('resolves a route with params', () => {
      const route = router.resolve('#/genres/rock')

      expect(route).not.toBeNull()
      expect(route!.screen).toBe('Genre')
      expect(router.$currentRoute.value.params).toEqual({ id: 'rock' })
    })

    it('resolves a route with query string params', () => {
      const route = router.resolve('#/genres/rock?sort=name')

      expect(route).not.toBeNull()
      expect(router.$currentRoute.value.params).toEqual({ id: 'rock', sort: 'name' })
    })

    it('triggers not found for unmatched routes', () => {
      const route = router.resolve('#/this/does/not/exist')

      expect(route).toBeNull()
      expect(router.$currentRoute.value.screen).toBe('404')
    })

    it('follows redirect routes', () => {
      const goSpy = vi.spyOn(Router, 'go').mockImplementation(() => {})

      const uuid = '019cc197-f709-733b-a9f2-a2a7fb6cf1c2'
      router.resolve(`#/songs/${uuid}`)

      expect(goSpy).toHaveBeenCalledWith('queue')
    })
  })

  describe('activateRoute', () => {
    it('sets the current route and params', () => {
      const route = { path: '/test', screen: 'Home' as ScreenName }

      router.activateRoute(route, { foo: 'bar' })

      expect(router.$currentRoute.value.path).toBe('/test')
      expect(router.$currentRoute.value.screen).toBe('Home')
      expect(router.$currentRoute.value.params).toEqual({ foo: 'bar' })
    })

    it('defaults params to an empty object', () => {
      const route = { path: '/test', screen: 'Home' as ScreenName }

      router.activateRoute(route)

      expect(router.$currentRoute.value.params).toEqual({})
    })
  })

  describe('onRouteChanged', () => {
    it('calls registered handlers on route change', async () => {
      const handler = vi.fn()
      router.onRouteChanged(handler)

      router.activateRoute({ path: '/songs', screen: 'Songs' })

      await vi.dynamicImportSettled()

      expect(handler).toHaveBeenCalled()
    })
  })

  describe('url', () => {
    it('generates a URL for a named route', () => {
      expect(Router.url('home')).toBe('/#/home')
    })

    it('generates a URL with required params', () => {
      expect(Router.url('genres.show', { id: 'rock' })).toBe('/#/genres/rock')
    })

    it('generates a URL with optional params', () => {
      const id = '01JQABC1234567890ABCDEFGHIJ'
      expect(Router.url('albums.show', { id, tab: 'songs' })).toBe(`/#/albums/${id}/songs`)
    })

    it('generates a URL with optional params omitted', () => {
      const id = '01JQABC1234567890ABCDEFGHIJ'
      expect(Router.url('albums.show', { id })).toBe(`/#/albums/${id}`)
    })

    it('throws for unknown route names', () => {
      expect(() => Router.url('nonexistent' as any)).toThrowError('Route "nonexistent" not found')
    })

    it('throws for missing required params', () => {
      expect(() => Router.url('genres.show')).toThrowError('Missing required param "id"')
    })
  })

  describe('triggerNotFound', () => {
    it('activates the 404 route', () => {
      router.triggerNotFound()

      expect(router.$currentRoute.value.screen).toBe('404')
    })
  })
})
