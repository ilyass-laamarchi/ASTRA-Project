<!-- Client-only portal showing owned reservations, payments, quick actions, and active cars. -->
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, CalendarCheck, Car, CheckCircle2, Clock3, CreditCard, Eye, UserRound, WalletCards, X } from 'lucide-vue-next'
import api from '../../lib/api'
import { clientSummary, isPaid, nextClientReservation } from '../../lib/clientDashboard'
import { todayIso } from '../../lib/dates'
import { publicMediaUrl } from '../../lib/media'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const loading = ref(true)
const actionBusy = ref(false)
const error = ref('')
const reservations = ref([])
const payments = ref([])
const cars = ref([])
const notifications = ref([])
const paymentAvailable = ref(false)

const items = result => Array.isArray(result?.data) ? result.data : (result?.data?.data || [])

/** Loads only the current client's reservations/payments plus active public cars. */
async function load() {
  loading.value = true
  error.value = ''
  try {
    const [reservationResult, paymentResult, carResult, notificationResult, paymentConfiguration] = await Promise.all([
      api.get('/my-reservations'),
      api.get('/my-payments'),
      api.get('/cars?sort=newest'),
      api.get('/notifications'),
      api.get('/payment-configuration'),
    ])
    reservations.value = items(reservationResult)
    payments.value = items(paymentResult)
    cars.value = items(carResult).slice(0, 4)
    notifications.value = items(notificationResult).slice(0, 4)
    paymentAvailable.value = Boolean(paymentConfiguration?.data?.checkout_available)
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Votre espace personnel n’a pas pu être chargé.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const summary = computed(() => clientSummary(reservations.value, payments.value, todayIso()))
const nextReservation = computed(() => nextClientReservation(reservations.value, todayIso()))
const latestReservations = computed(() => [...reservations.value].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 5))

/** Returns a browser-safe primary image URL with a local fleet fallback. */
function carImage(car) {
  const path = car?.images?.find(image => image.is_primary)?.path || car?.images?.[0]?.path
  if (!path) return '/assets/images/fleet-suv.png'
  return path.startsWith('/storage/') ? publicMediaUrl(path) : path
}

/** Formats a stored calendar date without UTC day shifts. */
function formatDate(value) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('fr-FR').format(new Date(`${value}T12:00:00`))
}

const formatMoney = value => Number(value || 0).toLocaleString('fr-MA', { style: 'currency', currency: 'MAD' })
const reservationStatus = status => ({ pending: 'En attente', confirmed: 'Confirmée', active: 'En cours', completed: 'Terminée', cancelled: 'Annulée', rejected: 'Refusée' }[status] || status)
const paymentStatus = reservation => isPaid(reservation) ? 'Payé' : (reservation?.payments || []).some(payment => ['pending', 'processing'].includes(payment.status)) ? 'En attente' : 'Non payé'

/** Cancels an owned pending reservation after user confirmation. */
async function cancelReservation(reservation) {
  if (reservation.status !== 'pending' || actionBusy.value) return
  actionBusy.value = true
  error.value = ''
  try {
    await api.patch(`/my-reservations/${reservation.id}/cancel`)
    reservation.status = 'cancelled'
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Cette réservation ne peut pas être annulée.'
  } finally {
    actionBusy.value = false
  }
}

/** Requests backend checkout and follows only the provider URL returned by Laravel. */
async function pay(reservation) {
  if (!paymentAvailable.value || reservation.status !== 'confirmed' || isPaid(reservation) || actionBusy.value) return
  actionBusy.value = true
  error.value = ''
  try {
    const result = await api.post(`/reservations/${reservation.id}/checkout`)
    const checkoutUrl = result.checkout_url || result.data?.checkout_url
    if (checkoutUrl) window.location.assign(checkoutUrl)
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Le paiement est indisponible.'
  } finally {
    actionBusy.value = false
  }
}
</script>

