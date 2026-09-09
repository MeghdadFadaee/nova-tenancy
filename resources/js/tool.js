import NovaTenancy from './pages/NovaTenancy.vue'
import TenantAppLogo from './components/TenantAppLogo.vue'

Nova.inertia('NovaTenancy', NovaTenancy)

Nova.booting(app => {
  if (Nova.config('novaTenancy')?.branding?.enabled !== false) {
    app.component('AppLogo', TenantAppLogo)
  }
})

Nova.request().interceptors.response.use(
  response => response,
  error => {
    if (
      error.response?.status === 409 &&
      error.response?.data?.code === 'tenant_selection_required'
    ) {
      const selectorPath = Nova.config('novaTenancy')?.selectorPath || '/nova-tenancy'
      Nova.visit(selectorPath)
    }

    return Promise.reject(error)
  }
)
