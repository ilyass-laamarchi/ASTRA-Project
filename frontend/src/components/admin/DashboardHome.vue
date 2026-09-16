<!-- Role-specific staff dashboard for Responsable operations or administrator analytics. -->
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AlertTriangle, CalendarDays, CarFront, CheckCircle2, Clock3, CreditCard, Gauge, Receipt, RefreshCw, TrendingUp, Undo2, Users, WalletCards, Wrench } from 'lucide-vue-next'
import api from '../../lib/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const loading = ref(true)
const error = ref('')
const period = ref('30d')
const dashboard = ref(null)
const isAdmin = computed(() => auth.user?.role === 'admin')
const prefix = computed(() => `/${auth.user.role}`)
const periods = [
  { value: 'today', label: 'Aujourd’hui' }, { value: '7d', label: '7 jours' },
  { value: '30d', label: '30 jours' }, { value: 'month', label: 'Ce mois' },
  { value: 'year', label: 'Cette année' },
]

// Fetch the role-specific dashboard for the selected reporting period.
async function loadDashboard(nextPeriod = period.value) {
  period.value = nextPeriod
  loading.value = true
  error.value = ''
  try {
    const response = await api.get(`${prefix.value}/dashboard`, { params: { period: period.value } })
    dashboard.value = response.data?.data || response.data
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Impossible de charger les analyses.'
  } finally { loading.value = false }
}
onMounted(() => loadDashboard())

const periodLabel = computed(() => periods.find(item => item.value === period.value)?.label || '')
const adminKpis = computed(() => {
  const k = dashboard.value?.kpis || {}
  return [
    ['Chiffre d’affaires', money(k.revenue), 'Paiements validés', TrendingUp, 'blue'],
    ['Réservations', number(k.reservations), periodLabel.value, CalendarDays, 'navy'],
    ['Clients actifs', number(k.active_clients), 'Comptes actifs', Users, 'cyan'],
    ['Véhicules actifs', number(k.active_vehicles), 'Flotte exploitable', CarFront, 'slate'],
    ['Paiements reçus', number(k.payments_received), 'Transactions payées', CreditCard, 'green'],
    ['Paiements en attente', number(k.payments_pending), 'À suivre', Clock3, 'amber'],
    ['Taux d’occupation', `${number(k.occupancy_rate)} %`, 'Flotte active', Gauge, 'violet'],
    ['Revenu moyen', money(k.average_booking_revenue), 'Par réservation payée', Receipt, 'blue'],
  ]
})
const ownerKpis = computed(() => {
  const k = dashboard.value?.kpis || {}
  return [
    ['Réservations aujourd’hui', number(k.reservations_today), 'Nouvelles demandes', CalendarDays, 'blue'],
    ['En attente', number(k.pending_reservations), 'À confirmer', Clock3, 'amber'],
    ['Confirmées', number(k.confirmed_reservations), periodLabel.value, CheckCircle2, 'green'],
    ['Retours aujourd’hui', number(k.returns_today), 'À réceptionner', Undo2, 'violet'],
    ['Disponibles', number(k.available_vehicles), 'Prêts à louer', CarFront, 'cyan'],
    ['Loués / réservés', number(k.rented_vehicles), 'Occupation actuelle', WalletCards, 'navy'],
    ['En maintenance', number(k.maintenance_vehicles), 'Hors disponibilité', Wrench, 'slate'],
    ['Taux d’occupation', `${number(k.occupancy_rate)} %`, 'Flotte active', Gauge, 'blue'],
  ]
})
const kpis = computed(() => isAdmin.value ? adminKpis.value : ownerKpis.value)
const activity = computed(() => dashboard.value?.reservation_activity || [])
const maxActivity = computed(() => Math.max(1, ...activity.value.flatMap(item => [item.pending, item.confirmed, item.completed, item.cancelled_rejected])))
const revenueSeries = computed(() => dashboard.value?.revenue_series || [])
const maxRevenue = computed(() => Math.max(1, ...revenueSeries.value.map(item => Number(item.value || 0))))
const revenuePoints = computed(() => revenueSeries.value.map((item, index, values) => {
  const x = values.length <= 1 ? 50 : (index / (values.length - 1)) * 100
  return `${x},${92 - (Number(item.value || 0) / maxRevenue.value) * 80}`
}).join(' '))

