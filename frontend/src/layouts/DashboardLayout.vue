<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { 
  LayoutDashboard, 
  ShoppingCart, 
  Pill, 
  Users, 
  BarChart3, 
  LogOut,
  Bell,
  Search,
  Menu,
  X,
  FileText,
  UserCircle,
  Settings2, 
  ChevronRight,
  CreditCard
} from 'lucide-vue-next'
import { ref, computed } from 'vue'

const router = useRouter()
const auth = useAuthStore()
const isSidebarOpen = ref(true)
const allNavigation = [
  { name: 'Tableau de bord', icon: LayoutDashboard, path: '/', roles: ['admin', 'vendeur', 'caissier'] },
  { name: 'Vente (Guichet)', icon: ShoppingCart, path: '/orders', roles: ['admin', 'vendeur'] },
  { name: 'Caisse', icon: CreditCard, path: '/cashier', roles: ['admin', 'caissier'] },
  { name: 'Médicaments', icon: Pill, path: '/medications', roles: ['admin'] },
  { name: 'Ordonnances', icon: FileText, path: '/prescriptions', roles: ['admin', 'vendeur'] },
  { name: 'Patients', icon: UserCircle, path: '/patients', roles: ['admin', 'vendeur'] },
  { name: 'Fournisseurs', icon: Users, path: '/suppliers', roles: ['admin'] },
  { name: 'Rapports', icon: BarChart3, path: '/reports', roles: ['admin'] },
  { name: 'Paramètres', icon: Settings2, path: '/settings', roles: ['admin'] }
]

const navigation = computed(() => {
  return allNavigation.filter(item => item.roles.includes(auth.user?.role))
})

const handleLogout = async () => {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="flex h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-slate-100 overflow-hidden font-sans">
    <!-- Modern Sidebar with Glassmorphism -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-72 transition-all duration-500 ease-out lg:relative lg:translate-x-0',
        isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <div class="flex flex-col h-full relative overflow-hidden bg-white/80 backdrop-blur-xl border-r border-slate-200/60 shadow-2xl shadow-emerald-900/5">
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-emerald-600/5 pointer-events-none"></div>
        
        <!-- Animated Background Orbs -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-emerald-400/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-32 -left-16 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl"></div>
        
        <!-- Sidebar Header -->
        <div class="h-20 flex items-center gap-3 px-6 border-b border-slate-200/60 relative z-10">
          <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
            <Pill class="text-white w-6 h-6" />
          </div>
          <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Pharmacie Pro</h1>
            <p class="text-[10px] font-medium text-emerald-600/70 uppercase tracking-wider">Système Officinal</p>
          </div>
        </div>

        <!-- Sidebar Nav -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 relative z-10 custom-scrollbar">
          <router-link 
            v-for="item in navigation" 
            :key="item.path"
            :to="item.path"
            v-slot="{ isActive, isExactActive }"
          >
            <div 
              :class="[
                'flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-300 group relative',
                (item.path === '/' ? isExactActive : isActive)
                  ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/30' 
                  : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
              ]"
            >
              <component :is="item.icon" :class="['w-5 h-5 transition-all duration-300', (item.path === '/' ? isExactActive : isActive) ? '' : 'group-hover:scale-110']" />
              <span class="flex-1">{{ item.name }}</span>
              
              <ChevronRight v-if="(item.path === '/' ? isExactActive : isActive)" class="w-4 h-4 opacity-70" />
              
              <!-- Hover glow effect -->
              <div v-if="!(item.path === '/' ? isExactActive : isActive)" class="absolute inset-0 rounded-xl bg-gradient-to-r from-emerald-500/0 via-emerald-500/5 to-emerald-500/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </div>
          </router-link>
        </nav>

        <!-- User Profile Card -->
        <div class="p-4 border-t border-slate-200/60 relative z-10">
          <div class="bg-gradient-to-br from-slate-50 to-emerald-50/50 rounded-xl p-3 mb-3 border border-slate-200/60">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
                {{ auth.user?.name?.[0] || 'U' }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-900 truncate">{{ auth.user?.name }}</p>
                <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">{{ auth.user?.role }}</p>
              </div>
            </div>
          </div>
          
          <button 
            @click="handleLogout"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-rose-600 hover:bg-rose-50 rounded-lg font-medium text-sm transition-all group border border-transparent hover:border-rose-200"
          >
            <LogOut class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
            <span>Déconnexion</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- Overlay for mobile -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false"
      class="fixed inset-0 bg-slate-900/20 backdrop-blur-sm z-40 lg:hidden animate-in fade-in duration-300"
    ></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
      <!-- Modern Navbar -->
      <header class="h-16 flex items-center justify-between px-6 bg-white/90 backdrop-blur-xl border-b border-slate-200/60 sticky top-0 z-30 shadow-sm">
        <div class="flex items-center gap-4">
          <button @click="isSidebarOpen = !isSidebarOpen" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-all lg:hidden">
            <Menu v-if="!isSidebarOpen" class="w-5 h-5" />
            <X v-else class="w-5 h-5" />
          </button>
          
          <div class="hidden sm:flex items-center gap-3 text-slate-400 bg-slate-100/80 px-4 py-2 rounded-lg border border-slate-200/60 group focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500/40 transition-all w-80">
            <Search class="w-4 h-4 group-focus-within:text-emerald-600 transition-colors" />
            <input type="text" placeholder="Recherche rapide..." class="bg-transparent border-none outline-none w-full text-sm font-medium text-slate-700 placeholder:text-slate-400">
          </div>
        </div>

        <div class="flex items-center gap-4">
          <button class="relative p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition-all group">
            <Bell class="w-5 h-5" />
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 border-2 border-white rounded-full group-hover:scale-110 transition-transform"></span>
          </button>
          
          <div class="h-8 w-px bg-slate-200"></div>

          <div class="flex items-center gap-3">
            <div class="hidden md:flex flex-col items-end">
              <span class="text-sm font-semibold text-slate-900">{{ auth.user?.name }}</span>
              <span class="text-xs font-medium text-emerald-600 uppercase tracking-wide">{{ auth.user?.role }}</span>
            </div>
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
              {{ auth.user?.name?.[0] || 'U' }}
            </div>
          </div>
        </div>
      </header>

      <!-- Content Area -->
      <div class="flex-1 overflow-y-auto custom-scrollbar">
        <div class="max-w-[1600px] mx-auto p-6">
          <router-view v-slot="{ Component }">
            <transition 
              enter-active-class="transition duration-300 ease-out"
              enter-from-class="opacity-0 translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              mode="out-in"
            >
              <component :is="Component" />
            </transition>
          </router-view>
        </div>
      </div>
    </main>
  </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #10b981;
}

/* Base resets for animations */
.animate-in {
  animation-fill-mode: both;
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.1;
  }
  50% {
    opacity: 0.15;
  }
}

.animate-pulse {
  animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