<template>
  <section class="client-home">
    <div v-if="loading" class="client-home__loading"><span></span>Chargement de votre espace…</div>
    <template v-else>
      <header class="client-home__welcome">
        <div><p>ESPACE PERSONNEL</p><h2>Bonjour, {{ auth.user?.first_name }}</h2><span>Retrouvez vos réservations, paiements et prochaines locations.</span></div>
        <button @click="router.push('/cars')"><Car :size="17" /> Réserver une voiture</button>
      </header>

      <p v-if="error" class="client-home__error">{{ error }}</p>

      <div class="client-home__stats">
        <article v-for="card in [
          { label: 'Mes réservations', value: summary.total, icon: CalendarCheck, tone: 'violet' },
          { label: 'Réservations à venir', value: summary.upcoming, icon: Clock3, tone: 'blue' },
          { label: 'Paiements en attente', value: summary.pendingPayments, icon: CreditCard, tone: 'amber' },
          { label: 'Locations terminées', value: summary.completed, icon: CheckCircle2, tone: 'green' },
        ]" :key="card.label" class="client-home__stat">
          <div :class="`client-home__stat-icon client-home__stat-icon--${card.tone}`"><component :is="card.icon" :size="19" /></div>
          <div><strong>{{ card.value }}</strong><span>{{ card.label }}</span></div>
        </article>
      </div>

      <div class="client-home__primary-grid">
        <article class="client-home__card client-home__next">
          <div class="client-home__card-head"><div><p>À VENIR</p><h3>Ma prochaine réservation</h3></div><CalendarCheck :size="20" /></div>
          <div v-if="nextReservation" class="client-home__next-body">
            <img :src="carImage(nextReservation.car)" :alt="`${nextReservation.car?.brand} ${nextReservation.car?.model}`">
            <div class="client-home__next-info">
              <div class="client-home__next-title"><div><small>{{ nextReservation.reservation_number }}</small><h4>{{ nextReservation.car?.brand }} {{ nextReservation.car?.model }}</h4></div><span :class="`status status--${nextReservation.status}`">{{ reservationStatus(nextReservation.status) }}</span></div>
              <dl>
                <div><dt>Dates</dt><dd>{{ formatDate(nextReservation.start_date) }} — {{ formatDate(nextReservation.end_date) }}</dd></div>
                <div><dt>Durée</dt><dd>{{ nextReservation.rental_days }} jour{{ nextReservation.rental_days > 1 ? 's' : '' }}</dd></div>
                <div><dt>Montant</dt><dd>{{ formatMoney(nextReservation.total_amount) }}</dd></div>
                <div><dt>Paiement</dt><dd>{{ paymentStatus(nextReservation) }}</dd></div>
              </dl>
              <div class="client-home__actions">
                <button class="action action--light" @click="router.push(`/client/reservations/${nextReservation.id}`)"><Eye :size="15" /> Voir</button>
                <button v-if="nextReservation.status === 'confirmed' && !isPaid(nextReservation) && paymentAvailable" class="action action--dark" :disabled="actionBusy" @click="pay(nextReservation)"><WalletCards :size="15" /> Payer maintenant</button>
                <button v-if="nextReservation.status === 'pending'" class="action action--danger" :disabled="actionBusy" @click="cancelReservation(nextReservation)"><X :size="15" /> Annuler</button>
              </div>
            </div>
          </div>
          <div v-else class="client-home__empty"><CalendarCheck :size="26" /><strong>Aucune réservation à venir</strong><button @click="router.push('/cars')">Découvrir les véhicules</button></div>
        </article>

        <article class="client-home__card client-home__quick">
          <div class="client-home__card-head"><div><p>RACCOURCIS</p><h3>Actions rapides</h3></div></div>
          <button v-for="action in [
            { label: 'Réserver une voiture', icon: Car, to: '/cars' },
            { label: 'Mes réservations', icon: CalendarCheck, to: '/client/reservations' },
            { label: 'Mes paiements', icon: CreditCard, to: '/client/payments' },
            { label: 'Mon profil', icon: UserRound, to: '/client/profile' },
          ]" :key="action.label" @click="router.push(action.to)"><component :is="action.icon" :size="17" /><span>{{ action.label }}</span><b>→</b></button>
        </article>
      </div>

      <article class="client-home__card">
        <div class="client-home__card-head"><div><p>HISTORIQUE</p><h3>Mes dernières réservations</h3></div><button class="client-home__link" @click="router.push('/client/reservations')">Tout voir →</button></div>
        <div class="client-home__table-wrap"><table><thead><tr><th>Réservation</th><th>Véhicule</th><th>Période</th><th>Montant</th><th>Statut</th></tr></thead><tbody><tr v-for="reservation in latestReservations" :key="reservation.id"><td><strong>{{ reservation.reservation_number }}</strong></td><td>{{ reservation.car?.brand }} {{ reservation.car?.model }}</td><td>{{ formatDate(reservation.start_date) }} — {{ formatDate(reservation.end_date) }}</td><td>{{ formatMoney(reservation.total_amount) }}</td><td><span :class="`status status--${reservation.status}`">{{ reservationStatus(reservation.status) }}</span></td></tr><tr v-if="!latestReservations.length"><td colspan="5" class="client-home__table-empty">Aucune réservation.</td></tr></tbody></table></div>
      </article>

      <article class="client-home__card">
        <div class="client-home__card-head"><div><p>FLOTTE ASTRA</p><h3>Véhicules disponibles</h3></div><button class="client-home__link" @click="router.push('/cars')">Voir la flotte →</button></div>
        <div class="client-home__cars"><button v-for="car in cars" :key="car.id" @click="router.push(`/cars/${car.id}`)"><img :src="carImage(car)" :alt="`${car.brand} ${car.model}`"><div><strong>{{ car.brand }} {{ car.model }}</strong><span>{{ formatMoney(car.daily_price) }} / jour</span></div></button><p v-if="!cars.length">Aucun véhicule disponible actuellement.</p></div>
      </article>

      <article v-if="notifications.length" class="client-home__card">
        <div class="client-home__card-head"><div><p>NOTIFICATIONS</p><h3>Mon activité récente</h3></div><Bell :size="19" /></div>
        <div class="client-home__activity"><div v-for="notification in notifications" :key="notification.id"><span></span><div><strong>{{ notification.title }}</strong><p>{{ notification.message }}</p></div></div></div>
      </article>
    </template>
  </section>
