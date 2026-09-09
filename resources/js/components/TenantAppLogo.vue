<template>
  <span
    ref="brand"
    class="nova-tenancy-brand"
    :title="title"
    @click.stop.prevent="visitBrandDestination"
  >
    <span v-if="showLogo" class="nova-tenancy-brand__logo" aria-hidden="true">
      <img
        v-if="!logoFailed"
        :key="logoUrl"
        :src="logoUrl"
        alt=""
        @error="logoFailed = true"
      />

      <span v-else class="nova-tenancy-brand__initial">
        {{ initial }}
      </span>
    </span>

    <span v-if="showTitle" class="nova-tenancy-brand__title">
      {{ title }}
    </span>
  </span>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { currentLogoUrl, tenantInitial } from '../tenantState'

defineOptions({ inheritAttrs: false })

const configuration = Nova.config('novaTenancy') || {}
const brand = ref(null)
const current = ref(configuration.current || null)
const revision = ref(Date.now())
const logoFailed = ref(false)
let parentLink = null

const showLogo = computed(() => configuration.branding?.show_logo !== false)
const showTitle = computed(() => configuration.branding?.show_title !== false)
const title = computed(
  () => current.value?.title || configuration.copy?.selectTenant || 'Select tenant'
)
const initial = computed(() => tenantInitial(title.value))
const logoUrl = computed(() => {
  const apiBase = configuration.apiBase || '/nova-vendor/nova-tenancy'
  const key = current.value?.key ?? 'none'

  return currentLogoUrl(apiBase, key, revision.value)
})

const updateCurrent = payload => {
  current.value = payload?.current ?? payload ?? null
  revision.value = Date.now()
  logoFailed.value = false
}

const refreshCurrent = async () => {
  try {
    const response = await Nova.request().get(
      `${configuration.apiBase || '/nova-vendor/nova-tenancy'}/current`
    )
    updateCurrent(response.data)
  } catch {
    // Keep the server-provided bootstrap state if the refresh cannot complete.
  }
}

const brandDestination = () => {
  const destination = configuration.branding?.link

  if (destination === 'home') {
    return '/'
  }

  if (typeof destination === 'string' && destination.startsWith('/')) {
    return destination
  }

  return configuration.selectorPath || '/nova-tenancy'
}

const visitBrandDestination = () => {
  Nova.visit(brandDestination())
}

const interceptParentLink = event => {
  event.preventDefault()
  event.stopImmediatePropagation()
  visitBrandDestination()
}

onMounted(() => {
  Nova.$on('nova-tenancy:changed', updateCurrent)
  parentLink = brand.value?.closest('a') || null
  parentLink?.addEventListener('click', interceptParentLink, true)
  refreshCurrent()
})

onBeforeUnmount(() => {
  Nova.$off('nova-tenancy:changed', updateCurrent)
  parentLink?.removeEventListener('click', interceptParentLink, true)
})
</script>
