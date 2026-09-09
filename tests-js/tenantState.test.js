import { describe, expect, it } from 'vitest'
import {
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
    expect(tenantStyle({ accent: null })).toEqual({ '--tenant-accent': '#7c3aed' })
  })

  it('creates a cache-busted fixed logo endpoint', () => {
    expect(currentLogoUrl('/nova-vendor/nova-tenancy', 'a/b', 42)).toBe(
      '/nova-vendor/nova-tenancy/current/logo?tenant=a%2Fb&v=42'
    )
  })
})