</template>

<style scoped>
.client-home{display:flex;flex-direction:column;gap:1.2rem;color:#17151c}.client-home__loading{display:flex;align-items:center;gap:.75rem;padding:3rem;color:#8f8a99;font-weight:700}.client-home__loading span{width:20px;height:20px;border:2px solid #e8e4ee;border-top-color:#6e5ba7;border-radius:50%;animation:spin .7s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
.client-home__welcome{padding:1.5rem 1.7rem;border-radius:1.4rem;background:linear-gradient(120deg,#1b1721,#4f426f);color:white;display:flex;align-items:center;justify-content:space-between;gap:1rem;box-shadow:0 12px 30px rgba(54,41,85,.14)}.client-home__welcome p,.client-home__card-head p{font-size:.62rem;font-weight:900;letter-spacing:.14em;color:#9f8ed0}.client-home__welcome h2{font-size:1.65rem;font-weight:900;letter-spacing:-.03em}.client-home__welcome span{font-size:.78rem;color:#d5cedf}.client-home__welcome button{display:flex;align-items:center;gap:.5rem;border:0;border-radius:.85rem;background:#fff;color:#28202f;padding:.8rem 1rem;font-size:.75rem;font-weight:900;cursor:pointer;white-space:nowrap}.client-home__error{padding:.8rem 1rem;border-radius:.8rem;background:#fff1f2;color:#be123c;font-size:.78rem;font-weight:800}
.client-home__stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.client-home__stat{display:flex;align-items:center;gap:.85rem;padding:1.1rem;background:#fff;border:1px solid #f0edf3;border-radius:1.15rem}.client-home__stat-icon{width:40px;height:40px;border-radius:.8rem;display:grid;place-items:center}.client-home__stat-icon--violet{background:#f0ebff;color:#7256aa}.client-home__stat-icon--blue{background:#eaf3ff;color:#4679aa}.client-home__stat-icon--amber{background:#fff5dc;color:#b57b20}.client-home__stat-icon--green{background:#eaf8f0;color:#34845a}.client-home__stat strong{display:block;font-size:1.3rem;font-weight:900}.client-home__stat span{font-size:.68rem;color:#85808d;font-weight:700}
.client-home__primary-grid{display:grid;grid-template-columns:minmax(0,2.2fr) minmax(230px,.8fr);gap:1rem}.client-home__card{background:#fff;border:1px solid #f0edf3;border-radius:1.25rem;padding:1.25rem}.client-home__card-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}.client-home__card-head h3{font-size:.92rem;font-weight:900}.client-home__next-body{display:grid;grid-template-columns:minmax(210px,.8fr) minmax(0,1.2fr);gap:1.2rem}.client-home__next-body>img{width:100%;height:210px;object-fit:contain;border-radius:1rem;background:linear-gradient(145deg,#f6f3f9,#eae5f1)}.client-home__next-info{display:flex;flex-direction:column;gap:.9rem}.client-home__next-title{display:flex;justify-content:space-between;align-items:flex-start;gap:.7rem}.client-home__next-title small{font-size:.62rem;font-weight:800;color:#91899b}.client-home__next-title h4{font-size:1.2rem;font-weight:900}.client-home__next-info dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem}.client-home__next-info dl div{padding:.65rem .75rem;background:#faf9fb;border-radius:.7rem}.client-home__next-info dt{font-size:.58rem;text-transform:uppercase;letter-spacing:.08em;color:#a19aa9;font-weight:800}.client-home__next-info dd{font-size:.73rem;font-weight:800;margin-top:.15rem}.client-home__actions{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:auto}.action{display:flex;align-items:center;gap:.35rem;border:0;border-radius:.65rem;padding:.65rem .8rem;font-size:.68rem;font-weight:900;cursor:pointer}.action--light{background:#f2eff5;color:#4b4059}.action--dark{background:#211b28;color:#fff}.action--danger{background:#fff0f1;color:#b83b48}.client-home__empty{min-height:210px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.65rem;color:#938b9c}.client-home__empty button{border:0;background:none;color:#635188;font-weight:800;cursor:pointer}
.client-home__quick{display:flex;flex-direction:column}.client-home__quick>button{display:flex;align-items:center;gap:.65rem;width:100%;padding:.82rem .3rem;background:none;border:0;border-bottom:1px solid #f2eff4;color:#3d3645;font-size:.72rem;font-weight:800;text-align:left;cursor:pointer}.client-home__quick>button:last-child{border-bottom:0}.client-home__quick>button span{flex:1}.client-home__quick>button b{color:#aaa2b3}.client-home__link{border:0;background:none;color:#6a568f;font-size:.68rem;font-weight:900;cursor:pointer}
.client-home__table-wrap{overflow-x:auto}.client-home table{width:100%;border-collapse:collapse;font-size:.72rem}.client-home th{text-align:left;padding:.65rem;color:#a09aa7;font-size:.58rem;text-transform:uppercase;letter-spacing:.08em;border-bottom:1px solid #eeeaf1}.client-home td{padding:.8rem .65rem;border-bottom:1px solid #f5f2f6;font-weight:650;white-space:nowrap}.client-home__table-empty{text-align:center;color:#aaa3af}.status{display:inline-flex;padding:.3rem .55rem;border-radius:999px;font-size:.58rem;font-weight:900}.status--confirmed{background:#e8f2ff;color:#3f6fa4}.status--pending{background:#fff4d8;color:#a66c17}.status--active{background:#e7f8ef;color:#2f8057}.status--completed{background:#f0eef3;color:#6b6572}.status--cancelled,.status--rejected{background:#ffedef;color:#a83e49}
.client-home__cars{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem}.client-home__cars>button{border:1px solid #f0edf3;background:#faf9fb;border-radius:1rem;padding:.7rem;text-align:left;cursor:pointer}.client-home__cars img{width:100%;height:105px;object-fit:contain}.client-home__cars strong{display:block;font-size:.75rem}.client-home__cars span{font-size:.65rem;color:#796c85;font-weight:750}.client-home__activity{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.client-home__activity>div{display:flex;gap:.65rem;padding:.75rem;background:#faf9fb;border-radius:.8rem}.client-home__activity>div>span{width:7px;height:7px;margin-top:.3rem;border-radius:50%;background:#8a73b2}.client-home__activity strong{font-size:.7rem}.client-home__activity p{font-size:.65rem;color:#827b89;margin-top:.12rem}
@media(max-width:1100px){.client-home__stats{grid-template-columns:repeat(2,1fr)}.client-home__primary-grid{grid-template-columns:1fr}.client-home__cars{grid-template-columns:repeat(2,1fr)}}@media(max-width:700px){.client-home__welcome{align-items:flex-start;flex-direction:column}.client-home__stats{grid-template-columns:1fr}.client-home__next-body{grid-template-columns:1fr}.client-home__next-body>img{height:170px}.client-home__next-info dl{grid-template-columns:1fr}.client-home__cars,.client-home__activity{grid-template-columns:1fr}}
</style>
