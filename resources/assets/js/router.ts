import type { Ref } from 'vue'
import { ref, watch } from 'vue'
import type { RouteName } from '@/config/routes'
import { routes as builtInRoutes } from '@/config/routes'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'
import { forceReloadWindow } from '@/utils/helpers'
import { basePath, toClientPath, usesCleanUrls } from '@/utils/clientUrl'

type RouteParams = Record<string, string>
type ResolvedHook = (params: RouteParams) => Promise<boolean | void> | boolean | void
type RedirectHook = (params: RouteParams) => Route | string
type RouteGuard = () => boolean

export interface Route {
  name?: string
  path: string
  screen: ScreenName
  constraints?: Record<string, string>
  params?: RouteParams
  meta?: {
    guard?: RouteGuard
    layout?: string
    onResolved?: ResolvedHook
    public?: boolean
    redirect?: RedirectHook
  } & Record<string, any>
}

let cachedRoutes: Route[] | null = null

const routes = () => (cachedRoutes ??= applyFilters<Route[]>(Filter.ROUTES, [...builtInRoutes]))

const currentClientPath = () =>
  usesCleanUrls() ? toClientPath(`${location.pathname}${location.search}`) : location.hash

interface CompiledRoute {
  regex: RegExp
  paramNames: string[]
  originalRoute: Route
}

interface MatchedRoute {
  originalRoute: Route
  params: RouteParams
}

type RouteChangedHandler = (newRoute: Route, oldRoute: Route | undefined) => any

export default class Router {
  public $currentRoute: Ref<Route>
  private readonly compiledRoutes: CompiledRoute[]

  private readonly homeRoute: Route
  private readonly notFoundRoute: Route
  private routeChangedHandlers: RouteChangedHandler[] = []

  compileRoute(route: Route): CompiledRoute {
    const paramNames: string[] = []

    const regexPath = route.path.replace(/\/:(\w+)\??/g, (match, key) => {
      const constraint = route.constraints?.[key] ?? '[^/]+'
      paramNames.push(key)
      return match.endsWith('?') ? `(?:/(?<${key}>${constraint}))?` : `/(?<${key}>${constraint})`
    })

    return {
      paramNames,
      originalRoute: route,
      regex: new RegExp(`^${regexPath}/?$`),
    }
  }

  constructor() {
    const allRoutes = routes()

    this.homeRoute = allRoutes.find(({ screen }) => screen === 'Home')!
    this.notFoundRoute = allRoutes.find(({ screen }) => screen === '404')!
    this.$currentRoute = ref(this.homeRoute)

    this.compiledRoutes = allRoutes.map(this.compileRoute)

    watch(
      this.$currentRoute,
      (newValue, oldValue) => this.routeChangedHandlers.forEach(async handler => await handler(newValue, oldValue)),
      {
        deep: true,
        immediate: true,
      },
    )

    addEventListener('popstate', () => this.resolve(), true)

    if (usesCleanUrls()) {
      this.rewriteHashUrl()
      addEventListener('click', this.interceptLinkClick)
    }
  }

  private rewriteHashUrl() {
    if (location.hash.startsWith('#/')) {
      history.replaceState(null, '', `${basePath()}${location.hash.substring(2)}`)
    }
  }

  private interceptLinkClick = (event: MouseEvent) => {
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    ) {
      return
    }

    const link = (event.target as Element | null)?.closest('a')

    if (!link || (link.target && link.target !== '_self') || link.hasAttribute('download')) {
      return
    }

    const url = new URL(link.href, location.href)

    if (url.origin !== location.origin || !url.pathname.startsWith(basePath())) {
      return
    }

    const path = toClientPath(`${url.pathname}${url.search}`)

    if (!this.tryMatchRoute(path)) {
      return
    }

    event.preventDefault()
    Router.go(path)
  }

  public static go(path: string | number, reload = false) {
    if (typeof path === 'number') {
      history.go(path)
      return
    }

    Router.navigate(path, 'push')

    reload && forceReloadWindow()
  }

  public static replace(path: string) {
    Router.navigate(path, 'replace')
  }

  private static navigate(path: string, mode: 'push' | 'replace') {
    if (usesCleanUrls()) {
      const url = `${basePath()}${toClientPath(path).substring(1)}`

      if (mode === 'push') {
        history.pushState(null, '', url)
      } else {
        history.replaceState(null, '', url)
      }

      dispatchEvent(new PopStateEvent('popstate'))

      return
    }

    if (!path.startsWith('/')) {
      path = `/${path}`
    }

    if (!path.startsWith('/#')) {
      path = `/#${path}`
    }

    const url = `${location.origin}${location.pathname}${path.substring(1)}`

    if (mode === 'push') {
      location.assign(url)
    } else {
      location.replace(url)
    }
  }

  public resolve(path?: string) {
    path = path ?? currentClientPath()

    const [pathWithoutQuery, query] = path.split('?')

    if (['', '/', '#', '#/', '#!/'].includes(pathWithoutQuery)) {
      Router.replace(query ? `${this.homeRoute.path}?${query}` : this.homeRoute.path)
      return null
    }

    const matchedRoute = this.tryMatchRoute(path)
    const [route, params] = matchedRoute ? [matchedRoute.originalRoute, matchedRoute.params] : [null, null]

    if (!route) {
      this.triggerNotFound()
      return null
    }

    route.meta?.onResolve?.(params)

    if (route.meta?.redirect) {
      const to = route.meta.redirect(params)
      typeof to === 'string' ? Router.go(to) : this.activateRoute(to, params)
    } else {
      this.activateRoute(route, params)
    }

    return route
  }

  public triggerNotFound = () => this.activateRoute(this.notFoundRoute)
  public onRouteChanged = (handler: RouteChangedHandler) => this.routeChangedHandlers.push(handler)

  public activateRoute(route: Route, params: RouteParams = {}) {
    this.$currentRoute.value = route
    this.$currentRoute.value.params = params
  }

  private tryMatchRoute(screenPath: string): MatchedRoute | null {
    const [path, queryString] = screenPath.replace(/^#?/, '').split('?')

    for (const route of this.compiledRoutes) {
      const match = path.match(route.regex)

      if (match) {
        const params = { ...match.groups }

        if (queryString) {
          const searchParams = new URLSearchParams(queryString)
          for (const [key, value] of searchParams) {
            params[key] = value
          }
        }

        return {
          params,
          originalRoute: route.originalRoute,
        }
      }
    }

    return null
  }

  public static url(name: RouteName, params: object = {}) {
    const route = routes().find(route => route.name === name)

    if (!route) {
      throw new Error(`Route "${name}" not found`)
    }

    let path = route.path as string

    path = path.replace(/:(\w+)\??/g, (_, key: string, offset: number, fullPath: string) => {
      const isOptional = fullPath[offset + key.length + 1] === '?'
      const value = params[key]

      if (value !== undefined && value !== null) {
        return value
      }

      if (isOptional) {
        return ''
      }

      throw new Error(`Missing required param "${key}" for route "${name}"`)
    })

    // Remove any accidental trailing slashes caused by optional segments
    path = path.replace(/\/+$/, '') || '/'

    if (!path.startsWith('/')) {
      path = `/${path}`
    }

    if (usesCleanUrls()) {
      return `${basePath()}${path.substring(1)}`
    }

    if (!path.startsWith('/#')) {
      path = `/#${path}`
    }

    return path
  }
}
