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
  Settings2
} from 'lucide-vue-next'
import { ref, computed } from 'vue'

const router = useRouter()
const auth = useAuthStore()
const isSidebarOpen = ref(true)
const allNavigation = [
  { name: 'Tableau de bord', icon: LayoutDashboard, path: '/', roles: ['admin', 'caissier'] },
  { name: 'Vente', icon: ShoppingCart, path: '/pos', roles: ['admin', 'caissier'] },
  { name: 'Médicaments', icon: Pill, path: '/medications', roles: ['admin', 'caissier'] },
  { name: 'Ordonnances', icon: FileText, path: '/prescriptions', roles: ['admin', 'caissier'] },
  { name: 'Patients', icon: UserCircle, path: '/patients', roles: ['admin', 'caissier'] },
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
  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans">
    <!-- Sidebar -->
    <aside 
      :class="[
        'fixed inset-y-0 left-0 z-50 w-72 bg-emerald-950 text-emerald-100 transition-transform duration-500 ease-in-out lg:relative lg:translate-x-0 border-r border-emerald-900',
        isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <div class="flex flex-col h-full relative overflow-hidden">
        <!-- Decoration SVG (Abstract DNA/Organic shapes for medical feel) -->
        <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-emerald-800 rounded-full blur-[80px] opacity-20 pointer-events-none"></div>
        
        <!-- Sidebar Header -->
        <div class="h-24 flex items-center gap-4 px-8 border-b border-emerald-900/50 bg-emerald-950/40 relative z-10">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-2xl shadow-emerald-500/40">
            <Pill class="text-white w-7 h-7" />
          </div>
          <div>
            <h1 class="text-xl font-black text-white tracking-tighter leading-none">Pharmacie Pro</h1>
            <p class="text-[10px] font-bold text-emerald-400/60 uppercase tracking-widest mt-1">Système Officinal</p>
          </div>
        </div>

        <!-- Sidebar Nav -->
        <nav class="flex-1 overflow-y-auto py-8 px-5 space-y-2 relative z-10 custom-scrollbar-dark">
          <router-link 
            v-for="item in navigation" 
            :key="item.path"
            :to="item.path"
            v-slot="{ isActive }"
          >
            <div 
              :class="[
                'flex items-center gap-4 px-5 py-4 rounded-2xl font-black text-sm transition-all duration-300 group relative overflow-hidden',
                isActive 
                  ? 'bg-emerald-500 text-emerald-950 shadow-xl shadow-emerald-500/20' 
                  : 'text-emerald-300 hover:bg-emerald-900/50 hover:text-white'
              ]"
            >
              <component :is="item.icon" :class="['w-5 h-5 transition-transform duration-300', isActive ? 'scale-110' : 'group-hover:scale-110']" />
              <span class="uppercase tracking-widest">{{ item.name }}</span>
              
              <div v-if="isActive" class="ml-auto w-2 h-2 rounded-full bg-emerald-950 shadow-sm border border-emerald-400/50"></div>
              
              <!-- Hover reflection effect -->
              <div v-if="!isActive" class="absolute inset-0 bg-gradient-to-r from-transparent via-emerald-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
            </div>
          </router-link>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-6 border-t border-emerald-900/50 bg-emerald-950/40 relative z-10">
          <button 
            @click="handleLogout"
            class="w-full flex items-center justify-center gap-3 px-5 py-4 text-rose-400 hover:bg-rose-500/10 hover:text-white rounded-2xl font-black text-xs uppercase tracking-widest transition-all group border border-transparent hover:border-rose-500/20"
          >
            <LogOut class="w-5 h-5 group-hover:-translate-x-1 transition-transform" />
            <span>Déconnexion</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- Overlay for mobile -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false"
      class="fixed inset-0 bg-emerald-950/60 backdrop-blur-md z-40 lg:hidden animate-in fade-in duration-300"
    ></div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
      <!-- Navbar -->
      <header class="h-24 flex items-center justify-between px-10 bg-white shadow-2xl shadow-slate-200/50 border-b border-emerald-50 sticky top-0 z-30">
        <div class="flex items-center gap-6">
          <button @click="isSidebarOpen = !isSidebarOpen" class="p-3 text-emerald-950 bg-emerald-50 hover:bg-emerald-100 rounded-2xl transition-all active:scale-90 lg:hidden">
            <Menu v-if="!isSidebarOpen" class="w-6 h-6" />
            <X v-else class="w-6 h-6" />
          </button>
          
          <div class="hidden sm:flex items-center gap-4 text-slate-400 bg-slate-50 px-6 py-3.5 rounded-[1.5rem] border border-slate-100 group focus-within:ring-4 focus-within:ring-emerald-500/10 focus-within:border-emerald-500/30 transition-all w-96">
            <Search class="w-5 h-5 group-focus-within:text-emerald-600 transition-colors" />
            <input type="text" placeholder="Recherche rapide..." class="bg-transparent border-none outline-none w-full text-sm font-bold text-slate-700 placeholder:text-slate-300">
          </div>
        </div>

        <div class="flex items-center gap-6">
          <button class="relative p-3.5 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 rounded-2xl transition-all hover:scale-105 active:scale-95 group">
            <Bell class="w-6 h-6" />
            <span class="absolute top-3 right-3 w-3 h-3 bg-rose-500 border-[3px] border-white rounded-full group-hover:scale-125 transition-transform"></span>
          </button>
          
          <div class="h-10 w-px bg-slate-100"></div>

          <div class="flex items-center gap-4 group cursor-default">
            <div class="flex flex-col items-end">
              <span class="text-sm font-black text-slate-900 group-hover:text-emerald-700 transition-colors">{{ auth.user?.name }}</span>
              <span class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg uppercase tracking-widest">{{ auth.user?.role }}</span>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-emerald-400 flex items-center justify-center text-emerald-950 font-black text-xl shadow-2xl shadow-emerald-500/30 ring-4 ring-emerald-50 group-hover:rotate-6 transition-all duration-500">
              {{ auth.user?.name?.[0] || 'U' }}
            </div>
          </div>
        </div>
      </header>

      <!-- Content Area -->
      <div class="flex-1 overflow-y-auto custom-scrollbar bg-slate-50/30">
        <div class="max-w-[1600px] mx-auto p-10 pt-8">
          <router-view v-slot="{ Component }">
            <transition 
              enter-active-class="transition duration-500 ease-out"
              enter-from-class="opacity-0 translate-y-6"
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
  width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #d1fae5;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #10b981;
}

.custom-scrollbar-dark::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar-dark::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar-dark::-webkit-scrollbar-thumb {
  background: rgba(16, 185, 129, 0.1);
  border-radius: 10px;
}
.custom-scrollbar-dark::-webkit-scrollbar-thumb:hover {
  background: rgba(16, 185, 129, 0.3);
}

/* Base resets for animations */
.animate-in {
  animation-fill-mode: both;
}
</style>
