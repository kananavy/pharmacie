<script setup lang="ts">
import { 
  CreditCard, 
  ArrowRight,
  Calculator,
  ScanLine
} from 'lucide-vue-next'

const props = defineProps<{
  user: any
}>()
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6">
     <!-- Cashier Status Banner -->
     <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
           <div>
              <h1 class="text-3xl font-bold mb-2">Caisse Ouverte 🟢</h1>
              <p class="text-emerald-100">Session active pour {{ user?.name }}</p>
           </div>
           
           <router-link to="/cashier" class="px-6 py-3 bg-white text-emerald-700 rounded-xl font-bold text-lg hover:bg-emerald-50 transition-colors shadow-lg flex items-center gap-2">
              <ScanLine class="w-5 h-5" /> Accéder à la Caisse
           </router-link>
        </div>
        
        <!-- Decoration -->
        <CreditCard class="absolute -bottom-6 -right-6 w-48 h-48 text-white/10 rotate-12" />
     </div>

     <!-- Stats / Info (To be connected to backend later if needed) -->
     <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
           <h3 class="font-bold text-slate-700 mb-2 flex items-center gap-2"><Calculator class="w-4 h-4"/> Rappel Clôture</h3>
           <p class="text-slate-600 text-sm">N'oubliez pas de vérifier votre fond de caisse à la fin de votre service. Les écarts doivent être signalés au superviseur.</p>
        </div>

        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between group cursor-pointer hover:border-emerald-500 transition-colors">
           <div>
              <h3 class="font-bold text-slate-900">Encaisser un Ticket</h3>
              <p class="text-sm text-slate-500">Mode scanner ou saisie manuelle</p>
           </div>
           <ArrowRight class="w-5 h-5 text-slate-400 group-hover:text-emerald-500 transition-colors" />
        </div>
     </div>
  </div>
</template>
