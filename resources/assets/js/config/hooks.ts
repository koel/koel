export const Action = {
  APPLICATION_CREATED: 'application-created',
} as const

export const Filter = {
  ROUTES: 'routes',
  SCREENS: 'screens',
  MANAGE_SIDEBAR_ITEMS: 'manage-sidebar-items',
  SIDEBAR_FOOTER_ITEMS: 'sidebar-footer-items',
  PROFILE_MENU_ITEMS: 'profile-menu-items',
  POLICIES: 'policies',
} as const

export type ActionName = (typeof Action)[keyof typeof Action]
export type FilterName = (typeof Filter)[keyof typeof Filter]
