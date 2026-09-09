<template>
  <div class="nova-tenancy-page">
    <Head :title="copy.pageTitle" />

    <section class="nova-tenancy-hero">
      <div class="nova-tenancy-hero__glow" aria-hidden="true"></div>

      <div class="nova-tenancy-hero__content">
        <p class="nova-tenancy-kicker">{{ copy.pageKicker }}</p>
        <h1 class="nova-tenancy-title">{{ copy.pageTitle }}</h1>
        <p class="nova-tenancy-description">{{ copy.pageDescription }}</p>
      </div>

      <div v-if="activeTenant" class="nova-tenancy-current">
        <div class="nova-tenancy-current__logo">
          <img
            v-if="activeTenant.logoUrl && !failedImages.has(String(activeTenant.key))"
            :src="activeTenant.logoUrl"
            alt=""
            @error="markImageFailed(activeTenant.key)"
          />
          <span v-else>{{ initial(activeTenant.title) }}</span>
        </div>
        <div class="nova-tenancy-current__copy">
          <span>{{ copy.currentTenant }}</span>
          <strong>{{ activeTenant.title }}</strong>
        </div>
        <span class="nova-tenancy-pulse" aria-hidden="true"></span>
      </div>
    </section>

    <section class="nova-tenancy-toolbar" :aria-label="copy.search">
      <label class="nova-tenancy-search">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
        </svg>
        <span class="nova-tenancy-sr-only">{{ copy.search }}</span>
        <input
          v-model="search"
          type="search"
          :placeholder="copy.searchPlaceholder"
          autocomplete="off"
        />
      </label>

      <span v-if="!loading" class="nova-tenancy-count">
        {{ meta.total.toLocaleString() }}
      </span>
    </section>

    <div v-if="loading" class="nova-tenancy-grid" aria-busy="true">
      <div v-for="index in 6" :key="index" class="nova-tenancy-card nova-tenancy-card--skeleton">
        <span></span><span></span><span></span>
      </div>
    </div>

    <div v-else-if="error" class="nova-tenancy-state" role="alert">
      <div class="nova-tenancy-state__icon">!</div>
      <h2>{{ copy.error }}</h2>
      <button type="button" class="nova-tenancy-button" @click="loadTenants">
        {{ copy.retry }}
      </button>
    </div>

    <div v-else-if="tenants.length === 0" class="nova-tenancy-state">
      <div class="nova-tenancy-state__icon nova-tenancy-state__icon--empty">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M9 14h.01M9 18h.01M15 10h.01M15 14h.01M15 18h.01" />
        </svg>
      </div>
      <h2>{{ copy.emptyTitle }}</h2>
      <p>{{ copy.emptyDescription }}</p>
    </div>

    <div v-else class="nova-tenancy-grid">
      <article
        v-for="tenant in tenants"
        :key="tenant.key"
        class="nova-tenancy-card"
        :class="{ 'nova-tenancy-card--selected': isSelected(tenant) }"
        :style="tenantStyle(tenant)"
        :aria-current="isSelected(tenant) ? 'true' : undefined"
      >
        <div class="nova-tenancy-card__accent" aria-hidden="true"></div>
        <div class="nova-tenancy-card__header">
          <div class="nova-tenancy-card__logo">
            <img
              v-if="tenant.logoUrl && !failedImages.has(String(tenant.key))"
              :src="tenant.logoUrl"
              alt=""
              loading="lazy"
              @error="markImageFailed(tenant.key)"
            />
            <span v-else>{{ initial(tenant.title) }}</span>
          </div>

          <span v-if="tenant.badge" class="nova-tenancy-badge">{{ tenant.badge }}</span>
          <span v-else-if="isSelected(tenant)" class="nova-tenancy-check" :title="copy.selected">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 10 3 3 7-7" /></svg>
          </span>
        </div>

        <div class="nova-tenancy-card__body">
          <h2>{{ tenant.title }}</h2>
          <p v-if="tenant.description">{{ tenant.description }}</p>
          <p v-else class="nova-tenancy-card__key">#{{ tenant.key }}</p>
        </div>

        <button
          type="button"
          class="nova-tenancy-select"
          :class="{ 'nova-tenancy-select--selected': isSelected(tenant) }"
          :disabled="workingKey !== null || isSelected(tenant)"
          :aria-busy="workingKey === String(tenant.key)"
          @click="selectTenant(tenant)"
        >
          <span v-if="workingKey === String(tenant.key)" class="nova-tenancy-spinner" aria-hidden="true"></span>
          <span>{{ isSelected(tenant) ? copy.selected : copy.select }}</span>
          <svg v-if="!isSelected(tenant)" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M4 10h12m-4-4 4 4-4 4" />
          </svg>
        </button>
      </article>
    </div>

    <nav v-if="meta.lastPage > 1" class="nova-tenancy-pagination" aria-label="Pagination">
      <button type="button" :disabled="meta.currentPage <= 1 || loading" @click="goToPage(meta.currentPage - 1)">
        {{ copy.previous }}
      </button>
      <span>{{ meta.currentPage }} / {{ meta.lastPage }}</span>
      <button type="button" :disabled="meta.currentPage >= meta.lastPage || loading" @click="goToPage(meta.currentPage + 1)">
        {{ copy.next }}
      </button>
    </nav>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { sameTenant, tenantInitial, tenantStyle as resolveTenantStyle } from '../tenantState'

