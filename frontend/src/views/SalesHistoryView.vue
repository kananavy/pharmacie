<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from '../api/axios'
import { 
  ShoppingCart,
  ReceiptText,
  User,
  Calendar,
  Tag,
  Eye,
  X,
  RotateCcw,
  Undo2,
  AlertCircle
} from 'lucide-vue-next'
import { formatCurrency } from '../utils/formatting'

interface Medicament {
  nom: string;
  code: string;
  prix: number;
}

interface Lot {
    numero_lot: string;
    date_expiration: string;
}

interface DetailVente {
  id: number;
  medicament_id: number;
  medicament: Medicament;
  lot_id?: number;
  lot?: Lot;
  quantite: number;
  prix_unitaire: number;
  type_vente: string;
}

interface Vente {
  id: number;
  total: number;
  user_id: number;
  user: { name: string };
  ordonnance_id?: number;
  mode_paiement: string;
  montant_recu: number;
  montant_rendu: number;
  status: 'completed' | 'cancelled' | 'returned' | 'returned_partially';
  created_at: string;
  details: DetailVente[];
}

const sales = ref<Vente[]>([])
const loading = ref(true)
const selectedSale = ref<Vente | null>(null)
const showDetailsModal = ref(false)
const showCancelModal = ref(false)
const showReturnModal = ref(false)
const cancelMotif = ref('')
const returnMotif = ref('')
const returnedItems = ref<{ detail_vente_id: number; quantite: number }[]>([])

onMounted(fetchSales)

async function fetchSales() {
  loading.value = true
  try {
    const response = await axios.get('/ventes')
    sales.value = response.data
  } catch (error) {
    console.error('Erreur lors de la récupération des ventes:', error)
    alert('Erreur lors de la récupération des ventes.')
  } finally {
    loading.value = false
  }
}

function openDetailsModal(sale: Vente) {
  selectedSale.value = sale
  showDetailsModal.value = true
}

function openCancelModal(sale: Vente) {
  selectedSale.value = sale
  cancelMotif.value = ''
  showCancelModal.value = true
}

async function confirmCancelSale() {
  if (!selectedSale.value || !cancelMotif.value) {
    alert('Veuillez fournir un motif pour l\'annulation.')
    return
  }
  try {
    await axios.post(`/ventes/${selectedSale.value.id}/cancel`, { motif: cancelMotif.value })
    alert('Vente annulée avec succès!')
    showCancelModal.value = false
    await fetchSales() // Refresh sales list
  } catch (error: any) {
    console.error('Erreur lors de l\'annulation de la vente:', error)
    alert(error.response?.data?.message || 'Erreur lors de l\'annulation de la vente.')
  }
}

function openReturnModal(sale: Vente) {
  selectedSale.value = sale
  returnMotif.value = ''
  returnedItems.value = sale.details.map(detail => ({
    detail_vente_id: detail.id,
    quantite: 0 // Initialize with 0
  }))
  showReturnModal.value = true
}

async function confirmReturnPartial() {
  if (!selectedSale.value || !returnMotif.value) {
    alert('Veuillez fournir un motif pour le retour.')
    return
  }
  const itemsToReturn = returnedItems.value.filter(item => item.quantite > 0)
  if (itemsToReturn.length === 0) {
    alert('Veuillez spécifier au moins un article à retourner.')
    return
  }

  try {
    await axios.post(`/ventes/${selectedSale.value.id}/return-partial`, {
      returned_items: itemsToReturn,
      motif: returnMotif.value,
    })
    alert('Retour partiel enregistré avec succès!')
    showReturnModal.value = false
    await fetchSales() // Refresh sales list
  } catch (error: any) {
    console.error('Erreur lors du retour partiel:', error)
    alert(error.response?.data?.message || 'Erreur lors du retour partiel.')
  }
}

const getStatusClass = (status: Vente['status']) => {
  switch (status) {
    case 'completed': return 'bg-emerald-100 text-emerald-700'
    case 'cancelled': return 'bg-rose-100 text-rose-700'
    case 'returned_partially': return 'bg-orange-100 text-orange-700'
    case 'returned': return 'bg-blue-100 text-blue-700' // For return transactions
    default: return 'bg-slate-100 text-slate-700'
  }
}

const getStatusText = (status: Vente['status']) => {
  switch (status) {
    case 'completed': return 'Complétée'
    case 'cancelled': return 'Annulée'
    case 'returned_partially': return 'Retour partiel'
    case 'returned': return 'Retour'
    default: return 'Inconnu'
  }
}

