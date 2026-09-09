import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NovaTenancyPage from '../resources/js/pages/NovaTenancy.vue'

describe('NovaTenancy page', () => {
  let get
  let post

  beforeEach(() => {
    get = vi.fn().mockResolvedValue({
      data: {
        data: [
          {
            key: 2,
            title: 'Beta',
            logoUrl: null,
            description: 'A focused workspace',
            badge: 'Pro',
            accent: '#2563eb',
          },
        ],
        currentKey: 1,
        meta: { currentPage: 1, lastPage: 1, perPage: 12, total: 1 },
      },
    })
    post = vi.fn().mockResolvedValue({
      data: {
        current: { key: 2, title: 'Beta' },
        redirectTo: '/dashboards/main',
      },
    })
    globalThis.Nova = {
      config: key =>
        key === 'novaTenancy'
          ? {
              current: { key: 1, title: 'Alpha' },
              apiBase: '/nova-vendor/nova-tenancy',
              searchDebounce: 1,
              copy: {
                pageTitle: 'Choose workspace',
                pageKicker: 'Tenant context',
                pageDescription: 'Choose where to work.',
                currentTenant: 'Current',
                search: 'Search',
                searchPlaceholder: 'Search…',
                select: 'Open',
                selected: 'Selected',
                emptyTitle: 'Empty',
                emptyDescription: 'No results',
                error: 'Error',
                retry: 'Retry',
                previous: 'Previous',
                next: 'Next',
              },
            }
          : null,
      request: () => ({ get, post }),
      visit: vi.fn(),
      $emit: vi.fn(),
    }
  })

  it('loads an authorized page and switches tenant through Nova navigation', async () => {
    const wrapper = mount(NovaTenancyPage, {
      global: { stubs: { Head: true } },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Beta')
    expect(wrapper.text()).toContain('A focused workspace')

    await wrapper.get('.nova-tenancy-select').trigger('click')
    await flushPromises()

    expect(post).toHaveBeenCalledWith('/nova-vendor/nova-tenancy/current', {
      tenant: '2',
    })
    expect(Nova.$emit).toHaveBeenCalledWith(
      'nova-tenancy:changed',
      expect.objectContaining({ current: { key: 2, title: 'Beta' } })
    )
    expect(Nova.visit).toHaveBeenCalledWith('/dashboards/main')
  })
})
