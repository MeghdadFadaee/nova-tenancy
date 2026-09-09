export const sameTenant = (left, right) =>
  left !== null && right !== null && String(left) === String(right)

export const tenantInitial = title =>
  String(title || 'T').trim().charAt(0).toLocaleUpperCase()

export const tenantStyle = tenant => ({
  '--tenant-accent': tenant.accent || '#7c3aed',
})

export const currentLogoUrl = (apiBase, tenantKey, revision) =>
  `${apiBase}/current/logo?tenant=${encodeURIComponent(tenantKey ?? 'none')}&v=${revision}`
