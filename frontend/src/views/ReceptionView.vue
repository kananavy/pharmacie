<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from '../api/axios'
import { Truck, Plus, Save, PackagePlus } from 'lucide-vue-next'

interface Medicament {
  id: number;
  nom: string;
  code: string;
}

interface Fournisseur {
  id: number;
  nom: string;
}

const medicaments = ref<Medicament[]>([])
const fournisseurs = ref<Fournisseur[]>([])
const loading = ref(true)

const form = ref({
  medicament_id: '',
  fournisseur_id: '',
  numero_lot: '',
  quantite: 1,
  prix_achat: 0,
  date_expiration: '',
})

onMounted(async () => {
  await Promise.all([fetchMedications(), fetchFournisseurs()])
  loading.value = false
})

const fetchMedications = async () => {
  try {
    const res = await axios.get('/medicaments')
    medicaments.value = res.data
  } catch (err) {
    console.error('Erreur lors de la récupération des médicaments:', err)
  }
}

const fetchFournisseurs = async () => {
  try {
    const res = await axios.get('/fournisseurs')
    fournisseurs.value = res.data
  } catch (err) {
    console.error('Erreur lors de la récupération des fournisseurs:', err)
  }
}

const submitReception = async () => {
  if (!form.value.medicament_id || !form.value.numero_lot || !form.value.date_expiration) {
    alert('Veuillez remplir tous les champs obligatoires.')
    return
  }
  try {
    await axios.post('/lots', form.value)
    alert('Lot enregistré avec succès!')
    // Reset form
    form.value = {
      medicament_id: '',
      fournisseur_id: '',
      numero_lot: '',
      quantite: 1,
      prix_achat: 0,
      date_expiration: '',
    }
  } catch (err: any) {
    alert('Erreur lors de l\'enregistrement du lot.')
    console.error(err)
  }
}
</script>

<template>
  <div class="h-[calc(100vh-140px)] flex flex-col gap-4">
    <!-- En-tête -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded bg-blue-100 text-blue-700 flex items-center justify-center">
          <PackagePlus class="w-4 h-4" />
        </div>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Réception de stock</h1>
          <p class="text-xs text-slate-500">Enregistrer l\'entrée de nouveaux lots de médicaments.</p>
        </div>
      </div>
    </div>

    <!-- Formulaire -->
    <div class="bg-white border border-slate-200 rounded-md p-6 max-w-4xl mx-auto w-full">
      <form @submit.prevent="submitReception" class="space-y-6 text-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Médicament</label>
            <select
              v-model="form.medicament_id"
              required
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white"
            >
              <option disabled value="">Sélectionnez un médicament</option>
              <option v-for="med in medicaments" :key="med.id" :value="med.id">
                {{ med.nom }} ({{ med.code }})
              </option>
            </select>
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Fournisseur</label>
             <select
              v-model="form.fournisseur_id"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-white"
            >
              <option value="">Aucun / Inconnu</option>
              <option v-for="f in fournisseurs" :key="f.id" :value="f.id">
                {{ f.nom }}
              </option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Numéro de Lot</label>
            <input
              v-model="form.numero_lot"
              required
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 font-mono"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Date d’expiration</label>
            <input
              v-model="form.date_expiration"
              type="date"
              required
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Quantité reçue (en boîtes)</label>
            <input
              v-model.number="form.quantite"
              type="number"
              min="1"
              required
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
        </div>
        
        <div class="space-y-1">
          <label class="block text-[11px] text-slate-600">Prix d’achat (par boîte)</label>
          <input
            v-model.number="form.prix_achat"
            type="number"
            step="0.01"
            min="0"
            required
            class="w-full max-w-xs border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
          />
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700"
          >
            <Save class="w-4 h-4" />
            Enregistrer la réception
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
