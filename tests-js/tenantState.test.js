import { describe, expect, it } from 'vitest'
import {
  accentContrast,
  currentLogoUrl,
  sameTenant,
  tenantInitial,
  tenantStyle,
} from '../resources/js/tenantState'

describe('tenant state helpers', () => {
  it('compares numeric and string tenant keys consistently', () => {
    expect(sameTenant(12, '12')).toBe(true)
    expect(sameTenant(null, null)).toBe(false)
  })

  it('builds safe presentation fallbacks', () => {
    expect(tenantInitial(' alpha')).toBe('A')
    expect(tenantStyle({ accent: null })).toEqual({})
    expect(tenantStyle({ accent: '#ffffff' })).toEqual({
      '--tenant-accent': '#ffffff',
      '--tenant-accent-contrast': '#0f172a',
    })
    expect(accentContrast('#2563eb')).toBe('#ffffff')
  })

  it('creates a cache-busted fixed logo endpoint', () => {
    expect(currentLogoUrl('/nova-vendor/nova-tenancy', 'a/b', 42)).toBe(
      '/nova-vendor/nova-tenancy/current/logo?tenant=a%2Fb&v=42'
    )
  })
})
