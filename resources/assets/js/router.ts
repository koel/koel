import { ref, Ref, watch } from 'vue'

type RouteParams = Record<string, string>
type BeforeEnterHook = (params: RouteParams) => boolean | void
type EnterHook = (params: RouteParams) => any
type RedirectHook = (params: RouteParams) => Route | string

export type Route = {
  path: string
  screen: ScreenName
  params?: RouteParams
  redirect?: RedirectHook
  onBeforeEnter?: BeforeEnterHook
  onEnter?: EnterHook
}

type RouteChangedHandler = (newRoute: Route, oldRoute: Route | undefined) => any

// @TODO: Remove support for hashbang (#!) and only support hash (#)
export default class Router {
  public $currentRoute: Ref<Route>

  private readonly routes: Route[]
  private readonly homeRoute: Route
  private readonly notFoundRoute: Route
  private routeChangedHandlers: RouteChangedHandler[] = []
  private cache: Map<string, { route: Route, params: RouteParams }> = new Map()

  constructor (routes: Route[]) {
    this.routes = routes
    this.homeRoute = routes.find(route => route.screen === 'Home')!
    this.notFoundRoute = routes.find(route => route.screen === '404')!
    this.$currentRoute = ref(this.homeRoute)

    watch(
      this.$currentRoute,
      (newValue, oldValue) => this.routeChangedHandlers.forEach(async handler => await handler(newValue, oldValue)),
      {
        deep: true,
        immediate: true
      }
    )

    addEventListener('popstate', () => this.resolve(), true)
  }

  public async resolve () {
    if (!location.hash || location.hash === '#/' || location.hash === '#!/') {
      return this.activateRoute(this.homeRoute)
    }

    const matched = this.tryMatchRoute(location.hash)
    const [route, params] = matched ? [matched.route, matched.params] : [null, null]

    if (!route) {
      return this.triggerNotFound()
    }

    if (route.onBeforeEnter && route.onBeforeEnter(params) === false) {
      return this.triggerNotFound()
    }

    if (route.redirect) {
      const to = route.redirect(params)
      return typeof to === 'string' ? this.go(to) : this.activateRoute(to, params)
    }

    return this.activateRoute(route, params)
  }

  private tryMatchRoute (hash: string) {
    if (!this.cache.has(hash)) {
      for (let i = 0; i < this.routes.length; i++) {
        const route = this.routes[i]
        const matches = hash.match(new RegExp(`^#!?${route.path}/?(?:\\?(.*))?$`))

        if (matches) {
          const searchParams = new URLSearchParams(new URL(location.href.replace(/#!?\?/, '')).search)

          this.cache.set(hash, {
            route,
            params: Object.assign(Object.fromEntries(searchParams.entries()), matches.groups || {})
          })

          break
        }
      }
    }

    return this.cache.get(hash)
  }

  public async triggerNotFound () {
    await this.activateRoute(this.notFoundRoute)
  }

  public onRouteChanged (handler: RouteChangedHandler) {
    this.routeChangedHandlers.push(handler)
  }

  public async activateRoute (route: Route, params: RouteParams = {}) {
    this.$currentRoute.value = route
    this.$currentRoute.value.params = params

    if (this.$currentRoute.value.onEnter) {
      await this.$currentRoute.value.onEnter(params)
    }
  }

  public go (path: string) {
    if (!path.startsWith('/')) {
      path = `/${path}`
    }

    if (!path.startsWith('/#')) {
      path = `/#${path}`
    }

    path = path.substring(1, path.length)
    location.assign(`${location.origin}${location.pathname}${path}`)
  }
}
