<!-- Presents the public legal notice or privacy policy selected by the router. -->
<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import AstraLogo from '../components/AstraLogo.vue'

const props = defineProps({ type: { type: String, required: true } })
const privacy = computed(() => props.type === 'privacy')
</script>

<template>
  <div class="min-h-screen bg-[#fafafa] text-gray-900">
    <header class="bg-white border-b"><div class="max-w-4xl mx-auto px-6 h-20 flex items-center justify-between"><RouterLink to="/"><AstraLogo class="h-9 w-auto" /></RouterLink><RouterLink to="/" class="text-sm font-bold">Retour à l’accueil</RouterLink></div></header>
    <main class="max-w-4xl mx-auto px-6 py-14">
      <article class="bg-white border border-gray-100 rounded-3xl p-7 md:p-12 shadow-sm space-y-8">
        <div><p class="text-xs font-black uppercase tracking-[.2em] text-amber-500">ASTRA Location</p><h1 class="mt-3 text-3xl md:text-4xl font-black">{{ privacy ? 'Politique de confidentialité' : 'Mentions légales et conditions d’utilisation' }}</h1><p class="mt-3 text-sm text-gray-500">Dernière mise à jour : 11 août 2026</p></div>
        <template v-if="privacy">
          <section><h2>1. Données collectées</h2><p>ASTRA traite les informations de compte, de contact, de réservation et de paiement nécessaires à la location. Les données bancaires complètes ne sont jamais stockées par ASTRA et sont traitées par le prestataire de paiement configuré.</p></section>
          <section><h2>2. Utilisation et base légale</h2><p>Ces données servent à créer et sécuriser votre compte, gérer les réservations, répondre aux demandes, respecter les obligations légales et prévenir les abus. Le traitement repose sur l’exécution du service, les obligations légales et l’intérêt légitime de sécurisation.</p></section>
          <section><h2>3. Conservation et destinataires</h2><p>L’accès est limité au personnel autorisé et aux prestataires indispensables au service. Les durées de conservation suivent les exigences contractuelles, comptables et légales applicables au Maroc.</p></section>
          <section><h2>4. Vos droits</h2><p>Vous pouvez demander l’accès, la rectification ou la suppression des données admissibles en écrivant à <a href="mailto:contact@astra.ma">contact@astra.ma</a>. Une vérification d’identité peut être demandée.</p></section>
        </template>
        <template v-else>
          <section><h2>1. Éditeur</h2><p>Le service ASTRA Location est exploité à Tanger, Maroc. Contact : <a href="mailto:contact@astra.ma">contact@astra.ma</a> — +212 5 39 00 00 00.</p></section>
          <section><h2>2. Utilisation du service</h2><p>L’utilisateur fournit des informations exactes, protège ses identifiants et utilise le service uniquement pour des demandes de location licites. Une réservation n’est définitive qu’après confirmation par ASTRA.</p></section>
          <section><h2>3. Location, paiement et annulation</h2><p>Les prix, dates, conditions du véhicule et état du paiement sont présentés avant validation. Les règles d’annulation et de remboursement applicables à chaque dossier sont communiquées avec la réservation. Le paiement en ligne est disponible uniquement lorsque le prestataire externe est configuré.</p></section>
          <section><h2>4. Responsabilité et propriété</h2><p>Les contenus, marques et interfaces ASTRA sont protégés. ASTRA met en œuvre des moyens raisonnables pour assurer la disponibilité et la sécurité du service, sous réserve des interruptions de maintenance et des services externes.</p></section>
        </template>
        <div class="pt-4 border-t flex flex-wrap gap-4"><RouterLink to="/contact" class="px-5 py-3 bg-gray-900 text-white rounded-xl font-bold">Nous contacter</RouterLink><RouterLink :to="privacy ? '/legal' : '/privacy'" class="px-5 py-3 border rounded-xl font-bold">{{ privacy ? 'Mentions légales' : 'Confidentialité' }}</RouterLink></div>
      </article>
    </main>
  </div>
</template>

<style scoped>
section { display: grid; gap: .65rem; }
h2 { font-size: 1.05rem; font-weight: 900; }
p { color: #5f6368; line-height: 1.75; }
a { font-weight: 700; text-decoration: underline; }
</style>
