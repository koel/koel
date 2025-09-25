import type { Ref } from 'vue'
import { ref, watch } from 'vue'
import type { RouteName } from '@/config/routes'
import { routes } from '@/config/routes'
import { forceReloadWindow } from '@/utils/helpers'

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

  compileRoute (route: Route): CompiledRoute {
    const paramNames: string[] = []

    const regexPath = route.path.replace(/\/:(\w+)\??/g, (match, key) => {
      const constraint = route.constraints?.[key] ?? '[^/]+'
      paramNames.push(key)
      return match.endsWith('?')
        ? `(?:/(?<${key}>${constraint}))?`
        : `/(?<${key}>${constraint})`
    })

    return {
      paramNames,
      originalRoute: route,
      regex: new RegExp(`^${regexPath}/?$`),
    }
  }

  constructor () {
    this.homeRoute = routes.find(({ screen }) => screen === 'Home')!
    this.notFoundRoute = routes.find(({ screen }) => screen === '404')!
    this.$currentRoute = ref(this.homeRoute)

    this.compiledRoutes = routes.map(this.compileRoute)

    watch(
      this.$currentRoute,
      (newValue, oldValue) => this.routeChangedHandlers.forEach(async handler => await handler(newValue, oldValue)),
      {
        deep: true,
        immediate: true,
      },
    )

    addEventListener('popstate', () => this.resolve(), true)
  }

  public static go (path: string | number, reload = false) {
    if (typeof path === 'number') {
      history.go(path)
      return
    }

    if (!path.startsWith('/')) {
      path = `/${path}`
    }

    if (!path.startsWith('/#')) {
      path = `/#${path}`
    }

    path = path.substring(1, path.length)
    location.assign(`${location.origin}${location.pathname}${path}`)

    reload && forceReloadWindow()
  }

  public resolve (hash?: string) {
    hash = hash ?? location.hash

    if (['', '#/', '#!/'].includes(hash)) {
      Router.go(this.homeRoute.path)
      return null
    }

    const matchedRoute = this.tryMatchRoute(hash)
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

  public activateRoute (route: Route, params: RouteParams = {}) {
    this.$currentRoute.value = route
    this.$currentRoute.value.params = params
  }

  private tryMatchRoute (hash: string): MatchedRoute | null {
    const [path, queryString] = hash.replace(/^#?/, '').split('?')

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

  public static url (name: RouteName, params: object = {}) {
    const route = routes.find(route => route.name === name)

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

    if (!path.startsWith('/#')) {
      path = `/#${path}`
    }

    return path
  }
}