const palette = ['#1a5d96', '#31b7e7', '#0a274f', '#64d7f6', '#6b7c93', '#17a673', '#efad35', '#8d6bd1']
// Convert labelled values into a conic-gradient definition for donut charts.
function donutStyle(items = []) {
  const total = items.reduce((sum, item) => sum + Number(item.value || 0), 0)
  if (!total) return { background: '#e8eef5' }
  let cursor = 0
  return { background: `conic-gradient(${items.map((item, index) => { const start = cursor; cursor += Number(item.value || 0) / total * 100; return `${palette[index % palette.length]} ${start}% ${cursor}%` }).join(',')})` }
}
const fleetItems = computed(() => {
  const fleet = dashboard.value?.fleet_distribution || {}
  return [
    { label: 'Disponible', value: fleet.available || 0 }, { label: 'Loué / réservé', value: fleet.rented_reserved || 0 },
    { label: 'Maintenance', value: fleet.maintenance || 0 }, { label: 'Indisponible', value: fleet.unavailable || 0 },
  ]
})
const totalFleet = computed(() => fleetItems.value.reduce((sum, item) => sum + Number(item.value), 0))
const categoryItems = computed(() => dashboard.value?.category_distribution || [])
const statusItems = computed(() => (dashboard.value?.reservation_status || []).map(item => ({ ...item, label: statusLabel(item.label) })))
// Format compact dashboard quantities for the Moroccan locale.
function number(value) { return Number(value || 0).toLocaleString('fr-MA', { maximumFractionDigits: 1 }) }
// Format dashboard revenue values in Moroccan dirhams.
function money(value) { return Number(value || 0).toLocaleString('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 }) }
// Render date-only values at midday to avoid timezone date shifts.
function date(value, year = true) { return value ? new Intl.DateTimeFormat('fr-MA', { day: '2-digit', month: 'short', ...(year ? { year: 'numeric' } : {}) }).format(new Date(`${value}T12:00:00`)) : '—' }
// Translate backend status identifiers for staff-facing labels.
function statusLabel(value) { return ({ pending: 'En attente', confirmed: 'Confirmée', completed: 'Terminée', cancelled: 'Annulée', rejected: 'Refusée', paid: 'Payé', unpaid: 'Non payé', available: 'Disponible', maintenance: 'Maintenance', unavailable: 'Indisponible' })[value] || value }
// Return the shared CSS class for a status pill.
function statusClass(value) { return `status status--${value || 'neutral'}` }
// Navigate from dashboard summaries to the staff reservation detail.
function openReservation(id) { router.push(`${prefix.value}/reservations/${id}`) }
</script>

<template>
  <section class="analytics" :class="isAdmin ? 'analytics--admin' : 'analytics--owner'">
    <header class="analytics__header">
      <div><span class="analytics__eyebrow">{{ isAdmin ? 'Pilotage stratégique' : 'Centre opérationnel' }}</span><h2>{{ isAdmin ? 'Performance globale ASTRA' : 'Opérations quotidiennes' }}</h2><p>{{ isAdmin ? 'Revenus, clientèle et performance de la flotte.' : 'Réservations, mouvements de flotte et priorités du jour.' }}</p></div>
      <div class="analytics__filters" aria-label="Période d’analyse"><button v-for="item in periods" :key="item.value" :class="{ active: period === item.value }" :disabled="loading" @click="loadDashboard(item.value)">{{ item.label }}</button></div>
    </header>

    <div v-if="loading" class="analytics__state"><RefreshCw :size="20" class="spin" /> Actualisation des données réelles…</div>
    <div v-else-if="error" class="analytics__state analytics__state--error"><AlertTriangle :size="20" /> {{ error }} <button @click="loadDashboard()">Réessayer</button></div>

    <template v-else-if="dashboard">
      <div class="kpi-grid">
        <article v-for="item in kpis" :key="item[0]" class="kpi" :class="`kpi--${item[4]}`"><div class="kpi__top"><span>{{ item[0] }}</span><span class="kpi__icon"><component :is="item[3]" :size="19" /></span></div><strong>{{ item[1] }}</strong><small>{{ item[2] }}</small></article>
      </div>

      <div class="analytics__grid analytics__grid--2">
        <article class="panel panel--wide">
          <div class="panel__head"><div><span>{{ isAdmin ? 'Évolution des réservations' : 'Activité des réservations' }}</span><small>{{ periodLabel }}</small></div><div class="legend"><i class="pending"></i>En attente <i class="confirmed"></i>Confirmées <i class="completed"></i>Terminées <i class="cancelled"></i>Annulées/refusées</div></div>
          <div class="activity-chart"><div v-for="point in activity" :key="point.label" class="activity-chart__group" :title="`${point.label} — ${point.pending + point.confirmed + point.completed + point.cancelled_rejected} réservation(s)`"><div class="activity-chart__bars"><i class="bar pending" :style="{ height: `${Math.max(3, point.pending / maxActivity * 100)}%` }"></i><i class="bar confirmed" :style="{ height: `${Math.max(3, point.confirmed / maxActivity * 100)}%` }"></i><i class="bar completed" :style="{ height: `${Math.max(3, point.completed / maxActivity * 100)}%` }"></i><i class="bar cancelled" :style="{ height: `${Math.max(3, point.cancelled_rejected / maxActivity * 100)}%` }"></i></div><span>{{ point.label }}</span></div></div>
        </article>
        <article v-if="isAdmin" class="panel"><div class="panel__head"><div><span>Évolution du chiffre d’affaires</span><small>Paiements validés uniquement</small></div></div><div class="line-chart"><svg viewBox="0 0 100 100" preserveAspectRatio="none"><polyline :points="revenuePoints" fill="none" stroke="#28b8ec" stroke-width="2.2" vector-effect="non-scaling-stroke" /></svg><div class="line-chart__labels"><span v-for="item in revenueSeries" :key="item.label" :title="money(item.value)">{{ item.label }}</span></div></div><strong class="panel__total">{{ money(dashboard.kpis.revenue) }}</strong></article>
        <article v-else class="panel"><div class="panel__head"><div><span>Disponibilité de la flotte</span><small>État actuel</small></div></div><div class="donut-layout"><div class="donut" :style="donutStyle(fleetItems)"><span>{{ totalFleet }}<small>véhicules</small></span></div><div class="donut-legend"><span v-for="(item,index) in fleetItems" :key="item.label"><i :style="{background:palette[index]}"></i>{{ item.label }}<b>{{ item.value }}</b></span></div></div></article>
      </div>

      <template v-if="isAdmin">
        <div class="analytics__grid analytics__grid--3">
          <article class="panel"><div class="panel__head"><div><span>Répartition de la flotte</span><small>État actuel</small></div></div><div class="donut-layout"><div class="donut" :style="donutStyle(fleetItems)"><span>{{ totalFleet }}<small>véhicules</small></span></div><div class="donut-legend"><span v-for="(item,index) in fleetItems" :key="item.label"><i :style="{background:palette[index]}"></i>{{ item.label }}<b>{{ item.value }}</b></span></div></div></article>
          <article class="panel"><div class="panel__head"><div><span>Répartition par catégorie</span><small>Flotte réelle</small></div></div><div class="donut-layout"><div class="donut" :style="donutStyle(categoryItems)"><span>{{ totalFleet }}<small>véhicules</small></span></div><div class="donut-legend"><span v-for="(item,index) in categoryItems" :key="item.label"><i :style="{background:palette[index%palette.length]}"></i>{{ item.label }}<b>{{ item.value }}</b></span></div></div></article>
          <article class="panel"><div class="panel__head"><div><span>Statut des réservations</span><small>{{ periodLabel }}</small></div></div><div class="donut-layout"><div class="donut" :style="donutStyle(statusItems)"><span>{{ dashboard.kpis.reservations }}<small>réservations</small></span></div><div class="donut-legend"><span v-for="(item,index) in statusItems" :key="item.label"><i :style="{background:palette[index]}"></i>{{ item.label }}<b>{{ item.value }}</b></span></div></div></article>
        </div>
        <div class="analytics__grid analytics__grid--2 analytics__grid--admin-detail">
          <article class="panel"><div class="panel__head"><div><span>Revenus par véhicule</span><small>Paiements réels</small></div></div><div class="horizontal-bars"><div v-for="item in dashboard.revenue_by_vehicle" :key="item.label"><span>{{ item.label }}</span><i><b :style="{width:`${Math.max(2,item.value/(dashboard.revenue_by_vehicle[0]?.value||1)*100)}%`}"></b></i><strong>{{ money(item.value) }}</strong></div><p v-if="!dashboard.revenue_by_vehicle.length">Aucun paiement validé sur cette période.</p></div></article>
          <article class="panel"><div class="panel__head"><div><span>Clients</span><small>Indicateurs relationnels</small></div></div><div class="client-stats"><div><strong>{{ number(dashboard.clients.total) }}</strong><span>Total clients</span></div><div><strong>{{ number(dashboard.clients.new_this_month) }}</strong><span>Nouveaux ce mois</span></div><div><strong>{{ number(dashboard.clients.returning) }}</strong><span>Clients récurrents</span></div><div><strong>{{ number(dashboard.clients.with_active_reservations) }}</strong><span>Réservations actives</span></div></div></article>
        </div>
        <article class="panel"><div class="panel__head"><div><span>Performance des véhicules</span><small>{{ periodLabel }}</small></div></div><div class="table-scroll"><table><thead><tr><th>Véhicule</th><th>Réservations</th><th>Jours réservés</th><th>Revenu</th><th>Utilisation</th><th>État</th></tr></thead><tbody><tr v-for="row in dashboard.vehicle_performance" :key="row.id"><td><b>{{ row.vehicle }}</b></td><td>{{ row.reservations }}</td><td>{{ row.booked_days }}</td><td>{{ money(row.revenue) }}</td><td><div class="util"><i><b :style="{width:`${row.utilization}%`}"></b></i><span>{{ row.utilization }}%</span></div></td><td><span :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td></tr></tbody></table></div></article>
        <div class="analytics__grid analytics__grid--2"><article class="panel reservations-panel"><div class="panel__head"><div><span>Dernières réservations</span><small>Données en temps réel</small></div></div><div class="table-scroll"><table><thead><tr><th>Référence</th><th>Client</th><th>Véhicule</th><th>Dates</th><th>Montant</th><th>Statut</th><th></th></tr></thead><tbody><tr v-for="row in dashboard.latest_reservations" :key="row.id"><td><b>{{ row.reference }}</b></td><td>{{ row.client }}</td><td>{{ row.vehicle }}</td><td>{{ date(row.start_date,false) }} → {{ date(row.end_date,false) }}</td><td>{{ money(row.amount) }}</td><td><span :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td><td><button class="table-action" @click="openReservation(row.id)">Voir</button></td></tr><tr v-if="!dashboard.latest_reservations.length"><td colspan="7" class="empty">Aucune réservation.</td></tr></tbody></table></div></article><article class="panel"><div class="panel__head"><div><span>Activité récente</span><small>Événements ASTRA</small></div></div><div class="activity-feed"><div v-for="item in dashboard.recent_activity" :key="item.id"><i></i><p><b>{{ item.title }}</b><span>{{ item.message }}</span></p></div><p v-if="!dashboard.recent_activity.length" class="empty">Aucune activité récente.</p></div></article></div>
      </template>

      <template v-else>
        <div class="analytics__grid analytics__grid--2"><article class="panel"><div class="panel__head"><div><span>Répartition par catégorie</span><small>Flotte réelle</small></div></div><div class="donut-layout"><div class="donut" :style="donutStyle(categoryItems)"><span>{{ totalFleet }}<small>véhicules</small></span></div><div class="donut-legend"><span v-for="(item,index) in categoryItems" :key="item.label"><i :style="{background:palette[index%palette.length]}"></i>{{ item.label }}<b>{{ item.value }}</b></span></div></div></article><article class="panel"><div class="panel__head"><div><span>Alertes opérationnelles</span><small>Priorités réelles</small></div></div><div class="alerts"><button v-for="alert in dashboard.alerts" :key="alert.type" @click="router.push(`${prefix}/reservations`)"><AlertTriangle :size="18" /><span>{{ alert.label }}</span><b>{{ alert.count }}</b></button><p v-if="!dashboard.alerts.length" class="empty">Aucune alerte opérationnelle.</p></div></article></div>
        <article class="panel"><div class="panel__head"><div><span>Planning des départs et retours</span><small>7 prochains jours</small></div></div><div class="table-scroll"><table><thead><tr><th>Client</th><th>Véhicule</th><th>Départ</th><th>Retour</th><th>Statut</th><th></th></tr></thead><tbody><tr v-for="row in dashboard.planning" :key="row.id"><td><b>{{ row.client }}</b></td><td>{{ row.vehicle }}</td><td>{{ date(row.departure) }}</td><td>{{ date(row.return) }}</td><td><span :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td><td><button class="table-action" @click="openReservation(row.id)">Voir</button></td></tr><tr v-if="!dashboard.planning.length"><td colspan="6" class="empty">Aucun départ ou retour planifié.</td></tr></tbody></table></div></article>
        <article class="panel reservations-panel"><div class="panel__head"><div><span>Dernières réservations</span><small>Données en temps réel</small></div></div><div class="table-scroll"><table><thead><tr><th>Référence</th><th>Client</th><th>Véhicule</th><th>Dates</th><th>Montant</th><th>Statut</th><th></th></tr></thead><tbody><tr v-for="row in dashboard.latest_reservations" :key="row.id"><td><b>{{ row.reference }}</b></td><td>{{ row.client }}</td><td>{{ row.vehicle }}</td><td>{{ date(row.start_date,false) }} → {{ date(row.end_date,false) }}</td><td>{{ money(row.amount) }}</td><td><span :class="statusClass(row.status)">{{ statusLabel(row.status) }}</span></td><td><button class="table-action" @click="openReservation(row.id)">Voir</button></td></tr><tr v-if="!dashboard.latest_reservations.length"><td colspan="7" class="empty">Aucune réservation.</td></tr></tbody></table></div></article>
      </template>
    </template>
  </section>
</template>

<style scoped>
.analytics{display:grid;gap:18px;color:#10213d}.analytics__header{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;padding:4px 2px 10px}.analytics__eyebrow{color:#198fc4;font-size:.66rem;font-weight:850;letter-spacing:.13em;text-transform:uppercase}.analytics__header h2{margin:5px 0 3px;font-size:1.55rem;letter-spacing:-.03em}.analytics__header p{margin:0;color:#728097;font-size:.78rem}.analytics__filters{display:flex;gap:5px;padding:5px;background:#edf3f8;border:1px solid #e0e9f1;border-radius:12px}.analytics__filters button{padding:8px 11px;border:0;border-radius:8px;color:#64738a;background:transparent;font:750 .65rem/1 inherit;cursor:pointer}.analytics__filters button.active{color:#fff;background:#0b2b54;box-shadow:0 5px 12px rgba(11,43,84,.17)}.analytics__filters button:disabled{cursor:wait;opacity:.65}
.analytics__state{display:flex;align-items:center;justify-content:center;gap:9px;min-height:260px;color:#718096;font-size:.8rem;font-weight:700}.analytics__state--error{color:#a33d3d}.analytics__state button{border:0;background:none;color:#0d6c9e;font-weight:800;cursor:pointer}.spin{animation:spin .8s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
.kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.kpi{--tone:#168fc8;position:relative;overflow:hidden;min-height:128px;padding:17px;background:#fff;border:1px solid #e7edf3;border-radius:16px;box-shadow:0 8px 24px rgba(19,43,74,.045)}.kpi:after{content:'';position:absolute;right:-28px;bottom:-38px;width:90px;height:90px;border-radius:50%;background:var(--tone);opacity:.07}.kpi__top{display:flex;align-items:center;justify-content:space-between;gap:12px;color:#68778d;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.045em}.kpi__icon{display:grid;place-items:center;width:34px;height:34px;color:var(--tone);background:color-mix(in srgb,var(--tone) 10%,white);border-radius:10px}.kpi strong{display:block;margin:13px 0 5px;color:#0a1d3a;font-size:1.48rem;line-height:1;letter-spacing:-.035em}.kpi small{color:#8a96a7;font-size:.65rem}.kpi--navy{--tone:#0a2c59}.kpi--cyan{--tone:#2bbce9}.kpi--slate{--tone:#64748b}.kpi--green{--tone:#16a36f}.kpi--amber{--tone:#d99725}.kpi--violet{--tone:#8064c2}
.analytics__grid{display:grid;gap:14px}.analytics__grid--2{grid-template-columns:minmax(0,1.55fr) minmax(320px,1fr)}.analytics__grid--3{grid-template-columns:repeat(3,minmax(0,1fr))}.analytics__grid--admin-detail{grid-template-columns:1.35fr 1fr}.panel{min-width:0;padding:18px;background:#fff;border:1px solid #e5ecf3;border-radius:17px;box-shadow:0 9px 26px rgba(20,43,72,.04)}.analytics--admin .panel{border-top:2px solid rgba(36,167,216,.3)}.analytics--owner .panel{border-top:2px solid rgba(11,43,84,.18)}.panel__head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px}.panel__head>div:first-child{display:grid;gap:3px}.panel__head span{font-size:.82rem;font-weight:850}.panel__head small{color:#8a96a7;font-size:.62rem}.panel__total{display:block;margin-top:8px;color:#0b315d;font-size:1.25rem}.legend{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:7px 10px;color:#77859a;font-size:.57rem;font-weight:650}.legend i{width:7px;height:7px;margin-right:-5px;border-radius:2px}.pending{background:#f0ad38}.confirmed{background:#18a978}.completed{background:#2a8ac3}.cancelled{background:#d86868}
.activity-chart{display:flex;align-items:stretch;gap:5px;height:205px;padding:8px 2px 0;overflow-x:auto;border-bottom:1px solid #e9eef4;background:repeating-linear-gradient(to bottom,transparent 0,transparent 48px,#eef2f6 49px)}.activity-chart__group{display:flex;flex:1 0 24px;min-width:24px;flex-direction:column;justify-content:flex-end;align-items:center;gap:7px}.activity-chart__bars{display:flex;align-items:flex-end;justify-content:center;gap:2px;width:100%;height:168px}.bar{display:block;width:20%;min-width:3px;max-width:8px;border-radius:3px 3px 0 0}.activity-chart__group>span{color:#8793a4;font-size:.52rem;white-space:nowrap}
.line-chart{position:relative;height:190px;background:repeating-linear-gradient(to bottom,transparent 0,transparent 44px,#eef2f6 45px)}.line-chart svg{width:100%;height:160px;overflow:visible}.line-chart__labels{display:flex;justify-content:space-between;gap:3px;color:#8995a7;font-size:.5rem;overflow:hidden}.line-chart__labels span{min-width:0;overflow:hidden;text-overflow:ellipsis}
.donut-layout{display:flex;align-items:center;gap:20px;min-height:155px}.donut{position:relative;flex:0 0 128px;width:128px;height:128px;border-radius:50%}.donut:after{content:'';position:absolute;inset:21px;background:#fff;border-radius:50%}.donut>span{position:absolute;z-index:2;inset:0;display:grid;place-content:center;text-align:center;color:#0c2547;font-size:1.2rem;font-weight:900}.donut>span small{display:block;color:#8794a6;font-size:.52rem;font-weight:650}.donut-legend{display:grid;flex:1;gap:8px}.donut-legend span{display:grid;grid-template-columns:8px 1fr auto;align-items:center;gap:8px;color:#66758a;font-size:.63rem}.donut-legend i{width:8px;height:8px;border-radius:3px}.donut-legend b{color:#152945}
.horizontal-bars{display:grid;gap:11px}.horizontal-bars>div{display:grid;grid-template-columns:130px 1fr 80px;align-items:center;gap:10px;font-size:.62rem}.horizontal-bars span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#526278;font-weight:700}.horizontal-bars i{height:7px;overflow:hidden;background:#eaf0f5;border-radius:999px}.horizontal-bars i b{display:block;height:100%;background:linear-gradient(90deg,#0d4f89,#35bce9);border-radius:inherit}.horizontal-bars strong{text-align:right;color:#17304f;font-size:.62rem}.horizontal-bars p,.empty{color:#96a1b0;font-size:.7rem;text-align:center}.client-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px}.client-stats div{display:grid;gap:5px;padding:15px;background:#f5f9fc;border:1px solid #e6edf4;border-radius:12px}.client-stats strong{font-size:1.25rem;color:#0c315d}.client-stats span{color:#718096;font-size:.62rem;font-weight:700}
.table-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}th{padding:9px 10px;color:#8a96a7;font-size:.57rem;font-weight:800;text-align:left;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #e8eef4}td{padding:11px 10px;color:#59697e;font-size:.64rem;border-bottom:1px solid #eef2f6}td b{color:#17304f}.status{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2f6;color:#607086;font-size:.55rem;font-weight:800}.status--pending{color:#9a6813;background:#fff5d9}.status--confirmed,.status--available,.status--paid{color:#137a58;background:#dcf8ed}.status--completed{color:#17658f;background:#e1f3fb}.status--cancelled,.status--rejected,.status--unavailable{color:#a33f3f;background:#fde8e8}.status--maintenance{color:#8b641a;background:#fff1cf}.table-action{padding:6px 9px;color:#0d6494;background:#e8f6fc;border:0;border-radius:7px;font-size:.57rem;font-weight:800;cursor:pointer}.util{display:flex;align-items:center;gap:7px}.util>i{width:70px;height:6px;overflow:hidden;background:#e8eef4;border-radius:99px}.util i b{display:block;height:100%;background:#2bb7e8}.util span{font-size:.57rem}
.activity-feed{display:grid;gap:12px}.activity-feed>div{display:grid;grid-template-columns:9px 1fr;gap:10px}.activity-feed i{width:8px;height:8px;margin-top:5px;background:#35bde9;border:2px solid #d9f4fd;border-radius:50%}.activity-feed p{display:grid;gap:3px;margin:0}.activity-feed b{color:#203550;font-size:.67rem}.activity-feed span{color:#79869a;font-size:.61rem;line-height:1.4}.alerts{display:grid;gap:9px}.alerts button{display:grid;grid-template-columns:24px 1fr auto;align-items:center;gap:9px;padding:12px;color:#6d5119;background:#fff8e7;border:1px solid #f6e5b8;border-radius:11px;text-align:left;cursor:pointer}.alerts button span{font-size:.66rem;font-weight:750}.alerts button b{display:grid;place-items:center;min-width:24px;height:24px;color:#fff;background:#d99b2a;border-radius:8px;font-size:.65rem}
@media(max-width:1280px){.kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.analytics__grid--3{grid-template-columns:1fr 1fr}.analytics__grid--3>.panel:last-child{grid-column:1/-1}}
@media(max-width:900px){.analytics__header{align-items:flex-start;flex-direction:column}.analytics__filters{max-width:100%;overflow-x:auto}.analytics__grid--2,.analytics__grid--3,.analytics__grid--admin-detail{grid-template-columns:1fr}.analytics__grid--3>.panel:last-child{grid-column:auto}.legend{display:none}}
@media(max-width:600px){.kpi-grid{grid-template-columns:1fr}.analytics__header h2{font-size:1.3rem}.kpi{min-height:112px}.donut-layout{align-items:flex-start}.donut{flex-basis:108px;width:108px;height:108px}.panel{padding:14px}.horizontal-bars>div{grid-template-columns:92px 1fr 65px}.client-stats{grid-template-columns:1fr}}
</style>
