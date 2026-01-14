import { defineStore } from 'pinia'
import axios from '../api/axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('token') || null,
        loading: false,
        error: null
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.role === 'admin',
        isCashier: (state) => state.user?.role === 'caissier'
    },
    actions: {
        async login(credentials: any) {
            this.loading = true
            this.error = null
            try {
                const response = await axios.post('/login', credentials)
                this.token = response.data.access_token
                this.user = response.data.user
                localStorage.setItem('token', this.token as string)
                localStorage.setItem('user', JSON.stringify(this.user))
                return true
            } catch (err: any) {
                this.error = err.response?.data?.message || 'Erreur de connexion'
                return false
            } finally {
                this.loading = false
            }
        },
        async register(credentials: any) {
            this.loading = true
            this.error = null
            try {
                const response = await axios.post('/register', credentials)
                this.token = response.data.access_token
                this.user = response.data.user
                localStorage.setItem('token', this.token as string)
                localStorage.setItem('user', JSON.stringify(this.user))
                return true
            } catch (err: any) {
                this.error = err.response?.data?.message || 'Erreur lors de l\'inscription'
                return false
            } finally {
                this.loading = false
            }
        },
        async logout() {
            try {
                await axios.post('/logout')
            } catch (err) {
                console.error('Logout error', err)
            } finally {
                this.token = null
                this.user = null
                localStorage.removeItem('token')
                localStorage.removeItem('user')
            }
        }
    }
})
