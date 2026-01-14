<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { Lock, Mail, Pill, AlertCircle, Loader2, ArrowRight, ShieldCheck, User, UserPlus } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'caissier'
})

const loading = ref(false)
const error = ref('')
const success = ref(false)

const handleRegister = async () => {
  loading.value = true
  error.value = ''
  
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Les mots de passe ne correspondent pas.'
    loading.value = false
    return
  }

  try {
    const response = await auth.register({
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      role: form.value.role
    })
    
    if (response) {
      success.value = true
      setTimeout(() => {
        router.push('/')
      }, 1500)
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Une erreur est survenue lors de l\'inscription.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 relative overflow-hidden font-sans">
    <!-- Abstract Medical Background Decor -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-100 rounded-full blur-[100px] opacity-60"></div>
    <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] bg-emerald-50 rounded-full blur-[120px] opacity-40"></div>
    
    <!-- Grid Pattern -->
    <div class="absolute inset-0 z-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#064e3b 1px, transparent 1px); background-size: 40px 40px;"></div>

    <div class="w-full max-w-xl px-6 relative z-10 animate-in fade-in zoom-in-95 duration-700">
      <!-- Logo & Branding -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-[2.5rem] shadow-2xl shadow-emerald-500/30 mb-8 border-4 border-white group hover:rotate-6 transition-transform duration-500">
          <Pill class="text-white w-10 h-10" />
        </div>
        <h1 class="text-4xl font-black text-slate-900 tracking-tighter mb-2 uppercase">Pharmacie Pro</h1>
        <p class="text-slate-500 font-bold uppercase tracking-[0.2em] text-[10px] flex items-center justify-center gap-2">
          <ShieldCheck class="w-3 h-3 text-emerald-500" />
          Création de Compte Professionnel
        </p>
      </div>

      <!-- Register Card -->
      <div class="bg-white p-12 rounded-[3.5rem] shadow-4xl border border-emerald-50 relative overflow-hidden group">
        <!-- Top Accent -->
        <div class="absolute top-0 left-0 w-full h-3 bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-600"></div>

        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Name Input -->
          <div class="space-y-3">
            <label class="block text-[10px] font-black text-emerald-950 uppercase tracking-[0.3em] ml-2">Nom Complet</label>
            <div class="relative group/input">
              <User class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within/input:text-emerald-500 transition-colors" />
              <input 
                v-model="form.name" 
                type="text" 
                required 
                placeholder="Dr. Jean Dupont"
                class="w-full bg-slate-50 border border-slate-100 rounded-[1.5rem] pl-16 pr-8 py-5 font-bold text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all placeholder:text-slate-300 shadow-inner"
              >
            </div>
          </div>

          <!-- Email Input -->
          <div class="space-y-3">
            <label class="block text-[10px] font-black text-emerald-950 uppercase tracking-[0.3em] ml-2">Email Professionnel</label>
            <div class="relative group/input">
              <Mail class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within/input:text-emerald-500 transition-colors" />
              <input 
                v-model="form.email" 
                type="email" 
                required 
                placeholder="nom@pharmacie.com"
                class="w-full bg-slate-50 border border-slate-100 rounded-[1.5rem] pl-16 pr-8 py-5 font-bold text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all placeholder:text-slate-300 shadow-inner"
              >
            </div>
          </div>

          <!-- Role Selection -->
          <div class="space-y-3">
            <label class="block text-[10px] font-black text-emerald-950 uppercase tracking-[0.3em] ml-2">Rôle</label>
            <div class="relative group/input">
              <UserPlus class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within/input:text-emerald-500 transition-colors" />
              <select 
                v-model="form.role"
                required
                class="w-full bg-slate-50 border border-slate-100 rounded-[1.5rem] pl-16 pr-8 py-5 font-bold text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-inner appearance-none cursor-pointer"
              >
                <option value="caissier">Caissier</option>
                <option value="vendeur">Vendeur</option>
                <option value="admin">Administrateur</option>
              </select>
            </div>
          </div>

          <!-- Password Input -->
          <div class="space-y-3">
            <label class="block text-[10px] font-black text-emerald-950 uppercase tracking-[0.3em] ml-2">Mot de Passe</label>
            <div class="relative group/input">
              <Lock class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within/input:text-emerald-500 transition-colors" />
              <input 
                v-model="form.password" 
                type="password" 
                required 
                minlength="8"
                placeholder="••••••••"
                class="w-full bg-slate-50 border border-slate-100 rounded-[1.5rem] pl-16 pr-8 py-5 font-bold text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all placeholder:text-slate-300 shadow-inner tracking-widest"
              >
            </div>
          </div>

          <!-- Password Confirmation -->
          <div class="space-y-3">
            <label class="block text-[10px] font-black text-emerald-950 uppercase tracking-[0.3em] ml-2">Confirmer Mot de Passe</label>
            <div class="relative group/input">
              <Lock class="absolute left-6 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within/input:text-emerald-500 transition-colors" />
              <input 
                v-model="form.password_confirmation" 
                type="password" 
                required 
                minlength="8"
                placeholder="••••••••"
                class="w-full bg-slate-50 border border-slate-100 rounded-[1.5rem] pl-16 pr-8 py-5 font-bold text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all placeholder:text-slate-300 shadow-inner tracking-widest"
              >
            </div>
          </div>

          <!-- Error Message -->
          <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="error" class="flex items-center gap-3 p-4 bg-rose-50 text-rose-600 rounded-2xl border border-rose-100 animate-shake">
              <AlertCircle class="w-5 h-5" />
              <p class="text-[11px] font-black uppercase tracking-wider">{{ error }}</p>
            </div>
          </transition>

          <!-- Success Message -->
          <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
            <div v-if="success" class="flex items-center gap-3 p-4 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100">
              <ShieldCheck class="w-5 h-5" />
              <p class="text-[11px] font-black uppercase tracking-wider">Inscription réussie ! Redirection...</p>
            </div>
          </transition>

          <!-- Submit Button -->
          <button 
            type="submit" 
            :disabled="loading || success"
            class="w-full py-6 bg-emerald-950 hover:bg-emerald-900 disabled:bg-slate-200 disabled:text-slate-400 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.4em] transition-all duration-500 shadow-2xl shadow-emerald-950/20 active:scale-95 flex items-center justify-center gap-4 group/btn relative overflow-hidden"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover/btn:translate-x-full transition-transform duration-1000"></div>
            <span v-if="!loading" class="transform group-hover/btn:translate-x-1 transition-transform">Créer Mon Compte</span>
            <Loader2 v-else class="w-6 h-6 animate-spin" />
            <ArrowRight v-if="!loading" class="w-5 h-5 opacity-0 -translate-x-4 group-hover/btn:opacity-100 group-hover/btn:translate-x-0 transition-all duration-500" />
          </button>
        </form>

        <!-- Login Link -->
        <div class="mt-8 text-center">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
            Vous avez déjà un compte ?
            <router-link to="/login" class="text-emerald-600 hover:text-emerald-700 font-black ml-2 underline decoration-2 underline-offset-4">
              Se Connecter
            </router-link>
          </p>
        </div>
      </div>
      
      <!-- Footer Copyright -->
      <footer class="mt-12 text-center text-slate-400 text-[10px] font-bold uppercase tracking-[0.2em]">
        &copy; 2026 Pharmacie Pro • Solution Cloud Haute Sécurité
      </footer>
    </div>
  </div>
</template>

<style scoped>
.shadow-4xl {
  box-shadow: 0 40px 100px -20px rgba(6, 78, 59, 0.15), 0 30px 60px -30px rgba(0, 0, 0, 0.2);
}

.animate-shake {
  animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>
