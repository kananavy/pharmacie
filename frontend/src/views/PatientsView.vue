<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from '../api/axios'
import { 
  UserCircle, 
  Plus, 
  Search, 
  Calendar,
  Phone,
  MapPin,
  IdCard,
  X,
  Save
} from 'lucide-vue-next'

interface Patient {
  id: number
  nom: string
  prenom: string
  date_naissance: string
  telephone: string
  adresse: string
  numero_dossier: string
}

const patients = ref<Patient[]>([])
const loading = ref(true)
const searchQuery = ref('')
const showModal = ref(false)
const modalMode = ref<'add' | 'edit'>('add')

const form = ref<Partial<Patient>>({
  id: undefined,
  nom: '',
  prenom: '',
  date_naissance: '',
  telephone: '',
  adresse: '',
  numero_dossier: ''
})

onMounted(async () => {
  await fetchPatients()
})

const fetchPatients = async () => {
  loading.value = true
  try {
    const res = await axios.get('/patients')
    patients.value = res.data
  } catch (err) {
    // Back non prêt : on tolère l'erreur pour l’instant
    console.warn('Patients API not ready, using empty list')
    patients.value = []
  } finally {
    loading.value = false
  }
}

const filteredPatients = computed(() => {
  if (!searchQuery.value) return patients.value
  const q = searchQuery.value.toLowerCase()
  return patients.value.filter(p =>
    p.nom.toLowerCase().includes(q) ||
    p.prenom.toLowerCase().includes(q) ||
    p.numero_dossier.toLowerCase().includes(q)
  )
})

const openAddModal = () => {
  modalMode.value = 'add'
  form.value = {
    id: undefined,
    nom: '',
    prenom: '',
    date_naissance: '',
    telephone: '',
    adresse: '',
    numero_dossier: ''
  }
  showModal.value = true
}

const openEditModal = (patient: Patient) => {
  modalMode.value = 'edit'
  form.value = { ...patient }
  showModal.value = true
}

const savePatient = async () => {
  try {
    if (modalMode.value === 'add') {
      await axios.post('/patients', form.value)
    } else {
      await axios.put(`/patients/${form.value.id}`, form.value)
    }
    await fetchPatients()
    showModal.value = false
  } catch (err) {
    alert('Erreur lors de l’enregistrement du patient')
  }
}
</script>

<template>
  <div class="space-y-8 h-[calc(100vh-140px)] flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <UserCircle class="w-6 h-6" />
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Patients</h1>
          <p class="text-xs text-slate-500">Dossier patients et historique des ordonnances.</p>
        </div>
      </div>
      <button
        @click="openAddModal"
        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-md bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700"
      >
        <Plus class="w-4 h-4" />
        Nouveau patient
      </button>
    </div>

    <!-- Barre outils -->
    <div class="flex items-center gap-4 bg-white border border-slate-200 rounded-md px-4 py-3">
      <div class="flex-1 flex items-center gap-2 text-xs text-slate-500">
        <Search class="w-4 h-4 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Rechercher (nom, prénom, numéro de dossier...)"
          class="w-full border-none outline-none text-xs text-slate-800"
        />
      </div>
      <div class="text-[11px] text-slate-400">
        {{ patients.length }} dossier(s)
      </div>
    </div>

    <!-- Tableau -->
    <div class="flex-1 bg-white border border-slate-200 rounded-md overflow-hidden flex flex-col">
      <div class="border-b border-slate-200 bg-slate-50/80">
        <table class="w-full text-xs text-left">
          <thead>
            <tr class="text-slate-500">
              <th class="px-4 py-3 font-medium">Dossier</th>
              <th class="px-4 py-3 font-medium">Nom / Prénom</th>
              <th class="px-4 py-3 font-medium">Date de naissance</th>
              <th class="px-4 py-3 font-medium">Téléphone</th>
              <th class="px-4 py-3 font-medium">Adresse</th>
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
              v-for="patient in filteredPatients"
              :key="patient.id"
              class="border-b border-slate-100 hover:bg-slate-50"
            >
              <td class="px-4 py-3 font-mono text-[11px] text-slate-700">
                {{ patient.numero_dossier || '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900 text-xs">
                  {{ patient.nom }} {{ patient.prenom }}
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600">
                <div class="flex items-center gap-1.5">
                  <Calendar class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ patient.date_naissance }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600">
                <div class="flex items-center gap-1.5">
                  <Phone class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ patient.telephone || '—' }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-600">
                <div class="flex items-center gap-1.5">
                  <MapPin class="w-3.5 h-3.5 text-slate-400" />
                  <span class="truncate max-w-[220px]">{{ patient.adresse || '—' }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  @click="openEditModal(patient)"
                  class="inline-flex items-center gap-1.5 px-2 py-1 text-[11px] text-emerald-700 border border-emerald-200 rounded hover:bg-emerald-50"
                >
                  <IdCard class="w-3.5 h-3.5" />
                  Fiche
                </button>
              </td>
            </tr>
            <tr v-if="!filteredPatients.length && !loading">
              <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-400">
                Aucun patient enregistré pour le moment.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Patient -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
      >
        <div class="bg-white w-full max-w-xl rounded-lg shadow-lg border border-slate-200">
          <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2">
              <UserCircle class="w-4 h-4 text-emerald-600" />
              <h2 class="text-sm font-semibold text-slate-900">
                {{ modalMode === 'add' ? 'Nouveau patient' : 'Modifier patient' }}
              </h2>
            </div>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
              <X class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="savePatient" class="px-5 py-4 space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Nom</label>
                <input
                  v-model="form.nom"
                  required
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Prénom</label>
                <input
                  v-model="form.prenom"
                  required
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Date de naissance</label>
                <input
                  v-model="form.date_naissance"
                  type="date"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[11px] text-slate-600">Numéro de dossier</label>
                <input
                  v-model="form.numero_dossier"
                  class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 font-mono"
                />
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-[11px] text-slate-600">Téléphone</label>
              <input
                v-model="form.telephone"
                class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
              />
            </div>

            <div class="space-y-1">
              <label class="block text-[11px] text-slate-600">Adresse</label>
              <textarea
                v-model="form.adresse"
                rows="2"
                class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 resize-none"
              ></textarea>
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