const getDetailQuantitySold = (detailId: number) => {
    const detail = selectedSale.value?.details.find(d => d.id === detailId);
    return detail ? detail.quantite : 0;
}

</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-indigo-100 text-indigo-700 flex items-center justify-center">
          <ReceiptText class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Historique des Ventes</h1>
          <p class="text-xs text-slate-500">Consultez et gérez les transactions de vente.</p>
        </div>
      </div>
    </div>

    <!-- Tableau des ventes -->
    <section class="flex-1 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center gap-3 text-xs">
        <div class="flex-1 flex items-center gap-2">
          <Search class="w-4 h-4 text-slate-400" />
          <input
            type="text"
            placeholder="Rechercher une vente..."
            class="w-full bg-transparent border-none outline-none text-xs text-slate-800"
          />
        </div>
        <div class="text-[11px] text-slate-400">
          {{ sales.length }} ventes
        </div>
      </div>

      <div class="flex-1 overflow-auto">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
            <tr>
              <th class="px-3 py-2 font-medium text-left">ID Vente</th>
              <th class="px-3 py-2 font-medium text-left">Date</th>
              <th class="px-3 py-2 font-medium text-left">Vendeur</th>
              <th class="px-3 py-2 font-medium text-right">Total</th>
              <th class="px-3 py-2 font-medium text-center">Statut</th>
              <th class="px-3 py-2 font-medium text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading" v-for="i in 6" :key="i" class="animate-pulse">
              <td colspan="6" class="px-3 py-3">
                <div class="h-6 bg-slate-100 rounded"></div>
              </td>
            </tr>
            <tr v-else v-for="sale in sales" :key="sale.id" class="border-b border-slate-100 hover:bg-slate-50">
              <td class="px-3 py-2 text-slate-800 font-mono">{{ sale.id }}</td>
              <td class="px-3 py-2 text-slate-600">{{ new Date(sale.created_at).toLocaleString() }}</td>
              <td class="px-3 py-2 text-slate-600">{{ sale.user.name }}</td>
              <td class="px-3 py-2 text-slate-900 font-semibold text-right">{{ formatCurrency(sale.total) }}</td>
              <td class="px-3 py-2 text-center">
                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold', getStatusClass(sale.status)]">
                  {{ getStatusText(sale.status) }}
                </span>
              </td>
              <td class="px-3 py-2 text-right">
                <button
                  @click="openDetailsModal(sale)"
                  class="inline-flex items-center gap-1.5 px-2 py-1 text-[11px] text-slate-700 border border-slate-300 rounded hover:bg-slate-50 mr-2"
                >
                  <Eye class="w-3.5 h-3.5" />
                  Détails
                </button>
                <button
                  v-if="sale.status === 'completed'"
                  @click="openCancelModal(sale)"
                  class="inline-flex items-center gap-1.5 px-2 py-1 text-[11px] text-rose-700 border border-rose-300 rounded hover:bg-rose-50 mr-2"
                >
                  <X class="w-3.5 h-3.5" />
                  Annuler
                </button>
                <button
                  v-if="sale.status === 'completed' || sale.status === 'returned_partially'"
                  @click="openReturnModal(sale)"
                  class="inline-flex items-center gap-1.5 px-2 py-1 text-[11px] text-blue-700 border border-blue-300 rounded hover:bg-blue-50"
                >
                  <Undo2 class="w-3.5 h-3.5" />
                  Retour
                </button>
              </td>
            </tr>
            <tr v-if="!loading && sales.length === 0">
              <td colspan="6" class="px-3 py-6 text-center text-[11px] text-slate-400">
                Aucune vente enregistrée.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Details Modal -->
    <Teleport to="body">
      <div v-if="showDetailsModal && selectedSale" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div class="bg-white w-full max-w-2xl rounded-lg shadow-lg border border-slate-200 max-h-[90vh] overflow-auto">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50 text-xs">
            <h2 class="text-sm font-semibold text-slate-900">Détails Vente #{{ selectedSale.id }}</h2>
            <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600">
              <X class="w-4 h-4" />
            </button>
          </div>
          <div class="px-5 py-4 space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-2 text-slate-600">
              <p><strong>Vendeur:</strong> {{ selectedSale.user.name }}</p>
              <p><strong>Date:</strong> {{ new Date(selectedSale.created_at).toLocaleString() }}</p>
              <p><strong>Mode de Paiement:</strong> {{ selectedSale.mode_paiement }}</p>
              <p><strong>Statut:</strong> <span :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold', getStatusClass(selectedSale.status)]">{{ getStatusText(selectedSale.status) }}</span></p>
              <p class="col-span-2 text-lg font-bold text-slate-900">Total: {{ formatCurrency(selectedSale.total) }}</p>
            </div>
            <div class="border-t border-slate-200 pt-3">
              <h3 class="font-semibold text-slate-800 mb-2">Articles vendus:</h3>
              <ul class="space-y-2">
                <li v-for="detail in selectedSale.details" :key="detail.id" class="border border-slate-100 p-2 rounded">
                  <div class="flex justify-between">
                    <p class="font-semibold">{{ detail.medicament.nom }} (Lot: {{ detail.lot?.numero_lot || 'N/A' }})</p>
                    <p>{{ formatCurrency(detail.prix_unitaire * detail.quantite) }}</p>
                  </div>
                  <p class="text-slate-500 text-[11px]">{{ detail.quantite }} {{ detail.type_vente }} @ {{ formatCurrency(detail.prix_unitaire) }}/{{ detail.type_vente }}</p>
                  <p v-if="detail.lot?.date_expiration" class="text-slate-500 text-[10px]">Exp: {{ new Date(detail.lot.date_expiration).toLocaleDateString() }}</p>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Cancel Modal -->
    <Teleport to="body">
      <div v-if="showCancelModal && selectedSale" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div class="bg-white w-full max-w-md rounded-lg shadow-lg border border-slate-200">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50 text-xs">
            <h2 class="text-sm font-semibold text-slate-900">Annuler Vente #{{ selectedSale.id }}</h2>
            <button @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600">
              <X class="w-4 h-4" />
            </button>
          </div>
          <div class="px-5 py-4 space-y-4 text-xs">
            <p class="text-sm text-slate-700">
              Voulez-vous vraiment annuler cette vente? Tous les articles seront remis en stock.
            </p>
            <div class="space-y-1">
              <label class="block text-[11px] text-slate-600">Motif d\'annulation</label>
              <textarea
                v-model="cancelMotif"
                rows="3"
                required
                class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-rose-500 focus:border-rose-500 resize-none"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2">
              <button @click="showCancelModal = false" type="button" class="px-3 py-2 text-[11px] text-slate-500 hover:text-slate-700">
                Annuler
              </button>
              <button
                @click="confirmCancelSale"
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded bg-rose-600 text-white text-[11px] font-semibold hover:bg-rose-700"
              >
                <X class="w-3.5 h-3.5" />
                Confirmer Annulation
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Return Modal -->
    <Teleport to="body">
      <div v-if="showReturnModal && selectedSale" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div class="bg-white w-full max-w-xl rounded-lg shadow-lg border border-slate-200 max-h-[90vh] overflow-auto">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50 text-xs">
            <h2 class="text-sm font-semibold text-slate-900">Retourner Articles Vente #{{ selectedSale.id }}</h2>
            <button @click="showReturnModal = false" class="text-slate-400 hover:text-slate-600">
              <X class="w-4 h-4" />
            </button>
          </div>
          <div class="px-5 py-4 space-y-4 text-xs">
            <p class="text-sm text-slate-700">
              Sélectionnez les articles et les quantités à retourner.
            </p>
            <div class="space-y-2">
              <div v-for="detail in selectedSale.details" :key="detail.id" class="flex items-center gap-3 border border-slate-100 p-2 rounded">
                <div class="flex-1">
                  <p class="font-semibold">{{ detail.medicament.nom }} (Lot: {{ detail.lot?.numero_lot || 'N/A' }})</p>
                  <p class="text-slate-500 text-[11px]">Vendu: {{ detail.quantite }} {{ detail.type_vente }}</p>
                </div>
                <div class="w-24">
                  <label class="block text-[10px] text-slate-500 mb-0.5">Qté à retourner</label>
                  <input
                    v-model.number="returnedItems.find(item => item.detail_vente_id === detail.id)!.quantite"
                    type="number"
                    min="0"
                    :max="detail.quantite"
                    class="w-full border border-slate-300 rounded px-2 py-1 text-xs outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>
            </div>
            <div class="space-y-1">
              <label class="block text-[11px] text-slate-600">Motif de retour</label>
              <textarea
                v-model="returnMotif"
                rows="3"
                required
                class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none"
              ></textarea>
            </div>
            <div class="flex justify-end gap-2">
              <button @click="showReturnModal = false" type="button" class="px-3 py-2 text-[11px] text-slate-500 hover:text-slate-700">
                Annuler
              </button>
              <button
                @click="confirmReturnPartial"
                type="button"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded bg-blue-600 text-white text-[11px] font-semibold hover:bg-blue-700"
              >
                <Undo2 class="w-3.5 h-3.5" />
                Confirmer Retour
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