const configuration = Nova.config('novaTenancy') || {}
const copy = configuration.copy || {}
const tenants = ref([])
const currentKey = ref(configuration.current?.key ?? null)
const search = ref('')
const loading = ref(true)
const error = ref(false)
const workingKey = ref(null)
const failedImages = reactive(new Set())
const meta = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: Number(configuration.perPage || 12),
  total: 0,
})
let debounceTimer
let requestSequence = 0

const activeTenant = computed(() => {
  const visible = tenants.value.find(tenant => isSelected(tenant))
  return (
    visible ||
    (sameTenant(configuration.current?.key ?? null, currentKey.value)
      ? configuration.current
      : null)
  )
})

const loadTenants = async () => {
  const sequence = ++requestSequence
  loading.value = true
  error.value = false

  try {
    const response = await Nova.request().get(
      `${configuration.apiBase || '/nova-vendor/nova-tenancy'}/tenants`,
      { params: { search: search.value || undefined, page: meta.currentPage, perPage: meta.perPage } }
    )

    if (sequence !== requestSequence) return

    tenants.value = response.data.data
    currentKey.value = response.data.currentKey
    Object.assign(meta, response.data.meta)
  } catch {
    if (sequence === requestSequence) error.value = true
  } finally {
    if (sequence === requestSequence) loading.value = false
  }
}

const selectTenant = async tenant => {
  if (workingKey.value !== null || isSelected(tenant)) return

  workingKey.value = String(tenant.key)

  try {
    const response = await Nova.request().post(
      `${configuration.apiBase || '/nova-vendor/nova-tenancy'}/current`,
      { tenant: String(tenant.key) }
    )
    currentKey.value = response.data.current.key
    Nova.$emit('nova-tenancy:changed', response.data)
    Nova.visit(response.data.redirectTo || '/')
  } catch {
    Nova.$emit('error', copy.error || 'Unable to select tenant.')
  } finally {
    workingKey.value = null
  }
}

const goToPage = page => {
  meta.currentPage = page
  loadTenants()
}

const isSelected = tenant => sameTenant(tenant.key, currentKey.value)
const initial = tenantInitial
const tenantStyle = resolveTenantStyle
const markImageFailed = key => failedImages.add(String(key))

watch(search, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    meta.currentPage = 1
    loadTenants()
  }, Number(configuration.searchDebounce || 300))
})

onMounted(loadTenants)
onBeforeUnmount(() => clearTimeout(debounceTimer))
</script>
