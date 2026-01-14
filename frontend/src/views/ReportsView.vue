<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '../api/axios'
import { formatCurrency } from '../utils/formatting'
import { 
  BarChart3, 
  TrendingUp, 
  Download, 
  DollarSign,
  PieChart,
  CalendarDays,
  Zap
} from 'lucide-vue-next'

interface TopMedication {
  id: number;
  medicament: {
    nom: string;
    categorie: string;
    prix: number;
  };
  total_qty: number;
}

const stats = ref({
  daily_revenue: 0,
  monthly_revenue: 0,
  top_medications: [] as TopMedication[]
})

const loading = ref(true)

onMounted(async () => {
  try {
    const [dailyRes, monthlyRes] = await Promise.all([
      axios.get('/reports/daily'),
      axios.get('/reports/monthly')
    ])
    stats.value = {
      daily_revenue: dailyRes.data.total_revenue,
      monthly_revenue: monthlyRes.data.total_revenue,
      top_medications: dailyRes.data.top_medications
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})

const formatPrice = (price: number) => formatCurrency(price)
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4 overflow-auto custom-scrollbar pr-1">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <BarChart3 class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Analyses & Business Intelligence</h1>
          <p class="text-xs text-slate-500 font-medium">Pilotage de la performance financière et opérationnelle.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button class="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition-colors shadow-sm">
          <Download class="w-3.5 h-3.5 text-slate-400" />
          Exporter CSV
        </button>
        <button class="flex items-center gap-2 px-3 py-1.5 bg-emerald-600 text-white rounded text-[11px] font-bold hover:bg-emerald-700 transition-all shadow-sm active:scale-95">
          <CalendarDays class="w-3.5 h-3.5" />
          Rapport Mensuel
        </button>
      </div>
    </div>

    <!-- Highlights Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Revenu Jour -->
      <div class="bg-white p-5 rounded-md border border-slate-200 shadow-sm relative overflow-hidden group">
        <div class="relative z-10">
          <div class="flex items-center justify-between mb-4">
            <div class="w-9 h-9 bg-emerald-50 rounded flex items-center justify-center text-emerald-600">
              <DollarSign class="w-5 h-5" />
            </div>
            <div class="flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
              <ArrowUpRight class="w-3 h-3" />
              +8.4%
            </div>
          </div>
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Revenu du Jour</div>
          <div class="text-2xl font-bold text-slate-900 font-mono tracking-tight">{{ formatPrice(stats.daily_revenue) }}</div>
        </div>
      </div>

      <!-- Performance Mensuelle -->
      <div class="bg-slate-900 p-5 rounded-md border border-slate-800 shadow-lg relative overflow-hidden group">
        <div class="absolute -top-6 -right-6 w-20 h-20 bg-emerald-500/10 rounded-full"></div>
        <div class="relative z-10">
          <div class="flex items-center justify-between mb-4">
            <div class="w-9 h-9 bg-emerald-500/20 rounded flex items-center justify-center text-emerald-400">
              <TrendingUp class="w-5 h-5" />
            </div>
            <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded uppercase tracking-wider">Objectif 92%</span>
          </div>
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Performance Mensuelle</div>
          <div class="text-2xl font-bold text-white font-mono tracking-tight">{{ formatPrice(stats.monthly_revenue) }}</div>
        </div>
      </div>

      <!-- Estimation Bénéfice -->
      <div class="bg-white p-5 rounded-md border border-slate-200 shadow-sm relative overflow-hidden group">
        <div class="relative z-10">
          <div class="flex items-center justify-between mb-4">
            <div class="w-9 h-9 bg-slate-100 rounded flex items-center justify-center text-slate-500">
              <ClipboardCheck class="w-5 h-5" />
            </div>
          </div>
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Estimation Bénéfice (25%)</div>
          <div class="text-2xl font-bold text-slate-900 font-mono tracking-tight">{{ formatPrice(stats.monthly_revenue * 0.25) }}</div>
          <div class="mt-2 text-[9px] text-slate-400 font-medium italic underline decoration-slate-200 underline-offset-4">Basé sur une marge brute théorique</div>
        </div>
      </div>
    </div>

    <!-- Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 flex-1 min-h-0 pb-1">
      <!-- Top Ventes Table -->
      <div class="lg:col-span-8 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Zap class="w-4 h-4 text-amber-500" />
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Best-Sellers du Jour</span>
          </div>
          <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic">Volume de rotation officinale</div>
        </div>

        <div class="flex-1 overflow-auto custom-scrollbar">
          <table class="w-full text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 sticky top-0 z-10">
              <tr>
                <th class="px-4 py-2 font-medium text-left">Produit</th>
                <th class="px-4 py-2 font-medium text-center">Volume</th>
                <th class="px-4 py-2 font-medium text-left">Catégorie</th>
                <th class="px-4 py-2 font-medium text-right">CA Brut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="4" class="px-4 py-3 border-b border-slate-100">
                  <div class="h-6 bg-slate-50 rounded"></div>
                </td>
              </tr>
              <tr 
                v-else
                v-for="(item, idx) in stats.top_medications" 
                :key="item.id"
                class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <span class="text-[10px] font-bold text-slate-300 w-4">{{ idx + 1 }}</span>
                    <span class="font-semibold text-slate-700">{{ item.medicament.nom }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">{{ item.total_qty }} vendus</span>
                </td>
                <td class="px-4 py-3 text-slate-500 text-[10px] font-medium uppercase">{{ item.medicament.categorie }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">{{ formatPrice(item.total_qty * item.medicament.prix) }}</td>
              </tr>
              <tr v-if="!loading && !stats.top_medications.length">
                <td colspan="4" class="px-4 py-20 text-center flex flex-col items-center">
                  <div class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                    <PieChart class="w-5 h-5 text-slate-200" />
                  </div>
                  <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest italic">Aucune donnée disponible</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Inventory Health Column -->
      <div class="lg:col-span-4 flex flex-col space-y-4">
        <div class="flex-1 bg-emerald-50 border border-emerald-100 rounded-md p-5 flex flex-col">
          <div class="flex items-center gap-2 mb-6">
            <div class="p-1.5 bg-emerald-100 rounded text-emerald-700">
              <PieChart class="w-4 h-4" />
            </div>
            <h2 class="text-xs font-bold text-emerald-900 uppercase tracking-tight">Rotation des Stocks</h2>
          </div>
          
          <div class="space-y-6 flex-1">
            <div class="space-y-2">
              <div class="flex justify-between items-end px-1">
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-widest">Antibiotiques</span>
                <span class="text-[10px] font-bold text-emerald-600">82% (Opt.)</span>
              </div>
              <div class="h-2 bg-emerald-200/50 rounded-full overflow-hidden border border-emerald-200/30">
                <div class="h-full bg-emerald-500 w-[82%] shadow-sm shadow-emerald-500/20"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex justify-between items-end px-1">
                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-widest">Analgésiques</span>
                <span class="text-[10px] font-bold text-amber-600">45% (Moy.)</span>
              </div>
              <div class="h-2 bg-amber-200/50 rounded-full overflow-hidden border border-amber-200/30">
                <div class="h-full bg-amber-500 w-[45%] shadow-sm shadow-amber-500/20"></div>
              </div>
            </div>

            <div class="mt-8 p-4 bg-white/60 border border-emerald-200/50 rounded-lg shadow-sm">
              <div class="flex items-center gap-2 mb-2">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                <p class="text-[10px] font-bold text-emerald-900 uppercase tracking-widest">Conseil Stratégique :</p>
              </div>
              <p class="text-[11px] font-medium text-emerald-800 leading-relaxed italic">
                Anticipez le réapprovisionnement des **Antipyrétiques** d'ici 48h pour couvrir le pic saisonnier prévu ce week-end.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
