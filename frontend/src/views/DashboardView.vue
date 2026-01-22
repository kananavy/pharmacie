<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

// Components
import AdminDashboard from '../components/dashboard/AdminDashboard.vue'
import VendeurDashboard from '../components/dashboard/VendeurDashboard.vue'
import CaissierDashboard from '../components/dashboard/CaissierDashboard.vue'

const auth = useAuthStore()

const currentDashboard = computed(() => {
  switch (auth.user?.role) {
    case 'admin':
    case 'superadmin':
      return AdminDashboard
    case 'vendeur':
      return VendeurDashboard
    case 'caissier':
      return CaissierDashboard
    default:
      return null
  }
})
</script>

<template>
  <div class="h-full overflow-y-auto custom-scrollbar p-1">
    <component 
      v-if="currentDashboard" 
      :is="currentDashboard" 
      :user="auth.user"
    />
    <div v-else class="flex items-center justify-center h-full text-slate-500">
      Rôle non reconnu ou accès non autorisé.
    </div>
  </div>
</template>

