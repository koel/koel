export const Action = {
  APPLICATION_CREATED: 'application-created',
} as const

export const Filter = {
  ROUTES: 'routes',
  SCREENS: 'screens',
  MANAGE_SIDEBAR_ITEMS: 'manage-sidebar-items',
  SLOT: 'slot',
  PROFILE_MENU_ITEMS: 'profile-menu-items',
  PROFILE_INTEGRATIONS: 'profile-integrations',
  ACCOUNT_SETTINGS_TABS: 'account-settings-tabs',
  SERVER_SETTINGS_TABS: 'server-settings-tabs',
  POLICIES: 'policies',
} as const

export type ActionName = (typeof Action)[keyof typeof Action]
export type FilterName = (typeof Filter)[keyof typeof Filter]
