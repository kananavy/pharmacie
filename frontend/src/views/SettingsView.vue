<script setup lang="ts">
import { ref } from 'vue'
import { Settings2, Globe2, Percent, ShieldCheck } from 'lucide-vue-next'

const general = ref({
  nom_pharmacie: 'Pharmacie',
  ville: 'Antananarivo',
  pays: 'Madagascar',
  devise: 'MGA'
})

const fiscal = ref({
  tva: 20,
  mention_legal_ticket: 'TVA incluse – Madagascar',
})
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
      <!-- Bloc identité -->
      <section class="bg-white border border-slate-200 rounded-md p-4 space-y-3 text-xs">
        <div class="flex items-center gap-2">
          <Globe2 class="w-4 h-4 text-slate-500" />
          <h2 class="font-semibold text-slate-800">Identité officine</h2>
        </div>

        <div class="space-y-2">
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Nom de la pharmacie</label>
            <input
              v-model="general.nom_pharmacie"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Ville</label>
            <input
              v-model="general.ville"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Pays</label>
            <input
              v-model="general.pays"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Devise</label>
            <input
              v-model="general.devise"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 font-mono"
            />
          </div>
        </div>
      </section>

      <!-- Bloc fiscal -->
      <section class="bg-white border border-slate-200 rounded-md p-4 space-y-3 text-xs">
        <div class="flex items-center gap-2">
          <Percent class="w-4 h-4 text-slate-500" />
          <h2 class="font-semibold text-slate-800">Paramètres fiscaux</h2>
        </div>

        <div class="space-y-2">
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Taux TVA (%)</label>
            <input
              v-model.number="fiscal.tva"
              type="number"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
            />
          </div>
          <div class="space-y-1">
            <label class="block text-[11px] text-slate-600">Mention légale sur ticket</label>
            <textarea
              v-model="fiscal.mention_legal_ticket"
              rows="3"
              class="w-full border border-slate-300 rounded px-2.5 py-2 text-xs outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 resize-none"
            ></textarea>
          </div>
          <div class="flex items-center gap-2 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-100 rounded px-2.5 py-2">
            <ShieldCheck class="w-3.5 h-3.5" />
            <p>
              Ces réglages ne sont pour l’instant **que visuels**. On pourra les relier au back plus tard.
            </p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

