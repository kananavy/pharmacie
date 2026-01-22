<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Settings2, Globe2, Percent, ShieldCheck, Upload, X } from 'lucide-vue-next'
import axios from '../api/axios'

interface SettingsData {
  pharmacy_name?: string;
  pharmacy_address?: string;
  pharmacy_phone?: string;
  pharmacy_email?: string;
  pharmacy_tax_id?: string;
  currency_symbol?: string;
  app_logo?: string | null; // URL of the logo
}

const settings = ref<SettingsData>({})
const loading = ref(true)
const saving = ref(false)
const selectedLogoFile = ref<File | null>(null)
const logoPreviewUrl = ref<string | null>(null)

onMounted(async () => {
  await fetchSettings()
})

const fetchSettings = async () => {
  loading.value = true
  try {
    const res = await axios.get('/settings')
    settings.value = res.data
    if (settings.value.app_logo) {
      logoPreviewUrl.value = import.meta.env.VITE_APP_URL + '/storage/' + settings.value.app_logo
    } else {
      logoPreviewUrl.value = null
    }
  } catch (err) {
    console.error('Erreur lors de la récupération des paramètres:', err)
    alert('Erreur lors de la récupération des paramètres.')
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  try {
    const formData = new FormData()

    for (const key in settings.value) {
      // Only append if value is not null and not app_logo itself
      if (settings.value[key as keyof SettingsData] !== null && key !== 'app_logo') {
        formData.append(key, String(settings.value[key as keyof SettingsData]));
      }
    }

    if (selectedLogoFile.value) {
      formData.append('app_logo', selectedLogoFile.value);
    } else if (!settings.value.app_logo && logoPreviewUrl.value === null) {
      // If no file selected AND no existing logo AND no preview (meaning it was removed)
      formData.append('remove_logo', 'true');
    }

    await axios.post('/settings', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    alert('Paramètres enregistrés avec succès!')
    selectedLogoFile.value = null // Clear selected file after upload
    await fetchSettings() // Re-fetch to update preview and ensure data is fresh
  } catch (err) {
    console.error('Erreur lors de l\'enregistrement des paramètres:', err)
    alert('Erreur lors de l\'enregistrement des paramètres.')
  } finally {
    saving.value = false
  }
}

const handleLogoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedLogoFile.value = target.files[0]
    logoPreviewUrl.value = URL.createObjectURL(target.files[0])
  } else {
    selectedLogoFile.value = null
    if (!settings.value.app_logo) {
      // Only clear preview if there was no existing logo
      logoPreviewUrl.value = null;
    }
  }
}

const removeLogo = () => {
  selectedLogoFile.value = null;
  logoPreviewUrl.value = null;
  // This will trigger the backend to delete the logo on saveSettings
}
</script>

<template>
  <div class="space-y-8 max-w-4xl">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center">
        <Settings2 class="w-6 h-6" />
      </div>
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Paramètres</h1>
        <p class="text-xs text-slate-500">Réglages généraux de la pharmacie et fiscalité.</p>
      </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Bloc Informations Pharmacie -->
      <section class="bg-white border border-slate-200 rounded-md p-4 space-y-3 text-xs">
        <div class="flex items-center gap-2">
          <Globe2 class="w-4 h-4 text-slate-500" />
          <h2 class="font-semibold text-slate-800">Informations Pharmacie</h2>
        </div>

        <div class="space-y-2">
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Nom de la pharmacie</label>
            <input
              v-model="settings.pharmacy_name"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Adresse</label>
            <input
              v-model="settings.pharmacy_address"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Téléphone</label>
            <input
              v-model="settings.pharmacy_phone"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">E-mail</label>
            <input
              v-model="settings.pharmacy_email"
              type="email"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Identifiant fiscal (NIF/STAT)</label>
            <input
              v-model="settings.pharmacy_tax_id"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Symbole Monétaire</label>
            <input
              v-model="settings.currency_symbol"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 font-mono"
            />
          </div>
        </div>
      </section>

      <!-- Bloc Logo -->
      <section class="bg-white border border-slate-200 rounded-md p-4 space-y-3 text-xs">
        <div class="flex items-center gap-2">
          <Upload class="w-4 h-4 text-slate-500" />
          <h2 class="font-semibold text-slate-800">Logo de l'officine</h2>
        </div>

        <div class="space-y-2">
          <div v-if="logoPreviewUrl" class="mb-3">
            <label class="block text-[11px] text-slate-600 mb-1">Logo actuel</label>
            <div class="flex items-center gap-2">
                <img :src="logoPreviewUrl" alt="Logo Pharmacie" class="h-20 w-auto object-contain border border-slate-200 rounded-md p-1">
                <button @click="removeLogo" type="button" class="text-rose-500 hover:text-rose-700 text-xs flex items-center gap-1">
                    <X class="w-3 h-3"/> Supprimer
                </button>
            </div>
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Charger un nouveau logo</label>
            <input
              type="file"
              @change="handleLogoChange"
              accept="image/*"
              class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
            />
            <p class="text-[10px] text-slate-400">
                Formats acceptés: JPG, PNG, GIF, SVG. Max 2MB.
            </p>
          </div>
        </div>
      </section>
    </div>

    <div class="flex justify-end pt-4 border-t border-slate-200">
      <button
        @click="saveSettings"
        :disabled="saving"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <Save class="w-4 h-4" />
        {{ saving ? 'Enregistrement...' : 'Enregistrer les paramètres' }}
      </button>
    </div>
  </div>
</template>

