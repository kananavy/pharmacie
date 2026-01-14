<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from '../api/axios'
import { 
  Users, 
  Plus, 
  Search, 
  Trash2, 
  X,
  Save,
  Phone,
  Mail,
  MapPin,
  ChevronRight,
  Truck,
  Settings,
  History,
  Building2
} from 'lucide-vue-next'

interface Supplier {
  id: number;
  nom: string;
  email: string;
  telephone: string;
  adresse: string;
  created_at?: string;
}

const suppliers = ref<Supplier[]>([])
const loading = ref(true)
const searchQuery = ref('')
const showModal = ref(false)
const modalMode = ref('add')
const selectedSupplier = ref<Supplier | null>(null)

const form = ref<Partial<Supplier>>({
  id: undefined,
  nom: '',
  email: '',
  telephone: '',
  adresse: ''
})

onMounted(async () => {
  await fetchSuppliers()
})

const fetchSuppliers = async () => {
  loading.value = true
  try {
    const res = await axios.get('/fournisseurs')
    suppliers.value = res.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const filteredSuppliers = computed(() => {
  if (!searchQuery.value) return suppliers.value
  const q = searchQuery.value.toLowerCase()
  return suppliers.value.filter(s => 
    s.nom.toLowerCase().includes(q) || 
    s.email.toLowerCase().includes(q) ||
    s.adresse.toLowerCase().includes(q)
  )
})

const openAddModal = () => {
  modalMode.value = 'add'
  form.value = { id: undefined, nom: '', email: '', telephone: '', adresse: '' }
  showModal.value = true
}

const openEditModal = (supplier: Supplier) => {
  modalMode.value = 'edit'
  form.value = { ...supplier }
  showModal.value = true
}

const saveSupplier = async () => {
  try {
    if (modalMode.value === 'add') {
      await axios.post('/fournisseurs', form.value)
    } else {
      await axios.put(`/fournisseurs/${form.value.id}`, form.value)
    }
    await fetchSuppliers()
    showModal.value = false
  } catch (err) {
    alert('Erreur lors de l\'enregistrement')
  }
}

const deleteSupplier = async (id: number) => {
  if (!confirm('Voulez-vous vraiment supprimer ce fournisseur ?')) return
  try {
    await axios.delete(`/fournisseurs/${id}`)
    await fetchSuppliers()
    if (selectedSupplier.value?.id === id) selectedSupplier.value = null
  } catch (err) {
    alert('Erreur lors de la suppression')
  }
}

const selectSupplier = (supplier: Supplier) => {
  selectedSupplier.value = supplier
}
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <Truck class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Partenaires & Grossistes</h1>
          <p class="text-xs text-slate-500">Gestion du répertoire des fournisseurs et laboratoires.</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="openAddModal"
          class="inline-flex items-center gap-2 px-3 py-2 rounded border border-emerald-600 bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition-colors shadow-sm"
        >
          <Plus class="w-4 h-4" />
          Nouveau fournisseur
        </button>
      </div>
    </div>

    <!-- Zone principale -->
    <div class="flex-1 flex gap-4 min-h-0">
      <!-- Table des fournisseurs -->
      <section class="flex-1 flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden min-w-0">
        <!-- Barre de recherche -->
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center gap-3 text-xs">
          <div class="flex-1 flex items-center gap-2">
            <Search class="w-4 h-4 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par nom, email ou adresse..."
              class="w-full bg-transparent border-none outline-none text-xs text-slate-800"
            />
          </div>
          <div class="text-[11px] text-slate-400">
            {{ suppliers.length }} partenaires actifs
          </div>
        </div>

        <!-- Tableau -->
        <div class="flex-1 overflow-auto custom-scrollbar">
          <table class="w-full text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 sticky top-0 z-10">
              <tr>
                <th class="px-4 py-2 font-medium text-left">Fournisseur</th>
                <th class="px-4 py-2 font-medium text-left">Email</th>
                <th class="px-4 py-2 font-medium text-left">Téléphone</th>
                <th class="px-4 py-2 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" v-for="i in 6" :key="i" class="animate-pulse">
                <td colspan="4" class="px-4 py-3 border-b border-slate-100">
                  <div class="h-6 bg-slate-100 rounded"></div>
                </td>
              </tr>
              <tr
                v-else
                v-for="supplier in filteredSuppliers"
                :key="supplier.id"
                @click="selectSupplier(supplier)"
                :class="[
                  'border-b border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors',
                  selectedSupplier?.id === supplier.id ? 'bg-emerald-50/50' : ''
                ]"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded bg-slate-100 flex items-center justify-center text-[11px] font-bold text-slate-600">
                      {{ supplier.nom[0]?.toUpperCase() }}
                    </div>
                    <div class="font-semibold text-slate-900">{{ supplier.nom }}</div>
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ supplier.email }}</td>
                <td class="px-4 py-3 font-mono text-slate-600">{{ supplier.telephone || '-- -- -- -- --' }}</td>
                <td class="px-4 py-3 text-right">
                  <button
                    @click.stop="openEditModal(supplier)"
                    class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded transition-all"
                  >
                    <Settings class="w-4 h-4" />
                  </button>
                  <button
                    @click.stop="deleteSupplier(supplier.id)"
                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-all ml-1"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="!loading && !filteredSuppliers.length">
                <td colspan="4" class="px-4 py-12 text-center text-slate-400 italic">
                  Aucun fournisseur trouvé.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Panneau de détail -->
      <section class="w-[380px] flex flex-col bg-white border border-slate-200 rounded-md overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between text-xs">
          <span class="font-semibold text-slate-800 uppercase tracking-wider">
            Fiche Partenaire
          </span>
        </div>

        <div v-if="selectedSupplier" class="flex-1 overflow-auto px-5 py-6 space-y-6">
          <div class="flex flex-col items-center text-center space-y-3 pb-2">
            <div class="w-16 h-16 rounded-2xl bg-emerald-950 text-emerald-400 flex items-center justify-center text-2xl font-bold shadow-lg shadow-emerald-950/20">
              {{ selectedSupplier.nom[0]?.toUpperCase() }}
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 leading-tight">{{ selectedSupplier.nom }}</h2>
              <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Fournisseur Actif</p>
            </div>
          </div>

          <div class="space-y-4">
            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex items-start gap-3">
              <Mail class="w-4 h-4 text-slate-400 mt-0.5" />
              <div>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mb-1">Email</p>
                <p class="text-xs font-semibold text-slate-700">{{ selectedSupplier.email }}</p>
              </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex items-start gap-3">
              <Phone class="w-4 h-4 text-slate-400 mt-0.5" />
              <div>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mb-1">Téléphone</p>
                <p class="text-xs font-semibold text-slate-700 font-mono">{{ selectedSupplier.telephone || 'Non renseigné' }}</p>
              </div>
            </div>

            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 flex items-start gap-3">
              <MapPin class="w-4 h-4 text-slate-400 mt-0.5" />
              <div>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none mb-1">Adresse</p>
                <p class="text-xs font-semibold text-slate-700 leading-relaxed">{{ selectedSupplier.adresse || 'Aucune adresse renseignée' }}</p>
              </div>
            </div>
          </div>

          <div class="pt-4 space-y-2">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:border-emerald-500 hover:text-emerald-700 transition-all group">
              <span class="flex items-center gap-2">
                <History class="w-4 h-4 text-slate-400 group-hover:text-emerald-500" />
                Historique des commandes
              </span>
              <ChevronRight class="w-4 h-4 text-slate-300 group-hover:translate-x-1 transition-transform" />
            </button>
            <button class="w-full flex items-center justify-between px-4 py-3 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:border-emerald-500 hover:text-emerald-700 transition-all group">
              <span class="flex items-center gap-2">
                <Building2 class="w-4 h-4 text-slate-400 group-hover:text-emerald-500" />
                Produits associés
              </span>
              <ChevronRight class="w-4 h-4 text-slate-300 group-hover:translate-x-1 transition-transform" />
            </button>
          </div>
        </div>

        <div v-else class="flex-1 flex flex-col items-center justify-center px-8 text-center space-y-4">
          <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-300">
            <Users class="w-6 h-6" />
          </div>
          <p class="text-xs text-slate-400 font-medium">Sélectionnez un partenaire dans la liste pour consulter ses informations détaillées.</p>
        </div>
      </section>
    </div>

    <!-- Modal Formulaire -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
        <div class="bg-white w-full max-w-xl rounded-lg shadow-xl border border-slate-200 overflow-hidden animate-in zoom-in-95 duration-200">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
              <Building2 class="w-4 h-4 text-emerald-600" />
              {{ modalMode === 'add' ? 'Nouveau Partenaire' : 'Paramètres Fournisseur' }}
            </h2>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
              <X class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="saveSupplier" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1.5 md:col-span-2">
                <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider ml-1">Nom de l'Entité</label>
                <input
                  v-model="form.nom"
                  required
                  placeholder="ex: Laboratoire Pfizer"
                  class="w-full bg-white border border-slate-200 rounded-md px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all placeholder:text-slate-400"
                />
              </div>
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider ml-1">Téléphone</label>
                <input
                  v-model="form.telephone"
                  placeholder="ex: 01 23 45 67 89"
                  class="w-full bg-white border border-slate-200 rounded-md px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all font-mono placeholder:text-slate-400"
                />
              </div>
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider ml-1">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  placeholder="contact@fournisseur.fr"
                  class="w-full bg-white border border-slate-200 rounded-md px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all placeholder:text-slate-400"
                />
              </div>
              <div class="space-y-1.5 md:col-span-2">
                <label class="text-[11px] font-bold text-slate-600 uppercase tracking-wider ml-1">Adresse / Siège Social</label>
                <textarea
                  v-model="form.adresse"
                  rows="3"
                  placeholder="Adresse complète..."
                  class="w-full bg-white border border-slate-200 rounded-md px-3 py-2 text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all resize-none placeholder:text-slate-400"
                ></textarea>
              </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4">
              <button
                type="button"
                @click="showModal = false"
                class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 transition-colors"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="flex items-center gap-2 px-6 py-2 bg-emerald-600 text-white rounded-md text-xs font-bold hover:bg-emerald-700 transition-all shadow-md shadow-emerald-600/10 active:scale-95"
              >
                <Save class="w-4 h-4" />
                {{ modalMode === 'add' ? 'Enregistrer' : 'Mettre à jour' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
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
