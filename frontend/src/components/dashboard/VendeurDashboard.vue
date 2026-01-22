<script setup lang="ts">
import { 
  ShoppingCart, 
  FileText, 
  UserCircle, 
  Plus, 
  Search,
  ClipboardList
} from 'lucide-vue-next'

const props = defineProps<{
  user: any
}>()

</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6">
     <!-- Welcome Banner -->
     <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10">
           <h1 class="text-3xl font-bold mb-2">Bonjour, {{ user?.name }} 👋</h1>
           <p class="text-blue-100 mb-6 max-w-lg">Prêt pour une nouvelle journée ? Gérez vos ventes, ordonnances et patients depuis votre espace dédié.</p>
           
           <div class="flex gap-3">
              <router-link to="/orders" class="px-4 py-2 bg-white text-blue-600 rounded-lg font-bold hover:bg-blue-50 transition-colors flex items-center gap-2">
                 <ShoppingCart class="w-4 h-4" /> Nouvelle Vente
              </router-link>
              <router-link to="/patients" class="px-4 py-2 bg-blue-500/30 backdrop-blur text-white border border-white/20 rounded-lg font-bold hover:bg-blue-500/40 transition-colors flex items-center gap-2">
                 <UserCircle class="w-4 h-4" /> Nouveau Patient
              </router-link>
           </div>
        </div>
        
        <!-- Decoration -->
        <ShoppingCart class="absolute -bottom-6 -right-6 w-48 h-48 text-white/10 rotate-12" />
     </div>

     <!-- Quick Access Grid -->
     <div class="grid md:grid-cols-2 gap-4">
        <router-link to="/orders" class="group p-6 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-blue-500 hover:ring-1 hover:ring-blue-500 transition-all">
           <div class="flex items-center gap-4 mb-3">
              <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                 <ClipboardList class="w-6 h-6" />
              </div>
              <div>
                 <h3 class="font-bold text-slate-900 group-hover:text-blue-600">Mes Ventes</h3>
                 <p class="text-sm text-slate-500">Créer et gérer les commandes</p>
              </div>
           </div>
           <p class="text-sm text-slate-600">Accédez au module de création de tickets pour la caisse.</p>
        </router-link>

        <router-link to="/prescriptions" class="group p-6 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-indigo-500 hover:ring-1 hover:ring-indigo-500 transition-all">
           <div class="flex items-center gap-4 mb-3">
              <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                 <FileText class="w-6 h-6" />
              </div>
              <div>
                 <h3 class="font-bold text-slate-900 group-hover:text-indigo-600">Ordonnances</h3>
                 <p class="text-sm text-slate-500">Numériser et traiter</p>
              </div>
           </div>
           <p class="text-sm text-slate-600">Gérez les ordonnances reçues et associez-les aux patients.</p>
        </router-link>
     </div>
  </div>
</template>
