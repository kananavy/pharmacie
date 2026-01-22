<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '../../api/axios'
import { formatCurrency } from '../../utils/formatting'
import { 
  TrendingUp, AlertTriangle, Calendar, ChevronRight, ArrowUpRight, Pill, 
  Clock, DollarSign, ClipboardList, PackageSearch, Timer
} from 'lucide-vue-next'

const props = defineProps<{
  user: any
}>()

// Interfaces
interface Medicament { id: number; nom: string; stock: number; prix: number; date_expiration: string; }
interface TopMed { medicament_id: number; total_qty: number; medicament: Medicament; }

const stats = ref({
  total_revenue: 0,
  sales_count: 0,
  stock_valuation: 0,
  top_medications: [] as TopMed[]
})
const alerts = ref({ stock_alerts: [] as Medicament[], expiration_alerts: [] as Medicament[] })
const loading = ref(true)

onMounted(async () => {
   try {
     const [statsRes, alertsRes] = await Promise.all([
       axios.get('/reports/daily'),
       axios.get('/medicaments/alerts/all')
     ])
     stats.value = statsRes.data
     alerts.value = alertsRes.data
   } catch (err) {
     console.error(err)
   } finally {
     loading.value = false
   }
})

const formatPrice = formatCurrency
</script>

<template>
  <div class="flex flex-col gap-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-bold text-slate-800">Vue d'ensemble - Administrateur</h2>
      <div class="text-sm text-slate-500 font-medium flex items-center gap-2 bg-white px-3 py-1 rounded border">
         <Calendar class="w-4 h-4"/> 
         {{ new Date().toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
      </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
       <!-- Revenue -->
       <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div class="flex justify-between items-center mb-2">
             <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><DollarSign class="w-5 h-5"/></div>
             <span class="text-xs font-bold uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Jour J</span>
          </div>
          <p class="text-slate-500 text-xs font-medium uppercase">Chiffre d'Affaires</p>
          <p class="text-2xl font-bold text-slate-900">{{ formatPrice(stats.total_revenue) }}</p>
       </div>

       <!-- Stock Value -->
       <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div class="flex justify-between items-center mb-2">
             <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg"><ClipboardList class="w-5 h-5"/></div>
          </div>
          <p class="text-slate-500 text-xs font-medium uppercase">Valeur Stock</p>
          <p class="text-2xl font-bold text-slate-900">{{ formatPrice(stats.stock_valuation) }}</p>
       </div>

       <!-- Alerts -->
       <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-rose-500">
          <div class="flex justify-between items-center mb-2">
             <div class="p-2 bg-rose-50 text-rose-600 rounded-lg"><AlertTriangle class="w-5 h-5"/></div>
             <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded">{{ alerts.stock_alerts.length }}</span>
          </div>
          <p class="text-slate-500 text-xs font-medium uppercase">Ruptures Stock</p>
          <p class="text-lg font-bold text-slate-900">Produits critiques</p>
       </div>

       <!-- Expirations -->
       <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-amber-500">
          <div class="flex justify-between items-center mb-2">
             <div class="p-2 bg-amber-50 text-amber-600 rounded-lg"><Clock class="w-5 h-5"/></div>
             <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">{{ alerts.expiration_alerts.length }}</span>
          </div>
          <p class="text-slate-500 text-xs font-medium uppercase">Péremptions</p>
          <p class="text-lg font-bold text-slate-900">À vérifier</p>
       </div>
    </div>

    <!-- Charts / Lists -->
    <div class="grid lg:grid-cols-3 gap-6">
       <!-- Top Sales -->
       <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-4">
          <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2"><TrendingUp class="w-5 h-5 text-emerald-500"/> Meilleures Ventes (Jour)</h3>
          <div v-if="loading" class="animate-pulse space-y-2">
             <div v-for="i in 3" :key="i" class="h-12 bg-slate-100 rounded"></div>
          </div>
          <div v-else-if="stats.top_medications.length" class="space-y-3">
             <div v-for="(item, idx) in stats.top_medications" :key="idx" class="flex items-center justify-between p-3 border rounded-lg hover:bg-slate-50">
                <div class="flex items-center gap-3">
                   <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold">{{ idx+1 }}</div>
                   <div>
                      <p class="font-medium text-slate-900">{{ item.medicament.nom }}</p>
                      <p class="text-xs text-slate-500">{{ item.total_qty }} unités vendues</p>
                   </div>
                </div>
                <p class="font-bold text-emerald-600">{{ formatPrice(item.total_qty * item.medicament.prix) }}</p>
             </div>
          </div>
          <div v-else class="text-center py-10 text-slate-400">Aucune vente aujourd'hui</div>
       </div>

       <!-- Quick Actions -->
       <div class="bg-white rounded-xl border border-slate-200 p-4">
          <h3 class="font-bold text-slate-800 mb-4">Actions Rapides</h3>
          <div class="space-y-2">
             <router-link to="/users" class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                <div class="p-2 bg-indigo-100 text-indigo-600 rounded"><PackageSearch class="w-4 h-4"/></div>
                <div>
                   <p class="font-medium text-slate-900">Gérer Utilisateurs</p>
                   <p class="text-xs text-slate-500">Ajouter/Modifier comptes</p>
                </div>
                <ChevronRight class="ml-auto w-4 h-4 text-slate-400"/>
             </router-link>
             <router-link to="/medications" class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors">
                <div class="p-2 bg-emerald-100 text-emerald-600 rounded"><Pill class="w-4 h-4"/></div>
                <div>
                   <p class="font-medium text-slate-900">Inventaire</p>
                   <p class="text-xs text-slate-500">Ajuster stocks</p>
                </div>
                <ChevronRight class="ml-auto w-4 h-4 text-slate-400"/>
             </router-link>
          </div>
       </div>
    </div>
  </div>
</template>
