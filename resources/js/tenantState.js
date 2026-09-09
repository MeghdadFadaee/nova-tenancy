export const sameTenant = (left, right) =>
  left !== null && right !== null && String(left) === String(right)

export const tenantInitial = title =>
  String(title || 'T').trim().charAt(0).toLocaleUpperCase()

export const accentContrast = accent => {
  if (!/^#[0-9a-f]{6}$/i.test(accent || '')) return '#ffffff'

  const channels = accent
    .slice(1)
    .match(/.{2}/g)
    .map(channel => Number.parseInt(channel, 16) / 255)
    .map(channel =>
      channel <= 0.04045
        ? channel / 12.92
        : ((channel + 0.055) / 1.055) ** 2.4
    )
  const luminance = channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722
  const lightContrast = 1.05 / (luminance + 0.05)
  const darkContrast = (luminance + 0.05) / 0.068

  return darkContrast > lightContrast ? '#0f172a' : '#ffffff'
}

export const tenantStyle = tenant =>
  tenant.accent
    ? {
        '--tenant-accent': tenant.accent,
        '--tenant-accent-contrast': accentContrast(tenant.accent),
      }
    : {}

export const currentLogoUrl = (apiBase, tenantKey, revision) =>
  `${apiBase}/current/logo?tenant=${encodeURIComponent(tenantKey ?? 'none')}&v=${revision}`
