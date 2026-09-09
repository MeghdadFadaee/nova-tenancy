import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TenantAppLogo from '../resources/js/components/TenantAppLogo.vue'

describe('TenantAppLogo', () => {
  let listeners

  beforeEach(() => {
    listeners = {}
    globalThis.Nova = {
      config: key =>
        key === 'novaTenancy'
          ? {
              current: { key: 1, title: 'Alpha' },
              apiBase: '/nova-vendor/nova-tenancy',
              selectorPath: '/nova-tenancy',
              branding: { show_logo: true, show_title: true, link: 'selector' },
              copy: { selectTenant: 'Select tenant' },
            }
          : null,
      request: () => ({
        get: vi.fn().mockResolvedValue({
          data: { current: { key: 1, title: 'Alpha' } },
        }),
      }),
      visit: vi.fn(),
      $on: vi.fn((event, callback) => {
        listeners[event] = callback
      }),
      $off: vi.fn(),
    }
  })

  it('renders and reactively updates the selected tenant brand', async () => {
    const wrapper = mount(TenantAppLogo)
    await flushPromises()

    expect(wrapper.text()).toContain('Alpha')
    expect(wrapper.get('img').attributes('src')).toContain('/current/logo?tenant=1')

    listeners['nova-tenancy:changed']({ current: { key: 2, title: 'Beta' } })
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Beta')
    expect(wrapper.get('img').attributes('src')).toContain('/current/logo?tenant=2')
  })

  it('uses Nova SPA navigation when the brand is clicked', async () => {
    const wrapper = mount(TenantAppLogo)
    await wrapper.get('.nova-tenancy-brand').trigger('click')

    expect(Nova.visit).toHaveBeenCalledWith('/nova-tenancy')
  })

  it('does not inherit Nova logo sizing and falls back when the image fails', async () => {
    const wrapper = mount(TenantAppLogo, {
      attrs: { class: 'h-6' },
    })
    await flushPromises()

    expect(wrapper.get('.nova-tenancy-brand').classes()).not.toContain('h-6')
    expect(wrapper.get('.nova-tenancy-brand__logo').classes()).not.toContain(
      'nova-tenancy-brand__logo--fallback'
    )

    await wrapper.get('.nova-tenancy-brand__logo img').trigger('error')

    expect(wrapper.find('.nova-tenancy-brand__logo img').exists()).toBe(false)
    expect(wrapper.get('.nova-tenancy-brand__logo').classes()).toContain(
      'nova-tenancy-brand__logo--fallback'
    )
    expect(wrapper.get('.nova-tenancy-brand__initial').text()).toBe('A')
  })
})
