import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('../views/LoginView.vue'),
            meta: { guest: true }
        },

        {
            path: '/',
            component: () => import('../layouts/DashboardLayout.vue'),
            meta: { auth: true },
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('../views/DashboardView.vue'),
                    meta: { roles: ['admin', 'vendeur', 'caissier'] }
                },
                {
                    path: 'orders',
                    name: 'orders',
                    component: () => import('../views/OrderCreationView.vue'),
                    meta: { roles: ['admin', 'vendeur'] }
                },
                {
                    path: 'cashier',
                    name: 'cashier',
                    component: () => import('../views/CashierView.vue'),
                    meta: { roles: ['admin', 'caissier'] }
                },
                {
                    path: 'cash-closing',
                    name: 'cash-closing',
                    component: () => import('../views/CashierClosingView.vue'),
                    meta: { roles: ['admin', 'caissier'] }
                },
                {
                    path: 'sales-history',
                    name: 'sales-history',
                    component: () => import('../views/SalesHistoryView.vue'),
                    meta: { roles: ['admin', 'caissier'] }
                },
                {
                    path: 'medications',
                    name: 'medications',
                    component: () => import('../views/MedicationsView.vue'),
                    meta: { roles: ['admin'] }
                },
                {
                    path: 'stock-reception',
                    name: 'stock-reception',
                    component: () => import('../views/ReceptionView.vue'),
                    meta: { roles: ['admin'] }
                },
                {
                    path: 'prescriptions',
                    name: 'prescriptions',
                    component: () => import('../views/PrescriptionsView.vue'),
                    meta: { roles: ['admin', 'vendeur'] }
                },
                {
                    path: 'patients',
                    name: 'patients',
                    component: () => import('../views/PatientsView.vue'),
                    meta: { roles: ['admin', 'vendeur'] }
                },
                {
                    path: 'suppliers',
                    name: 'suppliers',
                    component: () => import('../views/SuppliersView.vue'),
                    meta: { roles: ['admin'] }
                },
                {
                    path: 'reports',
                    name: 'reports',
                    component: () => import('../views/ReportsView.vue'),
                    meta: { roles: ['admin'] }
                },
                {
                    path: 'settings',
                    name: 'settings',
                    component: () => import('../views/SettingsView.vue'),
                    meta: { roles: ['admin'] }
                },
                {
                    path: 'users',
                    name: 'users',
                    component: () => import('../views/UserManagementView.vue'),
                    meta: { roles: ['admin'] }
                },
                {
                    path: 'audit-logs',
                    name: 'audit-logs',
                    component: () => import('../views/AuditLogView.vue'),
                    meta: { roles: ['admin'] }
                }
            ]
        }
    ]
})

router.beforeEach((to, _from, next) => {
    const auth = useAuthStore()

    if (to.meta.auth && !auth.isAuthenticated) {
        next('/login')
    } else if (to.meta.guest && auth.isAuthenticated) {
        next('/')
    } else {
        // Role check
        if (to.meta.roles && Array.isArray(to.meta.roles)) {
            const userRole = auth.user?.role
            if (!to.meta.roles.includes(userRole)) {
                // Prevent infinite loop: if they are already on the dashboard or if they have no role
                if (to.path === '/') {
                    if (userRole === 'vendeur') return next('/orders')
                    if (userRole === 'caissier') return next('/cashier')
                    return next('/login')
                }
                return next('/')
            }
        }
        next()
    }
})

export default router
