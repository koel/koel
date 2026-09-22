export const Action = {
  APPLICATION_BOOTED: 'application-booted',
} as const

export const Filter = {
  ROUTES: 'routes',
  SCREENS: 'screens',
  MANAGE_SIDEBAR_ITEMS: 'manage-sidebar-items',
  POLICIES: 'policies',
} as const

export type ActionName = (typeof Action)[keyof typeof Action]
export type FilterName = (typeof Filter)[keyof typeof Filter]
