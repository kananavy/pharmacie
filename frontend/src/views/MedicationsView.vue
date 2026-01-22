<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from '../api/axios'
import { formatCurrency } from '../utils/formatting'
import { 
  Pill, 
  Plus, 
  Search, 
  Filter, 
  X,
  History,
  TrendingDown,
  Package,
  MapPin,
  Truck,
  ArrowUpRight,
  ArrowDownRight,
  Settings,
  ClipboardList
} from 'lucide-vue-next'

interface Mouvement {
  id: number;
  quantite: number;
  type: string;
  motif: string;
  created_at: string;
  user?: { name: string };
}

interface Lot {
  id: number;
  numero_lot: string;
  quantite_actuelle: number;
  date_expiration: string;
  prix_achat: number;
  fournisseur?: { nom: string };
}

interface Medicament {
  id: number;
  nom: string;
  code: string;
  categorie: string;
  unite_emballage: string;
  quantite_par_emballage: number;
  prix: number;
  prix_achat: number;
  ordonnance_requise: boolean;
  seuil_alerte: number;
  max_stock: number;
  emplacement: string;
  fournisseur_id: number | string;
  fournisseur?: { nom: string };
  mouvements?: Mouvement[];
  unites_par_boite?: number;
  unite_stock?: string;
  prix_unitaire?: number;
  // New properties from backend
  lots_sum_quantite_actuelle: number;
  lots?: Lot[];
}

const medications = ref<Medicament[]>([])
const suppliers = ref<any[]>([])
const loading = ref(true)
const searchQuery = ref('')
const activeTab = ref('inventory') // 'inventory', 'details', 'history'
const selectedMed = ref<Medicament | null>(null)
const showModal = ref(false)
const modalMode = ref('add')

const form = ref<Partial<Medicament>>({
  id: undefined,
  nom: '',
  code: '',
  categorie: '',
  unite_emballage: 'Boîte',
  quantite_par_emballage: 1,
  prix: 0,
  prix_achat: 0,
  ordonnance_requise: false,
  seuil_alerte: 10,
  max_stock: 100,
  emplacement: '',
  fournisseur_id: '',
  unites_par_boite: 1,
  unite_stock: 'boite',
  prix_unitaire: 0
})

const categories = ['Antibiotique', 'Analgésique', 'Vitamines', 'Dermatologie', 'Cardiologie', 'Diabète', 'Gastro-entérologie']

onMounted(async () => {
  await Promise.all([fetchMedications(), fetchSuppliers()])
})

