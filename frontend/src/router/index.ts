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
                    meta: { roles: ['admin', 'caissier'] }
                },
                {
                    path: 'pos',
                    name: 'pos',
                    component: () => import('../views/POSView.vue'),
                    meta: { roles: ['admin', 'caissier'] }
                },
                {
                    path: 'medications',
                    name: 'medications',
                    component: () => import('../views/MedicationsView.vue'),
                    meta: { roles: ['admin', 'caissier'] }
                },
                {
                    path: 'prescriptions',
                    name: 'prescriptions',
                    component: () => import('../views/PrescriptionsView.vue'),
                    meta: { roles: ['admin', 'caissier'] }
                },
                {
                    path: 'patients',
                    name: 'patients',
                    component: () => import('../views/PatientsView.vue'),
                    meta: { roles: ['admin', 'caissier'] }
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
                return next('/') // Redirect to dashboard if unauthorized
            }
        }
        next()
    }
})

export default router
