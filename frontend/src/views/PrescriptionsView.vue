<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from '../api/axios'
import { 
  FileText,
  Search,
  Plus,
  UserCircle,
  Pill,
  Calendar,
  X,
  Save
} from 'lucide-vue-next'

interface Prescription {
  id: number
  numero: string
  patient_nom: string
  patient_id?: number
  medecin: string
  date_ordonnance: string
  statut: 'en_cours' | 'preparee' | 'livree'
}

const prescriptions = ref<Prescription[]>([])
const loading = ref(true)
const searchQuery = ref('')
const showModal = ref(false)
const modalMode = ref<'add' | 'edit'>('add')

const form = ref<Partial<Prescription>>({
  id: undefined,
  numero: '',
  patient_nom: '',
  medecin: '',
  date_ordonnance: new Date().toISOString().split('T')[0],
  statut: 'en_cours'
})

onMounted(async () => {
  await fetchPrescriptions()
})

const fetchPrescriptions = async () => {
  loading.value = true
  try {
    const res = await axios.get('/ordonnances')
    prescriptions.value = res.data
  } catch {
    console.warn('Ordonnances API not ready, using empty list')
    prescriptions.value = []
  } finally {
    loading.value = false
  }
}

const filteredPrescriptions = computed(() => {
  if (!searchQuery.value) return prescriptions.value
  const q = searchQuery.value.toLowerCase()
  return prescriptions.value.filter(p =>
    p.numero.toLowerCase().includes(q) ||
    p.patient_nom.toLowerCase().includes(q) ||
    p.medecin.toLowerCase().includes(q)
  )
})

const openAddModal = () => {
  modalMode.value = 'add'
  form.value = {
    id: undefined,
    numero: '',
    patient_nom: '',
    medecin: '',
    date_ordonnance: new Date().toISOString().split('T')[0],
    statut: 'en_cours'
  }
  showModal.value = true
}

const openEditModal = (row: Prescription) => {
  modalMode.value = 'edit'
  form.value = { ...row }
  showModal.value = true
}

const savePrescription = async () => {
  try {
    if (modalMode.value === 'add') {
      await axios.post('/ordonnances', form.value)
    } else {
      await axios.put(`/ordonnances/${form.value.id}`, form.value)
    }
    await fetchPrescriptions()
    showModal.value = false
  } catch {
    alert('Erreur lors de l’enregistrement de l’ordonnance')
  }
}

const statutLabel = (s: Prescription['statut']) => {
  if (s === 'preparee') return 'Préparée'
  if (s === 'livree') return 'Livrée'
  return 'En cours'
}
</script>

<template>
  <div class="space-y-8 h-[calc(100vh-140px)] flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
          <FileText class="w-6 h-6" />
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Ordonnances</h1>
          <p class="text-xs text-slate-500">Suivi des ordonnances à préparer et livrer.</p>
        </div>
      </div>
      <button
        @click="openAddModal"
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-md bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700"
      >
        <Plus class="w-4 h-4" />
        Nouvelle ordonnance
      </button>
    </div>

    <!-- Outils -->
    <div class="flex items-center gap-4 bg-white border border-slate-200 rounded-md px-4 py-3">
      <div class="flex-1 flex items-center gap-2 text-xs text-slate-500">
        <Search class="w-4 h-4 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher (N° ordonnance, patient, médecin...)"
          class="w-full border-none outline-none text-xs text-slate-800"
        />
      </div>
      <div class="text-[11px] text-slate-400">
        {{ prescriptions.length }} ordonnance(s)
      </div>
    </div>

    <!-- Tableau -->
    <div class="flex-1 bg-white border border-slate-200 rounded-md overflow-hidden flex flex-col">
      <div class="border-b border-slate-200 bg-slate-50/80">
        <table class="w-full text-xs text-left">
          <thead>
            <tr class="text-slate-500">
              <th class="px-4 py-3 font-medium">N°</th>
              <th class="px-4 py-3 font-medium">Patient</th>
              <th class="px-4 py-3 font-medium">Médecin</th>
              <th class="px-4 py-3 font-medium">Date</th>
              <th class="px-4 py-3 font-medium">Statut</th>
              <th class="px-4 py-3 font-medium text-right">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
      <div class="flex-1 overflow-auto">
        <table class="w-full text-xs text-left">
          <tbody v-if="loading">
            <tr v-for="i in 6" :key="i" class="animate-pulse">
              <td colspan="6" class="px-4 py-3">
                <div class="h-7 bg-slate-100 rounded"></div>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr
              v-for="row in filteredPrescriptions"
              :key="row.id"
              class="border-b border-slate-100 hover:bg-slate-50"
            >
              <td class="px-4 py-3 font-mono text-[11px] text-slate-700">
                {{ row.numero || '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-1.5">
                  <UserCircle class="w-3.5 h-3.5 text-slate-400" />
                  <span class="text-xs font-medium text-slate-900">
                    {{ row.patient_nom }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600">
                {{ row.medecin || '—' }}
              </td>
              <td class="px-4 py-3 text-slate-600">
                <div class="flex items-center gap-1.5">
                  <Calendar class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ row.date_ordonnance }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold border',
                    row.statut === 'livree'
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                      : row.statut === 'preparee'
                      ? 'bg-blue-50 text-blue-700 border-blue-200'
                      : 'bg-amber-50 text-amber-700 border-amber-200'
                  ]"
                >
                  <span class="w-1.5 h-1.5 rounded-full mr-1.5"
                    :class="row.statut === 'livree' ? 'bg-emerald-500' : row.statut === 'preparee' ? 'bg-blue-500' : 'bg-amber-500'"
                  />
                  {{ statutLabel(row.statut) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  @click="openEditModal(row)"
                  class="inline-flex items-center gap-1.5 px-2 py-1 text-[11px] text-emerald-700 border border-emerald-200 rounded hover:bg-emerald-50"
                >
                  <Pill class="w-3.5 h-3.5" />
                  Détail
                </button>
              </td>
            </tr>
            <tr v-if="!filteredPrescriptions.length && !loading">
              <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-400">
                Aucune ordonnance enregistrée pour le moment.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Ordonnance -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
      >
        <div class="bg-white w-full max-w-xl rounded-lg shadow-lg border border-slate-200">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2">
              <FileText class="w-4 h-4 text-slate-700" />
              <h2 class="text-sm font-semibold text-slate-900">
                {{ modalMode === 'add' ? 'Nouvelle ordonnance' : 'Modifier ordonnance' }}
              </h2>
            </div>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
              <X class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="savePrescription" class="px-5 py-4 space-y-4 text-xs">
            <div class="space-y-1">
              <label class="block text-[11px] text-slate-600">Numéro d’ordonnance</label>
              <input
                v-model="form.numero"
                required
                class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 font-mono"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Nom du patient</label>
                <input
                  v-model="form.patient_nom"
                  required
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Médecin prescripteur</label>
                <input
                  v-model="form.medecin"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Date ordonnance</label>
                <input
                  v-model="form.date_ordonnance"
                  type="date"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Statut</label>
                <select
                  v-model="form.statut"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white"
                >
                  <option value="en_cours">En cours</option>
                  <option value="preparee">Préparée</option>
                  <option value="livree">Livrée</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
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
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