const fetchMedications = async () => {
  loading.value = true
  try {
    const res = await axios.get('/medicaments')
    medications.value = res.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const fetchSuppliers = async () => {
  try {
    const res = await axios.get('/fournisseurs')
    suppliers.value = res.data
  } catch (err) {
    console.error(err)
  }
}

const filteredMedications = computed(() => {
  if (!searchQuery.value) return medications.value
  const q = searchQuery.value.toLowerCase()
  return medications.value.filter(m => 
    m.nom.toLowerCase().includes(q) || 
    (m.code && m.code.toLowerCase().includes(q)) ||
    (m.categorie && m.categorie.toLowerCase().includes(q))
  )
})

const openAddModal = () => {
  modalMode.value = 'add'
  form.value = {
    nom: '', code: '', categorie: '', unite_emballage: 'Boîte',
    quantite_par_emballage: 1, prix: 0, prix_achat: 0,
    ordonnance_requise: false, seuil_alerte: 10,
    max_stock: 100, emplacement: '', fournisseur_id: ''
  }
  showModal.value = true
}

const openEditModal = (med: Medicament) => {
  modalMode.value = 'edit'
  form.value = { ...med }
  showModal.value = true
}

const saveMedication = async () => {
  try {
    if (modalMode.value === 'add') {
      await axios.post('/medicaments', form.value)
    } else {
      await axios.put(`/medicaments/${form.value.id}`, form.value)
    }
    await fetchMedications()
    showModal.value = false
  } catch (err) {
    alert('Erreur lors de l\'enregistrement')
    console.error(err);
  }
}

const deleteMedication = async (id: number) => {
  if (!confirm('Voulez-vous vraiment supprimer ce médicament ?')) return
  try {
    await axios.delete(`/medicaments/${id}`)
    await fetchMedications()
    if (selectedMed.value?.id === id) selectedMed.value = null
  } catch (err) {
    alert('Erreur lors de la suppression')
  }
}

const selectMed = async (med: Medicament) => {
  try {
    const res = await axios.get(`/medicaments/${med.id}`)
    selectedMed.value = res.data
    activeTab.value = 'details'
  } catch (err) {
    alert('Erreur lors de la récupération des détails du médicament.')
    console.error(err)
  }
}

const formatPrice = (price: number) => formatCurrency(price)
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <Pill class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Médicaments & stock</h1>
          <p class="text-xs text-slate-500">Gestion du référentiel produits et des niveaux de stock.</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="openAddModal"
          class="inline-flex items-center gap-2 px-3 py-2 rounded border border-emerald-600 bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700"
        >
          <Plus class="w-4 h-4" />
          Nouveau produit
        </button>
      </div>
    </div>

    <!-- Zone principale -->
    <div class="flex-1 flex gap-4 min-h-0">
      <!-- Liste / table -->
      <section class="flex-1 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden min-w-0">
        <!-- Barre outils -->
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center gap-3 text-xs">
          <div class="flex-1 flex items-center gap-2">
            <Search class="w-4 h-4 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Recherche (CIP, nom, DCI, catégorie...)"
              class="w-full bg-transparent border-none outline-none text-xs text-slate-800"
            />
          </div>
          <div class="text-[11px] text-slate-400">
            {{ medications.length }} produits
          </div>
        </div>

        <!-- Tableau -->
        <div class="flex-1 overflow-auto">
          <table class="w-full text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
              <tr>
                <th class="px-3 py-2 font-medium text-left">Produit</th>
                <th class="px-3 py-2 font-medium text-left">Catégorie</th>
                <th class="px-3 py-2 font-medium text-center">Stock</th>
                <th class="px-3 py-2 font-medium text-left">Seuil / Max</th>
                <th class="px-3 py-2 font-medium text-left">Prix vente</th>
                <th class="px-3 py-2 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" v-for="i in 6" :key="i" class="animate-pulse">
                <td colspan="6" class="px-3 py-3">
                  <div class="h-6 bg-slate-100 rounded"></div>
                </td>
              </tr>
              <tr
                v-else
                v-for="med in filteredMedications"
                :key="med.id"
                @click="selectMed(med)"
                class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
              >
                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-[11px] text-slate-600">
                      {{ med.nom[0]?.toUpperCase() }}
                    </div>
                    <div>
                      <div class="text-[11px] font-semibold text-slate-900">
                        {{ med.nom }}
                      </div>
                      <div class="text-[10px] text-slate-400 font-mono">
                        {{ med.code }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-2 text-[11px] text-slate-600">
                  {{ med.categorie }}
                </td>
                <td class="px-3 py-2 text-center">
                  <span
                    :class="[
                      'inline-flex items-center justify-center min-w-[40px] px-2 py-0.5 rounded-full text-[10px] font-semibold',
                      med.lots_sum_quantite_actuelle <= med.seuil_alerte
                        ? 'bg-rose-50 text-rose-600'
                        : 'bg-emerald-50 text-emerald-700'
                    ]"
                  >
                    {{ med.lots_sum_quantite_actuelle }}
                  </span>
                </td>
                <td class="px-3 py-2 text-[11px] text-slate-600 font-mono">
                  {{ med.seuil_alerte }} / {{ med.max_stock }}
                </td>
                <td class="px-3 py-2 text-[11px] text-slate-800 font-semibold">
                  {{ formatPrice(med.prix) }}
                </td>
                <td class="px-3 py-2 text-right">
                  <button
                    @click.stop="openEditModal(med)"
                    class="inline-flex items-center gap-1.5 px-2 py-1 text-[11px] text-slate-700 border border-slate-300 rounded hover:bg-slate-50"
                  >
                    <Settings class="w-3.5 h-3.5" />
                    Éditer
                  </button>
                  <button
                    @click.stop="deleteMedication(med.id)"
                    class="ml-2 text-slate-400 hover:text-rose-500"
                  >
                    <X class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && !filteredMedications.length">
                <td colspan="6" class="px-3 py-6 text-center text-[11px] text-slate-400">
                  Aucun médicament trouvé.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Panneau de droite -->
      <section class="w-[420px] flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between text-xs">
          <span class="font-semibold text-slate-800">
            {{ selectedMed ? 'Fiche produit' : 'Aucune sélection' }}
          </span>
        </div>

        <div v-if="selectedMed" class="flex-1 overflow-auto px-4 py-4 space-y-4 text-xs">
          <!-- Infos principales -->
          <div class="border border-slate-200 rounded p-3 space-y-2">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <Pill class="w-4 h-4" />
              </div>
              <div>
                <div class="text-[12px] font-semibold text-slate-900">
                  {{ selectedMed.nom }}
                </div>
                <div class="text-[10px] text-slate-400 font-mono">
                  {{ selectedMed.code }}
                </div>
              </div>
            </div>
              <div class="flex items-center justify-between text-[11px] text-slate-600">
                <span>Valeur stock</span>
                <span class="font-mono font-semibold text-slate-800">
                  {{ formatPrice(selectedMed.lots_sum_quantite_actuelle * selectedMed.prix) }}
                </span>
              </div>
          </div>
        </div>

        <div v-else class="flex-1 flex items-center justify-center text-[11px] text-slate-400">
          Sélectionnez un médicament dans la liste pour afficher sa fiche.
        </div>
      </section>
    </div>

    <!-- Modal édition / création -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
      >
        <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg border border-slate-200 max-h-[90vh] overflow-auto">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50 text-xs">
            <div class="flex items-center gap-2">
              <Pill class="w-4 h-4 text-emerald-600" />
              <h2 class="text-sm font-semibold text-slate-900">
                {{ modalMode === 'add' ? 'Nouveau médicament' : 'Modifier médicament' }}
              </h2>
            </div>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
              <X class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="saveMedication" class="px-5 py-4 space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Nom du produit</label>
                <input
                  v-model="form.nom"
                  required
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Code CIP</label>
                <input
                  v-model="form.code"
                  required
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 font-mono"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Catégorie</label>
                <select
                  v-model="form.categorie"
                  required
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white"
                >
                  <option v-for="cat in categories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Emplacement</label>
                <input
                  v-model="form.emplacement"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Seuil alerte</label>
                <input
                  v-model.number="form.seuil_alerte"
                  type="number"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Stock max</label>
                <input
                  v-model.number="form.max_stock"
                  type="number"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Prix d’achat (pkg)</label>
                <input
                  v-model.number="form.prix_achat"
                  type="number"
                  step="0.01"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Prix vente (unit.)</label>
                <input
                  v-model.number="form.prix"
                  type="number"
                  step="0.01"
                  required
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 pt-3 mt-1">
              <div class="space-y-1">
                 <label class="block text-[11px] text-slate-600 font-bold">Unités par Boîte</label>
                 <input
                   v-model.number="form.unites_par_boite"
                   type="number"
                   min="1"
                   class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                   placeholder="Ex: 28 (comprimés)"
                 />
              </div>
              <div class="space-y-1">
                 <label class="block text-[11px] text-slate-600 font-bold">Prix Unitaire (Détail)</label>
                 <input
                   v-model.number="form.prix_unitaire"
                   type="number"
                   step="0.01"
                   class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                   placeholder="Ex: 500 Ar / plaquette"
                 />
              </div>
            </div>



            <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-100">
              <div class="flex items-center gap-2 text-[11px] text-slate-600">
                <input
                  id="ordonnance"
                  type="checkbox"
                  v-model="form.ordonnance_requise"
                  class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                />
                <label for="ordonnance">Ordonnance obligatoire</label>
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  @click="showModal = false"
                  class="px-3 py-2 text-[11px] text-slate-500 hover:text-slate-700"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded bg-emerald-600 text-white text-[11px] font-semibold hover:bg-emerald-700"
                >
                  <Save class="w-3.5 h-3.5" />
                  Enregistrer
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1fae5; border-radius: 10px; }

.custom-scrollbar-dark::-webkit-scrollbar { width: 4px; }
.custom-scrollbar-dark::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar-dark::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); border-radius: 10px; }

.shadow-4xl {
  box-shadow: 0 50px 100px -20px rgba(6, 78, 59, 0.25), 0 30px 60px -30px rgba(0, 0, 0, 0.3);
}

input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(15%) sepia(35%) saturate(3000%) hue-rotate(140deg) brightness(95%) contrast(85%);
  cursor: pointer;
}
</style>
