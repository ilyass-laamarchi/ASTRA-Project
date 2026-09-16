<!-- Public ASTRA homepage with premium hero, fleet highlights, trust content, and navigation. -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../lib/api'
import AstraLogo from '../components/AstraLogo.vue'
import {
  Search, MapPin, ChevronRight, ChevronLeft, ChevronDown,
  Star, Phone, Mail, MessageCircle, Plus, Minus,
  Fuel, Settings2, Users, Gauge, ArrowRight,
  Menu, X, MousePointerClick, CalendarCheck,
  Diamond, ShieldCheck, ConciergeBell, Truck, Headphones, Calendar, Car, UserRound
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const allCars = ref([])
const fleetTotal = ref(0)
const loading = ref(true)
const fleetError = ref('')
const activeCategory = ref('all')
const categories = ref([])
const currentSlide = ref(0)
const openFaq = ref(null)
const mobileNav = ref(false)
const searchCity = ref('')
const heroStartDate = ref('')
const heroEndDate = ref('')

const faqs = [
  { q: 'Quels documents sont nécessaires pour louer un véhicule ?', a: "Une pièce d'identité en cours de validité, un permis de conduire valide depuis plus de 2 ans, et une carte bancaire au nom du conducteur principal pour la caution." },
  { q: "L'assurance est-elle incluse dans le tarif de location ?", a: "Oui, une assurance tous risques avec franchise est incluse dans toutes nos locations. Vous pouvez opter pour un rachat de franchise lors de la réservation." },
  { q: 'Comment fonctionne le processus de paiement ?', a: "Le paiement est sécurisé via Stripe. Le montant de la location est débité lors de la réservation, et la caution est bloquée (non débitée) avant la remise des clés." },
  { q: 'Puis-je annuler ou modifier ma réservation ?', a: "Oui, l'annulation est gratuite jusqu'à 48h avant le début de la location. Au-delà, des frais peuvent s'appliquer selon nos conditions générales." },
  { q: 'Proposez-vous la livraison du véhicule ?', a: "Oui, nous proposons un service de livraison et de récupération à l'adresse de votre choix dans un rayon de 30 km, sous conditions." },
]

const testimonials = [
  { text: "Un service d'exception. Le véhicule était en parfait état et le processus de location incroyablement fluide. ASTRA est désormais mon premier choix.", author: 'Karim B.', role: 'Client régulier', rating: 5 },
  { text: "J'ai loué une Tesla Model 3 pour un week-end. Expérience inoubliable du début à la fin. L'équipe est réactive et professionnelle.", author: 'Sophie M.', role: 'Première location', rating: 5 },
]

const gaugeNumbers = [0, 20, 40, 60, 80, 100, 120, 140, 160, 180, 200]
const luxuryStats = computed(() => [
  { val: fleetTotal.value || '—', label: 'Véhicules' },
  { val: categories.value.length || '—', label: 'Catégories' },
  { val: '24/7', label: 'Réservation' },
  { val: 'MAD', label: 'Tarifs clairs' },
  { val: 'Web', label: 'Parcours en ligne' },
])

onMounted(async () => {
  try {
    const [response, categoryResponse] = await Promise.all([api.get('/cars'), api.get('/categories')])
    const data = response.data.data || response.data
    allCars.value = Array.isArray(data) ? data : []
    fleetTotal.value = Number(response.meta?.total || allCars.value.length)
    const categoryData = categoryResponse.data?.data || categoryResponse.data || []
    categories.value = Array.isArray(categoryData) ? categoryData.map(category => category.name) : []
  } catch {
    fleetError.value = 'La flotte est momentanément indisponible.'
  } finally {
    loading.value = false
  }
})

const filteredCars = computed(() => {
  const source = activeCategory.value === 'all'
    ? allCars.value
    : allCars.value.filter(c => c.category?.name === activeCategory.value)
  return source.slice(0, 3)
})

const topPicks = computed(() => allCars.value.slice(0, 4))

/** Opens one FAQ answer and closes the previously open item. */
function toggleFaq(i) { openFaq.value = openFaq.value === i ? null : i }
/** Advances the featured-vehicle carousel with wraparound. */
function nextSlide() { currentSlide.value = (currentSlide.value + 1) % Math.max(topPicks.value.length, 1) }
/** Moves the featured-vehicle carousel backward with wraparound. */
function prevSlide() { currentSlide.value = (currentSlide.value - 1 + topPicks.value.length) % Math.max(topPicks.value.length, 1) }
</script>

<template>
  <div class="dd-page">

    <!-- ═══════════════ NAVBAR ═══════════════ -->
    <nav class="dd-nav">
      <div class="dd-nav__inner">
        <RouterLink to="/" class="dd-nav__logo">
          <AstraLogo variant="inverse" class="dd-nav__logo-img" />
        </RouterLink>

        <div class="dd-nav__links">
          <RouterLink to="/" class="dd-nav__link dd-nav__link--active">Accueil</RouterLink>
          <RouterLink to="/cars" class="dd-nav__link">Véhicules</RouterLink>
          <RouterLink to="/about" class="dd-nav__link">À propos</RouterLink>
          <RouterLink to="/contact" class="dd-nav__link">Contact</RouterLink>
        </div>

        <div class="dd-nav__actions">
          <RouterLink
            :to="auth.isAuthenticated ? `/${auth.user.role}/dashboard` : '/login'"
            class="dd-nav__account"
          >
            <UserRound :size="22" :stroke-width="1.6" aria-hidden="true" />
            <span>Mon espace</span>
          </RouterLink>
        </div>

        <button type="button" class="dd-nav__burger" :aria-label="mobileNav ? 'Fermer le menu' : 'Ouvrir le menu'" aria-controls="mobile-navigation" :aria-expanded="mobileNav" @click="mobileNav = !mobileNav">
          <X v-if="mobileNav" :size="24" />
          <Menu v-else :size="24" />
        </button>
      </div>

      <!-- Mobile menu -->
      <div v-if="mobileNav" id="mobile-navigation" class="dd-nav__mobile" @click="mobileNav = false">
        <RouterLink to="/" class="dd-nav__mobile-link">Accueil</RouterLink>
        <RouterLink to="/cars" class="dd-nav__mobile-link">Véhicules</RouterLink>
        <RouterLink to="/about" class="dd-nav__mobile-link">À propos</RouterLink>
        <RouterLink to="/contact" class="dd-nav__mobile-link">Contact</RouterLink>
        <template v-if="!auth.isAuthenticated">
          <RouterLink to="/login" class="dd-nav__mobile-link">Connexion</RouterLink>
          <RouterLink to="/register" class="dd-nav__mobile-link dd-nav__mobile-link--cta">S'inscrire</RouterLink>
        </template>
        <template v-else>
          <RouterLink :to="`/${auth.user.role}/dashboard`" class="dd-nav__mobile-link dd-nav__mobile-link--cta">Mon Espace</RouterLink>
        </template>
      </div>
    </nav>

    <!-- ═══════════════ HERO ═══════════════ -->
    <section class="dd-hero" aria-label="Section principale ASTRA">

      <!-- Luxury architectural background (includes car) -->
      <div class="dd-hero__bg">
        <img
          src="/assets/images/astra-background-2560.jpg"
          srcset="/assets/images/astra-background-1280.jpg 1280w, /assets/images/astra-background-2560.jpg 2560w"
          sizes="100vw"
          width="2560"
          height="1440"
          fetchpriority="high"
          decoding="async"
          alt="ASTRA Location Premium — voiture de luxe face à la Méditerranée"
          class="dd-hero__bg-img"
        />
        <div class="dd-hero__bg-overlay"></div>
      </div>

      <!-- Main content wrapper -->
      <div class="dd-hero__inner">

        <!-- LEFT: Marketing content -->
        <div class="dd-hero__left">
          <span class="dd-hero__eyebrow">ASTRA LOCATION PREMIUM</span>

          <h1 class="dd-hero__title">
            <span class="dd-hero__title-line">L'excellence</span>
            <span class="dd-hero__title-line dd-hero__title-line--wide">à chaque kilomètre<span class="dd-hero__title-dot">.</span></span>
          </h1>

          <p class="dd-hero__sub">
            Des véhicules d'exception, un service sur mesure<br/>
            et une expérience pensée pour dépasser vos attentes.
          </p>

          <!-- 3 Premium highlights -->
          <div class="dd-hero__highlights">
            <div class="dd-hero__highlight">
              <div class="dd-hero__highlight-icon" aria-hidden="true">
                <Diamond :size="28" stroke-width="1.75" />
              </div>
              <div class="dd-hero__highlight-text">
                <span class="dd-hero__highlight-title">Véhicules</span>
                <span class="dd-hero__highlight-sub">Premium</span>
              </div>
            </div>
            <div class="dd-hero__highlight">
              <div class="dd-hero__highlight-icon" aria-hidden="true">
                <ShieldCheck :size="28" stroke-width="1.75" />
              </div>
              <div class="dd-hero__highlight-text">
                <span class="dd-hero__highlight-title">Assurance</span>
                <span class="dd-hero__highlight-sub">Tous risques</span>
              </div>
            </div>
            <div class="dd-hero__highlight">
              <div class="dd-hero__highlight-icon" aria-hidden="true">
                <ConciergeBell :size="28" stroke-width="1.75" />
              </div>
              <div class="dd-hero__highlight-text">
                <span class="dd-hero__highlight-title">Conciergerie</span>
                <span class="dd-hero__highlight-sub">Sur mesure</span>
              </div>
            </div>
          </div>

        <!-- ── Search directly below the premium benefits ── -->
        <div class="dd-hero__searchbar-wrap">
        <div class="dd-hero__searchbar" role="search">
          <!-- Field 1: Marque / Modèle -->
          <div class="dd-hero__sfield">
            <div class="dd-hero__sfield-head">
              <Car :size="16" stroke-width="2" />
              <div class="dd-hero__sfield-label">Marque / Modèle</div>
            </div>
            <div class="dd-hero__sfield-input-wrap">
              <input
                id="hero-search-model"
                v-model="searchCity"
                type="search"
                class="dd-hero__sfield-input"
                placeholder="Ex : Lamborghini Urus"
                @keyup.enter="router.push({path:'/cars',query:{search:searchCity,start_date:heroStartDate,end_date:heroEndDate}})"
              />
              <ChevronDown :size="15" stroke-width="2" class="dd-hero__sfield-chevron" />
            </div>
          </div>

          <div class="dd-hero__sdivider"></div>

          <!-- Field 2: Date début -->
          <div class="dd-hero__sfield">
            <div class="dd-hero__sfield-head">
              <Calendar :size="16" stroke-width="2" />
              <div class="dd-hero__sfield-label">Date début</div>
            </div>
            <div class="dd-hero__sfield-input-wrap">
              <input
                id="hero-start-date"
                v-model="heroStartDate"
                type="text"
                onfocus="(this.type='date')"
                onblur="(this.value==''?this.type='text':this.type='date')"
                class="dd-hero__sfield-input"
                :min="new Date().toISOString().split('T')[0]"
                placeholder="Sélectionner"
              />
              <ChevronDown :size="15" stroke-width="2" class="dd-hero__sfield-chevron" />
            </div>
          </div>

          <div class="dd-hero__sdivider"></div>

          <!-- Field 3: Date fin -->
          <div class="dd-hero__sfield">
            <div class="dd-hero__sfield-head">
              <Calendar :size="16" stroke-width="2" />
              <div class="dd-hero__sfield-label">Date fin</div>
            </div>
            <div class="dd-hero__sfield-input-wrap">
              <input
                id="hero-end-date"
                v-model="heroEndDate"
                type="text"
                onfocus="(this.type='date')"
                onblur="(this.value==''?this.type='text':this.type='date')"
                class="dd-hero__sfield-input"
                :min="heroStartDate || new Date().toISOString().split('T')[0]"
                placeholder="Sélectionner"
              />
              <ChevronDown :size="15" stroke-width="2" class="dd-hero__sfield-chevron" />
            </div>
          </div>

          <!-- Search button -->
          <button class="dd-hero__sbtn" @click="router.push({path:'/cars',query:{search:searchCity,start_date:heroStartDate,end_date:heroEndDate}})">
            <Search :size="16" stroke-width="2.5" /> Rechercher
          </button>
        </div>

        <div class="dd-hero__trust">
          <ShieldCheck :size="18" stroke-width="2" />
          <div class="dd-hero__trust-text">
            <strong>Confiance &amp; sécurité</strong><br/>
            Paiement sécurisé. Données protégées.
          </div>
        </div>
        </div>

        </div><!-- /left -->

      <!-- RIGHT: Car showcase + floating cards -->
      <div class="dd-hero__right">

        <!-- TOP-RIGHT cards -->
        <div class="dd-hero__card dd-hero__card--c1" style="--delay:0s" aria-hidden="true">
          <div class="dd-hero__card-icon"><Calendar :size="15" /></div>
          <div class="dd-hero__card-content">
            <span class="dd-hero__card-label">Disponibilité</span>
            <span class="dd-hero__card-value"><span class="dd-hero__card-dot"></span>Élevée</span>
          </div>
        </div>

        <div class="dd-hero__card dd-hero__card--c2" style="--delay:0.5s" aria-hidden="true">
          <div class="dd-hero__card-icon"><Star :size="15" /></div>
          <div class="dd-hero__card-content">
            <span class="dd-hero__card-label">Note clients</span>
            <span class="dd-hero__card-value dd-hero__card-value--gold">4,9/5</span>
            <span class="dd-hero__card-meta">+200 avis vérifiés</span>
          </div>
        </div>

        <!-- Car is baked into the background image, no need for separate img tag -->

        <!-- BOTTOM-RIGHT cards -->
        <div class="dd-hero__card dd-hero__card--c3" style="--delay:1.1s" aria-hidden="true">
          <div class="dd-hero__card-icon"><Truck :size="15" /></div>
          <div class="dd-hero__card-content">
            <span class="dd-hero__card-label">Livraison premium</span>
            <span class="dd-hero__card-meta">À votre hôtel ou aéroport</span>
          </div>
        </div>

        <div class="dd-hero__card dd-hero__card--c4" style="--delay:1.6s" aria-hidden="true">
          <div class="dd-hero__card-icon"><Headphones :size="15" /></div>
          <div class="dd-hero__card-content">
            <span class="dd-hero__card-label">Assistance 24/7</span>
            <span class="dd-hero__card-meta">Un expert à votre écoute<br/>à tout moment.</span>
          </div>
        </div>

      </div><!-- /right -->
    </div><!-- /inner -->

    </section>

    <!-- ═══════════════ DRIVE LUXURY LIVE FREEDOM ═══════════════ -->
    <section class="dd-luxury">
      <div class="dd-luxury__inner">
        <div class="dd-luxury__top">
          <div class="dd-luxury__heading">
            <h2>Roulez Premium.<br/>Vivez Libre.</h2>
          </div>
          <div class="dd-luxury__desc">
            <p>
              Explorez notre collection de véhicules premium soigneusement sélectionnés pour répondre à vos exigences les plus élevées. Du confort urbain à la route ouverte, chaque trajet devient une expérience d'exception.
            </p>
            <div class="dd-luxury__thumbs">
              <img src="/assets/images/fleet-sedan-480.webp" alt="Berline" width="480" height="278" loading="lazy" decoding="async" class="dd-luxury__thumb" />
              <img src="/assets/images/fleet-suv-480.webp" alt="SUV" width="480" height="320" loading="lazy" decoding="async" class="dd-luxury__thumb" />
            </div>
          </div>
        </div>

        <div class="dd-luxury__stats">
          <div class="dd-luxury__stat" v-for="s in luxuryStats" :key="s.label">
            <span class="dd-luxury__stat-val">{{ s.val }}</span>
            <span class="dd-luxury__stat-label">{{ s.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════ FIND YOUR PERFECT RIDE ═══════════════ -->
    <section class="dd-fleet">
      <div class="dd-fleet__inner">
        <div class="dd-fleet__header">
          <h2>Trouvez Votre Véhicule Idéal</h2>
          <p>Parcourez notre sélection et réservez en quelques instants.</p>
        </div>

        <div class="dd-fleet__filters">
          <button type="button" :class="['dd-fleet__filter', { 'dd-fleet__filter--active': activeCategory === 'all' }]"
                  @click="activeCategory = 'all'">Tous</button>
          <button v-for="cat in categories" :key="cat" type="button"
                  :class="['dd-fleet__filter', { 'dd-fleet__filter--active': activeCategory === cat }]"
                  @click="activeCategory = cat">{{ cat }}</button>
        </div>

        <div v-if="loading" class="dd-fleet__grid">
          <div v-for="i in 3" :key="i" class="dd-fleet__card dd-fleet__card--skeleton"></div>
        </div>

        <p v-else-if="fleetError" role="alert" class="py-10 text-center font-bold text-red-700">{{ fleetError }}</p>

        <div v-else class="dd-fleet__grid">
          <RouterLink v-for="car in filteredCars" :key="car.id" :to="`/cars/${car.id}`" class="dd-fleet__card">
            <div class="dd-fleet__card-img">
              <img :src="car.images?.[0]?.path || '/assets/images/fleet-sedan-900.webp'" :alt="`${car.brand} ${car.model}`" width="900" height="600" loading="lazy" decoding="async" />
            </div>
            <div class="dd-fleet__card-body">
              <h3 class="dd-fleet__card-name">{{ car.brand }} {{ car.model }}</h3>
              <div class="dd-fleet__card-specs">
                <span><Fuel :size="14" /> {{ car.fuel_type === 'electric' ? 'Électrique' : car.fuel_type === 'hybrid' ? 'Hybride' : car.fuel_type === 'diesel' ? 'Diesel' : 'Essence' }}</span>
                <span><Settings2 :size="14" /> {{ car.transmission === 'automatic' ? 'Auto' : 'Manuel' }}</span>
                <span><Users :size="14" /> {{ car.seats }} places</span>
                <span><Gauge :size="14" /> {{ car.year }}</span>
              </div>
              <div class="dd-fleet__card-footer">
                <div class="dd-fleet__card-price">
                  <span class="dd-fleet__card-amount">{{ Number(car.daily_price).toLocaleString('fr-MA') }} MAD</span>
                  <span class="dd-fleet__card-per">/jour</span>
                </div>
                <span class="dd-fleet__card-cta">Réserver <ArrowRight :size="14" /></span>
              </div>
            </div>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ═══════════════ LUXURY MEETS RELIABILITY ═══════════════ -->
    <section class="dd-gauge">
      <div class="dd-gauge__inner">
        <div class="dd-gauge__main">
          <div class="dd-gauge__copy">
            <span class="dd-gauge__eyebrow"><i></i> L'expérience ASTRA</span>
            <h2>Le Luxe Rencontre<br/>la Fiabilité</h2>
            <p>Une flotte choisie avec exigence, entretenue avec précision et disponible en quelques clics.</p>
            <RouterLink to="/cars" class="dd-gauge__link">Explorer la flotte <ArrowRight :size="17" /></RouterLink>
          </div>

          <div class="dd-gauge__visual" aria-label="Tableau de bord digital ASTRA">
            <div class="dd-gauge__glow"></div>
            <svg class="dd-gauge__svg" viewBox="0 0 400 220" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M 40 200 A 160 160 0 0 1 360 200" stroke="rgba(255,255,255,0.16)" stroke-width="3" fill="none" />
              <line v-for="i in 11" :key="i"
                :x1="200 + 150 * Math.cos(Math.PI + (i-1) * Math.PI / 10)"
                :y1="200 + 150 * Math.sin(Math.PI + (i-1) * Math.PI / 10)"
                :x2="200 + 140 * Math.cos(Math.PI + (i-1) * Math.PI / 10)"
                :y2="200 + 140 * Math.sin(Math.PI + (i-1) * Math.PI / 10)"
                stroke="rgba(255,255,255,0.24)" stroke-width="2" />
              <text v-for="(n, i) in gaugeNumbers" :key="'n'+i"
                :x="200 + 125 * Math.cos(Math.PI + i * Math.PI / 10)"
                :y="200 + 125 * Math.sin(Math.PI + i * Math.PI / 10)"
                text-anchor="middle" dominant-baseline="middle"
                fill="rgba(255,255,255,0.48)" font-size="10" font-weight="600">{{ n }}</text>
              <path d="M 40 200 A 160 160 0 0 1 280 52" stroke="#ffd100" stroke-width="5" fill="none" stroke-linecap="round" />
              <line x1="200" y1="200" x2="280" y2="60" stroke="#fff" stroke-width="3" stroke-linecap="round" />
              <circle cx="200" cy="200" r="9" fill="#ffd100" />
              <circle cx="200" cy="200" r="4" fill="#111" />
            </svg>
            <div class="dd-gauge__car">
              <img src="/assets/images/gauge-evoque-1100.webp" alt="Véhicule premium de la flotte ASTRA" width="1100" height="733" loading="lazy" decoding="async" />
            </div>
            <div class="dd-gauge__metric dd-gauge__metric--left">
              <strong>{{ fleetTotal || '12' }}</strong><span>véhicules</span>
            </div>
            <div class="dd-gauge__metric dd-gauge__metric--right">
              <strong>24/7</strong><span>en ligne</span>
            </div>
          </div>
        </div>
        <div class="dd-gauge__bottom">
          <div><span>01</span><strong>Véhicules contrôlés</strong><small>Qualité vérifiée avant chaque départ</small></div>
          <div><span>02</span><strong>Tarifs transparents</strong><small>Le bon prix, sans mauvaise surprise</small></div>
          <div><span>03</span><strong>Réservation rapide</strong><small>Votre voiture en quelques clics</small></div>
        </div>
      </div>
    </section>

    <!-- ═══════════════ TOP PICKS ═══════════════ -->
    <section class="dd-picks">
      <div class="dd-picks__inner">
        <div class="dd-picks__header">
          <h2>Sélection de la Semaine</h2>
          <div class="dd-picks__nav">
            <button type="button" class="dd-picks__arrow" aria-label="Véhicule précédent" @click="prevSlide"><ChevronLeft :size="20" /></button>
            <button type="button" class="dd-picks__arrow" aria-label="Véhicule suivant" @click="nextSlide"><ChevronRight :size="20" /></button>
          </div>
        </div>

        <div class="dd-picks__track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
          <div v-for="car in topPicks" :key="car.id" class="dd-picks__slide">
            <RouterLink :to="`/cars/${car.id}`" class="dd-picks__card">
              <img :src="car.images?.[0]?.path || '/assets/images/fleet-sedan-900.webp'" :alt="`${car.brand} ${car.model}`" width="900" height="600" loading="lazy" decoding="async" />
              <div class="dd-picks__card-info">
                <h3>{{ car.brand }} {{ car.model }}</h3>
                <p>{{ Number(car.daily_price).toLocaleString('fr-MA') }} MAD <span>/jour</span></p>
              </div>
            </RouterLink>
          </div>
        </div>

        <div class="dd-picks__dots">
          <button v-for="(_, i) in topPicks" :key="i" type="button" :aria-label="`Afficher le véhicule ${i + 1}`" :aria-pressed="currentSlide === i"
                :class="['dd-picks__dot', { 'dd-picks__dot--active': currentSlide === i }]"
                @click="currentSlide = i"></button>
        </div>
      </div>
    </section>

    <!-- ═══════════════ SIMPLE FAST HASSLE-FREE ═══════════════ -->
    <section class="dd-steps">
      <div class="dd-steps__inner">
        <div class="dd-steps__header">
          <h2>Simple, Rapide, Sans Tracas</h2>
          <p>En trois étapes, prenez le volant de votre prochain véhicule premium.</p>
        </div>

        <div class="dd-steps__grid">
          <div class="dd-steps__card">
            <div class="dd-steps__icon">
              <MousePointerClick :size="32" />
            </div>
            <h3>Faites Votre Choix</h3>
            <p>Ajoutez vos préférences et explorez notre flotte de véhicules premium.</p>
          </div>
          <div class="dd-steps__card">
            <div class="dd-steps__icon">
              <CalendarCheck :size="32" />
            </div>
            <h3>Confirmez</h3>
            <p>Sélectionnez vos dates, confirmez votre réservation en ligne.</p>
          </div>
          <div class="dd-steps__card dd-steps__card--img">
            <img src="/assets/images/fleet-sedan-900.webp" alt="Berline premium ASTRA" width="900" height="522" loading="lazy" decoding="async" />
            <div class="dd-steps__card-overlay">
              <h3>Profitez du Trajet</h3>
              <p>Récupérez le véhicule et savourez l'expérience ASTRA.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════ TRUSTED BY THOUSANDS ═══════════════ -->
    <section class="dd-trust">
      <div class="dd-trust__inner">
        <div class="dd-trust__header">
          <h2>La Confiance de Nos Clients</h2>
          <p>Découvrez pourquoi nos clients nous recommandent à chaque occasion.</p>
        </div>

        <div class="dd-trust__grid">
          <div v-for="(t, i) in testimonials" :key="i" class="dd-trust__card">
            <div class="dd-trust__stars">
              <Star v-for="s in t.rating" :key="s" :size="16" fill="#111" stroke="#111" />
            </div>
            <p class="dd-trust__quote">"{{ t.text }}"</p>
            <div class="dd-trust__author">
              <div class="dd-trust__avatar">{{ t.author.charAt(0) }}</div>
              <div>
                <strong>{{ t.author }}</strong>
                <span>{{ t.role }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════ FAQ ═══════════════ -->
    <section class="dd-faq">
      <div class="dd-faq__inner">
        <div class="dd-faq__left">
          <h2>Des questions ?<br/>On a les réponses !</h2>
          <div class="dd-faq__contact-card">
            <h4>Besoin d'aide ?</h4>
            <a href="tel:+212600000001" class="dd-faq__contact-row"><Phone :size="16" /> +212 600 000 001</a>
            <a href="mailto:contact@astra.ma" class="dd-faq__contact-row"><Mail :size="16" /> contact@astra.ma</a>
            <RouterLink to="/contact" class="dd-faq__contact-row"><MessageCircle :size="16" /> Nous écrire</RouterLink>
          </div>
        </div>

        <div class="dd-faq__right">
          <div v-for="(faq, i) in faqs" :key="i" class="dd-faq__item" :class="{ 'dd-faq__item--open': openFaq === i }">
            <button type="button" class="dd-faq__q" :aria-expanded="openFaq === i" :aria-controls="`faq-answer-${i}`" @click="toggleFaq(i)">
              <span>{{ faq.q }}</span>
              <Minus v-if="openFaq === i" :size="18" />
              <Plus v-else :size="18" />
            </button>
            <div v-if="openFaq === i" :id="`faq-answer-${i}`" class="dd-faq__a" role="region">
              <p>{{ faq.a }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════ FOOTER ═══════════════ -->
    <footer class="dd-footer">
      <div class="dd-footer__inner">
        <div class="dd-footer__top">
          <div class="dd-footer__brand">
            <AstraLogo variant="inverse" class="dd-footer__logo" loading="lazy" />
            <p>Mobilité premium, service d'exception et confiance pour chaque trajet.</p>
          </div>
          <div class="dd-footer__col">
            <h4>Pages</h4>
            <RouterLink to="/">Accueil</RouterLink>
            <RouterLink to="/cars">Véhicules</RouterLink>
            <RouterLink to="/about">À propos</RouterLink>
            <RouterLink to="/contact">Contact</RouterLink>
          </div>
          <div class="dd-footer__col">
            <h4>Espace Client</h4>
            <RouterLink to="/login">Connexion</RouterLink>
            <RouterLink to="/register">Inscription</RouterLink>
          </div>
          <div class="dd-footer__col"><h4>Nous contacter</h4><RouterLink to="/contact">Formulaire de contact</RouterLink><a href="mailto:contact@astra.ma">contact@astra.ma</a><a href="tel:+212539000000">+212 5 39 00 00 00</a><span>Tanger, Maroc</span></div>
        </div>
        <div class="dd-footer__bottom">
          <span>© {{ new Date().getFullYear() }} ASTRA Premium. Tous droits réservés.</span>
          <div class="dd-footer__legal">
            <RouterLink to="/privacy">Confidentialité</RouterLink>
            <RouterLink to="/legal">Mentions légales</RouterLink>
          </div>
        </div>
      </div>
    </footer>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&display=swap');
/* ============================
   GLOBAL PAGE
   ============================ */
.dd-page {
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #111;
  background: #fff;
  overflow-x: hidden;
}

.dd-fleet,
.dd-gauge,
.dd-picks,
.dd-steps,
.dd-trust,
.dd-faq,
.dd-footer {
  content-visibility: auto;
  contain-intrinsic-size: auto 760px;
}

.dd-page :focus-visible {
  outline: 3px solid #f5c400;
  outline-offset: 3px;
}

/* ============================
   NAVBAR
   ============================ */
.dd-nav {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  background: transparent !important;
  border: none !important;
}
.dd-nav__inner {
  max-width: 100%;
  width: 100%;
  margin: 0;
  padding: 0 2vw 0 3.75vw;
  height: 110px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.dd-nav__logo {
  position: relative;
  display: inline-block;
  line-height: 0;
}
.dd-nav__logo img {
  height: 52px;
  width: auto;
  object-fit: contain;
  display: block;
  filter: drop-shadow(0 2px 8px rgba(5, 28, 67, 0.28));
  opacity: 0.98;
}
.dd-nav__links {
  display: flex;
  gap: 3.5rem;
}
.dd-nav__link {
  font-size: 1.05rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.94);
  text-decoration: none;
  transition: color 0.2s;
  position: relative;
  padding-bottom: 2px;
  text-shadow: 0 2px 10px rgba(5, 28, 67, 0.24);
}
.dd-nav__link:hover,
.dd-nav__link--active {
  color: #fff;
}
/* Underline accent on active link */
.dd-nav__link--active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 2px;
  background: #42c9ff;
  box-shadow: 0 0 8px rgba(66, 201, 255, 0.4);
  border-radius: 2px;
}
.dd-nav__actions {
  display: flex;
  align-items: center;
}
.dd-nav__account {
  min-height: 54px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  padding: 0.75rem 1.45rem;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.1);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), 0 10px 30px rgba(4, 29, 72, 0.12);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  color: #fff;
  font-size: 0.83rem;
  font-weight: 600;
  letter-spacing: 0.025em;
  text-transform: uppercase;
  text-decoration: none;
  transition: background 0.2s, border-color 0.2s, transform 0.2s, box-shadow 0.2s;
}
.dd-nav__account svg {
  color: #4cc9ff;
  filter: drop-shadow(0 0 8px rgba(76, 201, 255, 0.35));
}
.dd-nav__account:hover {
  background: rgba(255, 255, 255, 0.17);
  border-color: rgba(255, 255, 255, 0.38);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 12px 34px rgba(4, 29, 72, 0.2);
  transform: translateY(-1px);
}
.dd-nav__burger {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
}
.dd-nav__mobile {
  display: none;
  flex-direction: column;
  padding: 1rem 2rem 2rem;
  border-top: 1px solid #f0f0f0;
}
.dd-nav__mobile-link {
  padding: 0.75rem 0;
  font-weight: 600;
  color: #111;
  text-decoration: none;
  border-bottom: 1px solid #f5f5f5;
}
.dd-nav__mobile-link--cta {
  margin-top: 0.5rem;
  background: #111;
  color: #fff;
  text-align: center;
  padding: 0.75rem;
  border-radius: 100px;
  border-bottom: none;
}

@media (max-width: 768px) {
  .dd-nav {
    position: absolute;
  }
  .dd-nav__inner {
    padding: 0.6rem 1.25rem;
    height: 70px;
  }
  .dd-nav__links, .dd-nav__actions { display: none; }
  .dd-nav__burger { display: flex; align-items: center; justify-content: center; color: #1a1208; }
  .dd-nav__mobile {
    display: flex;
    position: absolute;
    top: 70px;
    left: 0;
    right: 0;
    background: rgba(245, 237, 222, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    padding: 1.25rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    z-index: 101;
  }
}

/* ============================
   HERO
   ============================ */
@keyframes hero-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-5px); }
}

.dd-hero {
  position: relative;
  height: 100svh;
  min-height: 760px;
  overflow: hidden;
  background: #f4eadc;
}

.dd-hero__bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
}
.dd-hero__bg-img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}
.dd-hero__bg-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(4,24,58,0.34) 0%, rgba(4,24,58,0.18) 35%, transparent 58%);
  pointer-events: none;
}

.dd-hero__inner {
  position: relative;
  z-index: 2;
  width: 100%;
  height: 100%;
}

.dd-hero__left {
  position: absolute;
  top: 21.5%;
  left: 4.9vw;
  width: min(690px, 52vw);
}
.dd-hero__eyebrow {
  display: block;
  margin-bottom: 18px;
  color: #56d3ff;
  font-size: 0.94rem;
  font-weight: 700;
  letter-spacing: 0.015em;
  text-shadow: 0 2px 12px rgba(1, 17, 47, 0.5);
}
.dd-hero__title {
  margin: 0;
  color: #fff;
  font-family: 'Playfair Display', Georgia, serif;
  font-size: clamp(4rem, 4.6vw, 5rem);
  font-weight: 600;
  line-height: 1.03;
  letter-spacing: -0.035em;
  white-space: nowrap;
  text-shadow: 0 4px 22px rgba(1, 17, 47, 0.42);
}
.dd-hero__title-dot { color: #56d3ff; }
.dd-hero__title-line { display: block; }
.dd-hero__title-line--wide { letter-spacing: 0; }
.dd-hero__sub {
  max-width: 630px;
  margin: 26px 0 0;
  color: rgba(255, 255, 255, 0.92);
  font-size: 1.2rem;
  font-weight: 400;
  line-height: 1.48;
  text-shadow: 0 2px 12px rgba(1, 17, 47, 0.42);
}
.dd-hero__highlights {
  display: flex;
  align-items: center;
  gap: 0;
  margin-top: 46px;
}
.dd-hero__highlight {
  position: relative;
  display: flex;
  align-items: center;
  min-width: 180px;
  gap: 14px;
}
.dd-hero__highlight + .dd-hero__highlight {
  margin-left: 4px;
  padding-left: 32px;
}
.dd-hero__highlight + .dd-hero__highlight::before {
  content: '';
  position: absolute;
  left: 0;
  width: 1px;
  height: 42px;
  background: rgba(255,255,255,0.25);
}
.dd-hero__highlight-icon {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  color: #56d3ff;
  filter: drop-shadow(0 2px 8px rgba(1, 17, 47, 0.35));
}
.dd-hero__highlight-icon svg { width: 31px; height: 31px; }
.dd-hero__highlight-text {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.dd-hero__highlight-title {
  color: #fff;
  font-size: 0.83rem;
  font-weight: 750;
  line-height: 1.2;
  text-shadow: 0 2px 9px rgba(1, 17, 47, 0.48);
}
.dd-hero__highlight-sub {
  color: rgba(218, 244, 255, 0.9);
  font-size: 0.77rem;
  line-height: 1.2;
  text-shadow: 0 2px 9px rgba(1, 17, 47, 0.48);
}

.dd-hero__searchbar-wrap {
  position: relative;
  z-index: 12;
  left: auto;
  bottom: auto;
  width: min(945px, 62vw);
  margin-top: 30px;
}
.dd-hero__searchbar {
  display: flex;
  align-items: center;
  width: 100%;
  min-height: 92px;
  padding: 11px 13px;
  background: rgba(255,255,255,0.94);
  border: 1px solid rgba(255,255,255,0.88);
  border-radius: 20px;
  box-shadow: 0 12px 36px rgba(37,28,16,0.10);
  backdrop-filter: blur(9px);
  -webkit-backdrop-filter: blur(9px);
}
.dd-hero__sfield {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
  gap: 7px;
  padding: 8px 20px;
}
.dd-hero__sfield:nth-of-type(1) { flex: 1.34; }
.dd-hero__sfield:nth-of-type(2),
.dd-hero__sfield:nth-of-type(3) { flex: 1; }
.dd-hero__sfield-head {
  display: flex;
  align-items: center;
  gap: 12px;
  color: #15213a;
}
.dd-hero__sfield-head svg {
  flex: 0 0 auto;
  width: 20px;
  height: 20px;
  color: #3c4a63;
}
.dd-hero__sfield-label {
  font-size: 0.79rem;
  font-weight: 750;
}
.dd-hero__sfield-input-wrap {
  position: relative;
  width: 100%;
  padding-left: 32px;
}
.dd-hero__sfield-input {
  width: 100%;
  padding: 0 20px 0 0;
  border: 0;
  outline: 0;
  color: #657088;
  background: transparent;
  font-family: inherit;
  font-size: 0.74rem;
  cursor: pointer;
}
.dd-hero__sfield-input::placeholder { color: #7d8798; opacity: 1; }
.dd-hero__sfield-input::-webkit-calendar-picker-indicator {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}
.dd-hero__sfield-chevron {
  position: absolute;
  top: 50%;
  right: 0;
  width: 15px;
  height: 15px;
  color: #2f3d55;
  transform: translateY(-50%);
  pointer-events: none;
}
.dd-hero__sdivider {
  flex: 0 0 1px;
  width: 1px;
  height: 52px;
  background: rgba(10,23,49,0.09);
}
.dd-hero__sbtn {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-width: 150px;
  min-height: 62px;
  margin-left: 10px;
  padding: 0 22px;
  border: 0;
  border-radius: 16px;
  color: #fff;
  background: #071228;
  font-family: inherit;
  font-size: 0.79rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}
.dd-hero__sbtn:hover { background: #14213c; transform: translateY(-1px); }
.dd-hero__trust {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  margin-top: 35px;
  margin-left: 5px;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.63rem;
  line-height: 1.35;
  text-shadow: 0 2px 9px rgba(1, 17, 47, 0.48);
}
.dd-hero__trust > svg {
  width: 27px;
  height: 27px;
  color: #fff;
  filter: drop-shadow(0 2px 8px rgba(1, 17, 47, 0.4));
}
.dd-hero__trust-text strong {
  color: #fff;
  font-size: 0.72rem;
  font-weight: 750;
}

.dd-hero__right {
  position: absolute;
  inset: 0;
  pointer-events: none;
}
.dd-hero__card {
  position: absolute;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 78px;
  padding: 14px 18px;
  color: #111b30;
  background: rgba(255,255,255,0.91);
  border: 1px solid rgba(255,255,255,0.88);
  border-radius: 17px;
  box-shadow: 0 10px 26px rgba(23,18,12,0.09);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: hero-float 7s ease-in-out infinite;
  animation-delay: var(--delay, 0s);
}
.dd-hero__card-icon {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  width: 43px;
  height: 43px;
  color: #bd8427;
  background: #f8eddb;
  border-radius: 50%;
}
.dd-hero__card-icon svg { width: 21px; height: 21px; }
.dd-hero__card-content {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 3px;
}
.dd-hero__card-label {
  color: #111b30;
  font-size: 0.75rem;
  font-weight: 750;
  line-height: 1.25;
}
.dd-hero__card-value {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #111b30;
  font-size: 0.84rem;
  font-weight: 750;
  line-height: 1.2;
}
.dd-hero__card-value--gold { color: #111b30; font-size: 1rem; }
.dd-hero__card-meta {
  color: #657088;
  font-size: 0.68rem;
  font-weight: 450;
  line-height: 1.35;
}
.dd-hero__card-dot {
  width: 7px;
  height: 7px;
  background: #35b96b;
  border-radius: 50%;
}
.dd-hero__card--c1 { top: 18.1%; right: 14.1%; width: 194px; }
.dd-hero__card--c2 { top: 30.2%; right: 4.5%; width: 216px; }
.dd-hero__card--c3 { right: 5%; bottom: 18%; width: 238px; }
.dd-hero__card--c4 { right: 5%; bottom: 8.1%; width: 238px; min-height: 90px; }

@media (max-width: 1100px) and (min-width: 769px) {
  .dd-hero__left { width: 58vw; }
  .dd-hero__title { font-size: clamp(3.1rem, 5.2vw, 3.8rem); }
  .dd-hero__sub { font-size: 1rem; }
  .dd-hero__highlight { min-width: 150px; gap: 10px; }
  .dd-hero__highlight + .dd-hero__highlight { padding-left: 18px; }
  .dd-hero__searchbar-wrap { width: 67vw; }
  .dd-hero__card--c1 { right: 4%; }
  .dd-hero__card--c2 { right: 2%; }
  .dd-hero__card--c3,
  .dd-hero__card--c4 { right: 2%; }
}

@media (max-width: 768px) {
  .dd-hero {
    height: auto;
    min-height: 0;
    background: #081b3d;
  }
  .dd-hero__bg { height: 560px; }
  .dd-hero__bg-img {
    object-position: 64% top;
  }
  .dd-hero__bg-overlay {
    background: linear-gradient(180deg, rgba(4,24,58,0.24) 0%, rgba(4,24,58,0.56) 72%, #081b3d 100%);
  }
  .dd-hero__inner {
    height: auto;
    padding: 96px 18px 28px;
  }
  .dd-hero__left {
    position: relative;
    top: auto;
    left: auto;
    width: 100%;
  }
  .dd-hero__eyebrow {
    margin-bottom: 12px;
    font-size: 0.68rem;
  }
  .dd-hero__title {
    font-size: clamp(2.35rem, 10.9vw, 3rem);
    line-height: 1.02;
    white-space: nowrap;
  }
  .dd-hero__title-line--wide { letter-spacing: -0.035em; }
  .dd-hero__sub {
    margin-top: 18px;
    font-size: 0.84rem;
    line-height: 1.5;
  }
  .dd-hero__sub br { display: none; }
  .dd-hero__highlights {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 25px;
  }
  .dd-hero__highlight {
    min-width: 0;
    gap: 7px;
  }
  .dd-hero__highlight + .dd-hero__highlight {
    margin-left: 0;
    padding-left: 0;
  }
  .dd-hero__highlight + .dd-hero__highlight::before { display: none; }
  .dd-hero__highlight-icon svg { width: 23px; height: 23px; }
  .dd-hero__highlight-title { font-size: 0.66rem; }
  .dd-hero__highlight-sub { font-size: 0.61rem; }

  .dd-hero__searchbar-wrap {
    position: relative;
    left: auto;
    bottom: auto;
    width: 100%;
    margin-top: 30px;
  }
  .dd-hero__searchbar {
    min-height: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    padding: 12px;
    border-radius: 18px;
  }
  .dd-hero__sfield,
  .dd-hero__sfield:nth-of-type(1),
  .dd-hero__sfield:nth-of-type(2),
  .dd-hero__sfield:nth-of-type(3) {
    width: 100%;
    flex: none;
    padding: 10px 8px;
  }
  .dd-hero__sfield-head { gap: 10px; }
  .dd-hero__sfield-input-wrap { padding-left: 30px; }
  .dd-hero__sdivider { display: none; }
  .dd-hero__sbtn {
    width: 100%;
    min-height: 52px;
    margin: 9px 0 0;
    border-radius: 14px;
  }
  .dd-hero__trust {
    margin: 16px 2px 0;
    font-size: 0.64rem;
  }
  .dd-hero__trust > svg { width: 24px; height: 24px; }

  .dd-hero__right {
    position: relative;
    inset: auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
    width: 100%;
    margin-top: 22px;
  }
  .dd-hero__card,
  .dd-hero__card--c1,
  .dd-hero__card--c2,
  .dd-hero__card--c3,
  .dd-hero__card--c4 {
    position: relative;
    inset: auto;
    width: auto;
    min-width: 0;
    min-height: 88px;
    padding: 11px 10px;
    gap: 9px;
    border-radius: 14px;
    animation-duration: 9s;
  }
  .dd-hero__card-icon {
    width: 34px;
    height: 34px;
  }
  .dd-hero__card-icon svg { width: 17px; height: 17px; }
  .dd-hero__card-label { font-size: 0.64rem; }
  .dd-hero__card-value { font-size: 0.72rem; }
  .dd-hero__card-value--gold { font-size: 0.82rem; }
  .dd-hero__card-meta { font-size: 0.57rem; }
}

@media (max-width: 380px) {
  .dd-hero__inner { padding-inline: 14px; }
  .dd-hero__title { font-size: 2.3rem; }
  .dd-hero__highlight-icon svg { width: 21px; height: 21px; }
  .dd-hero__highlight-title { font-size: 0.61rem; }
  .dd-hero__highlight-sub { font-size: 0.56rem; }
}

/* ============================
   DRIVE LUXURY LIVE FREEDOM
   ============================ */
.dd-luxury {
  padding: 5rem 0 0;
  border-top: 1px solid #f0f0f0;
}
.dd-luxury__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
}
.dd-luxury__top {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: start;
  margin-bottom: 4rem;
}
.dd-luxury__heading h2 {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 900;
  letter-spacing: -0.03em;
  line-height: 1.15;
}
.dd-luxury__desc p {
  font-size: 0.95rem;
  color: #6b7280;
  font-weight: 500;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}
.dd-luxury__thumbs {
  display: flex;
  gap: 1rem;
}
.dd-luxury__thumb {
  width: 120px;
  height: 70px;
  object-fit: cover;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}
.dd-luxury__stats {
  display: flex;
  justify-content: space-between;
  border-top: 1px solid #f0f0f0;
  border-bottom: 1px solid #f0f0f0;
  padding: 2rem 0;
}
.dd-luxury__stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}
.dd-luxury__stat-val {
  font-size: 1.75rem;
  font-weight: 900;
  letter-spacing: -0.02em;
}
.dd-luxury__stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

@media (max-width: 768px) {
  .dd-luxury__top { grid-template-columns: 1fr; gap: 2rem; }
  .dd-luxury__stats { flex-wrap: wrap; gap: 1.5rem; justify-content: center; }
}

/* ============================
   FLEET / FIND YOUR PERFECT RIDE
   ============================ */
.dd-fleet {
  padding: 5rem 0;
}
.dd-fleet__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
}
.dd-fleet__header {
  text-align: center;
  margin-bottom: 2rem;
}
.dd-fleet__header h2 {
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 900;
  letter-spacing: -0.03em;
  margin-bottom: 0.5rem;
}
.dd-fleet__header p {
  color: #6b7280;
  font-weight: 500;
}
.dd-fleet__filters {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
}
.dd-fleet__filter {
  padding: 0.5rem 1.25rem;
  border-radius: 100px;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  font-size: 0.8rem;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}
.dd-fleet__filter:hover {
  border-color: #111;
  color: #111;
}
.dd-fleet__filter--active {
  background: #111;
  color: #fff;
  border-color: #111;
}
.dd-fleet__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}
.dd-fleet__card {
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 1.25rem;
  overflow: hidden;
  text-decoration: none;
  color: #111;
  transition: box-shadow 0.3s, transform 0.3s;
}
.dd-fleet__card:hover {
  box-shadow: 0 8px 30px rgba(0,0,0,0.08);
  transform: translateY(-4px);
}
.dd-fleet__card--skeleton {
  height: 360px;
  background: linear-gradient(110deg, #f0f0f0 25%, #fafafa 37%, #f0f0f0 63%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
@keyframes shimmer {
  to { background-position-x: -200%; }
}
.dd-fleet__card-img {
  padding: 1.5rem 1.5rem 0;
  height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.dd-fleet__card-img img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}
.dd-fleet__card-body {
  padding: 1.25rem 1.5rem 1.5rem;
}
.dd-fleet__card-name {
  font-size: 1.1rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
}
.dd-fleet__card-specs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
.dd-fleet__card-specs span {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
}
.dd-fleet__card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid #f0f0f0;
  padding-top: 1rem;
}
.dd-fleet__card-amount {
  font-size: 1.1rem;
  font-weight: 800;
}
.dd-fleet__card-per {
  font-size: 0.75rem;
  color: #9ca3af;
  font-weight: 600;
}
.dd-fleet__card-cta {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #111;
}

@media (max-width: 768px) {
  .dd-fleet__grid { grid-template-columns: 1fr; }
}

/* ============================
   GAUGE / LUXURY MEETS RELIABILITY
   ============================ */
.dd-gauge {
  padding: clamp(4rem, 7vw, 6.5rem) 2rem;
  background: #f6f6f4;
}
.dd-gauge__inner {
  position: relative;
  max-width: 1180px;
  margin: 0 auto;
  padding: clamp(2rem, 5vw, 4.5rem);
  color: #fff;
  background:
    radial-gradient(circle at 76% 20%, rgba(255, 209, 0, 0.11), transparent 25%),
    linear-gradient(135deg, #0b0b0c 0%, #171719 58%, #0d0d0e 100%);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 2.25rem;
  overflow: hidden;
  box-shadow: 0 30px 80px rgba(15, 15, 18, 0.18);
}
.dd-gauge__inner::before {
  content: '';
  position: absolute;
  width: 260px;
  height: 260px;
  top: -140px;
  left: -100px;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 50%;
}
.dd-gauge__main {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: minmax(280px, 0.78fr) minmax(440px, 1.22fr);
  align-items: center;
  gap: clamp(2rem, 5vw, 4.5rem);
}
.dd-gauge__copy {
  text-align: left;
}
.dd-gauge__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 1.25rem;
  color: rgba(255,255,255,0.58);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}
.dd-gauge__eyebrow i {
  width: 7px;
  height: 7px;
  background: #ffd100;
  border-radius: 50%;
  box-shadow: 0 0 0 5px rgba(255,209,0,0.12);
}
.dd-gauge__copy h2 {
  margin: 0;
  font-size: clamp(2.15rem, 4.1vw, 3.65rem);
  font-weight: 900;
  line-height: 1.03;
  letter-spacing: -0.055em;
}
.dd-gauge__copy p {
  max-width: 410px;
  margin: 1.4rem 0 1.8rem;
  color: rgba(255,255,255,0.58);
  font-size: 0.95rem;
  font-weight: 500;
  line-height: 1.75;
}
.dd-gauge__visual {
  position: relative;
  width: 100%;
  max-width: 620px;
  min-height: 340px;
  margin: 0 auto;
}
.dd-gauge__glow {
  position: absolute;
  inset: 7% 7% 8%;
  background: radial-gradient(ellipse at center, rgba(255,209,0,0.16), rgba(255,255,255,0.035) 45%, transparent 72%);
  filter: blur(12px);
}
.dd-gauge__svg {
  position: relative;
  z-index: 1;
  width: 100%;
  height: auto;
  margin-top: 1.5rem;
  filter: drop-shadow(0 12px 32px rgba(0,0,0,0.3));
}
.dd-gauge__car {
  position: absolute;
  z-index: 2;
  bottom: -2%;
  left: 50%;
  transform: translateX(-50%);
  width: 89%;
}
.dd-gauge__car img {
  display: block;
  width: 100%;
  height: auto;
  object-fit: contain;
  filter: drop-shadow(0 30px 20px rgba(0,0,0,0.48));
}
.dd-gauge__metric {
  position: absolute;
  z-index: 3;
  top: 52%;
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
  padding: 0.62rem 0.8rem;
  background: rgba(18,18,20,0.7);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 0.8rem;
  backdrop-filter: blur(12px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.25);
}
.dd-gauge__metric--left { left: 1%; }
.dd-gauge__metric--right { right: 1%; top: 32%; }
.dd-gauge__metric strong {
  color: #ffd100;
  font-size: 0.95rem;
  font-weight: 900;
}
.dd-gauge__metric span {
  color: rgba(255,255,255,0.56);
  font-size: 0.66rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.dd-gauge__bottom {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  margin-top: 2.5rem;
  padding-top: 2rem;
  border-top: 1px solid rgba(255,255,255,0.1);
}
.dd-gauge__bottom > div {
  display: grid;
  grid-template-columns: auto 1fr;
  column-gap: 0.75rem;
  padding: 0 1.5rem;
}
.dd-gauge__bottom > div:first-child { padding-left: 0; }
.dd-gauge__bottom > div + div { border-left: 1px solid rgba(255,255,255,0.1); }
.dd-gauge__bottom span {
  grid-row: 1 / 3;
  color: #ffd100;
  font-size: 0.68rem;
  font-weight: 900;
  letter-spacing: 0.08em;
}
.dd-gauge__bottom strong {
  color: #fff;
  font-size: 0.8rem;
  font-weight: 800;
}
.dd-gauge__bottom small {
  margin-top: 0.3rem;
  color: rgba(255,255,255,0.42);
  font-size: 0.68rem;
  font-weight: 500;
  line-height: 1.45;
}
.dd-gauge__link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  min-height: 46px;
  padding: 0.75rem 1.15rem;
  color: #111;
  background: #ffd100;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 800;
  text-decoration: none;
  transition: transform 0.2s, background 0.2s;
}
.dd-gauge__link:hover {
  background: #ffe04a;
  transform: translateY(-2px);
}

@media (max-width: 900px) {
  .dd-gauge__main {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  .dd-gauge__copy {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
  }
  .dd-gauge__copy p { margin-left: auto; margin-right: auto; }
  .dd-gauge__visual { max-width: 620px; }
}

@media (max-width: 640px) {
  .dd-gauge { padding: 3rem 1rem; }
  .dd-gauge__inner { padding: 2rem 1.15rem 1.5rem; border-radius: 1.5rem; }
  .dd-gauge__copy h2 { font-size: clamp(2rem, 11vw, 2.75rem); }
  .dd-gauge__copy p { font-size: 0.86rem; }
  .dd-gauge__visual { min-height: 245px; }
  .dd-gauge__svg { margin-top: 0.5rem; }
  .dd-gauge__car { bottom: 1%; width: 94%; }
  .dd-gauge__metric { display: none; }
  .dd-gauge__bottom { grid-template-columns: 1fr; gap: 1rem; margin-top: 1rem; }
  .dd-gauge__bottom > div { padding: 0; }
  .dd-gauge__bottom > div + div { padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08); border-left: 0; }
}

/* ============================
   TOP PICKS
   ============================ */
.dd-picks {
  padding: 5rem 0;
}
.dd-picks__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
  overflow: hidden;
}
.dd-picks__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
.dd-picks__header h2 {
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 900;
  letter-spacing: -0.02em;
}
.dd-picks__nav {
  display: flex;
  gap: 0.5rem;
}
.dd-picks__arrow {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.dd-picks__arrow:hover {
  background: #111;
  color: #fff;
  border-color: #111;
}
.dd-picks__track {
  display: flex;
  gap: 1.5rem;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.dd-picks__slide {
  min-width: calc(50% - 0.75rem);
  flex-shrink: 0;
}
.dd-picks__card {
  display: block;
  text-decoration: none;
  color: #111;
  border: 1px solid #f0f0f0;
  border-radius: 1.25rem;
  overflow: hidden;
  background: #fafafa;
  transition: box-shadow 0.3s;
}
.dd-picks__card:hover {
  box-shadow: 0 8px 30px rgba(0,0,0,0.08);
}
.dd-picks__card img {
  width: 100%;
  height: 200px;
  object-fit: contain;
  padding: 1.5rem;
}
.dd-picks__card-info {
  padding: 0 1.5rem 1.5rem;
}
.dd-picks__card-info h3 {
  font-size: 1.1rem;
  font-weight: 800;
  margin-bottom: 0.25rem;
}
.dd-picks__card-info p {
  font-size: 0.9rem;
  font-weight: 700;
}
.dd-picks__card-info p span {
  color: #9ca3af;
  font-weight: 500;
  font-size: 0.8rem;
}
.dd-picks__dots {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 2rem;
}
.dd-picks__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #d1d5db;
  border: 0;
  padding: 0;
  cursor: pointer;
  transition: background 0.2s;
}
.dd-picks__dot--active {
  background: #111;
  width: 24px;
  border-radius: 100px;
}

@media (max-width: 768px) {
  .dd-picks__slide { min-width: 100%; }
}

/* ============================
   STEPS / PROCESS
   ============================ */
.dd-steps {
  padding: 5rem 0;
  background: #fafafa;
  border-top: 1px solid #f0f0f0;
}
.dd-steps__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
}
.dd-steps__header {
  text-align: center;
  margin-bottom: 3rem;
}
.dd-steps__header h2 {
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 900;
  letter-spacing: -0.03em;
  margin-bottom: 0.5rem;
}
.dd-steps__header p {
  color: #6b7280;
  font-weight: 500;
}
.dd-steps__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}
.dd-steps__card {
  background: #fff;
  border: 1px solid #f0f0f0;
  border-radius: 1.25rem;
  padding: 2.5rem 2rem;
  text-align: center;
}
.dd-steps__icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: #111;
}
.dd-steps__card h3 {
  font-size: 1.1rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
}
.dd-steps__card p {
  font-size: 0.85rem;
  color: #6b7280;
  font-weight: 500;
  line-height: 1.6;
}
.dd-steps__card--img {
  position: relative;
  overflow: hidden;
  padding: 0;
}
.dd-steps__card--img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  position: absolute;
  inset: 0;
}
.dd-steps__card-overlay {
  position: relative;
  z-index: 2;
  background: linear-gradient(to top, rgba(0,0,0,0.85), transparent);
  height: 100%;
  min-height: 260px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 2rem;
  color: #fff;
  text-align: left;
}
.dd-steps__card-overlay h3 {
  color: #fff;
}
.dd-steps__card-overlay p {
  color: rgba(255,255,255,0.7);
}

@media (max-width: 768px) {
  .dd-steps__grid { grid-template-columns: 1fr; }
}

/* ============================
   TRUST / TESTIMONIALS
   ============================ */
.dd-trust {
  padding: 5rem 0;
}
.dd-trust__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
}
.dd-trust__header {
  text-align: center;
  margin-bottom: 3rem;
}
.dd-trust__header h2 {
  font-size: clamp(1.75rem, 3.5vw, 2.5rem);
  font-weight: 900;
  letter-spacing: -0.03em;
  margin-bottom: 0.5rem;
}
.dd-trust__header p {
  color: #6b7280;
  font-weight: 500;
}
.dd-trust__grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}
.dd-trust__card {
  background: #fafafa;
  border: 1px solid #f0f0f0;
  border-radius: 1.25rem;
  padding: 2rem;
}
.dd-trust__stars {
  display: flex;
  gap: 0.15rem;
  margin-bottom: 1rem;
}
.dd-trust__quote {
  font-size: 0.95rem;
  font-weight: 500;
  line-height: 1.7;
  color: #374151;
  margin-bottom: 1.5rem;
}
.dd-trust__author {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.dd-trust__avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #111;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.9rem;
}
.dd-trust__author strong {
  font-size: 0.85rem;
  display: block;
}
.dd-trust__author span {
  font-size: 0.75rem;
  color: #9ca3af;
  font-weight: 500;
}

@media (max-width: 768px) {
  .dd-trust__grid { grid-template-columns: 1fr; }
}

/* ============================
   FAQ
   ============================ */
.dd-faq {
  padding: 5rem 0;
  background: #fafafa;
  border-top: 1px solid #f0f0f0;
}
.dd-faq__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 4rem;
  align-items: start;
}
.dd-faq__left h2 {
  font-size: clamp(1.75rem, 3vw, 2.25rem);
  font-weight: 900;
  letter-spacing: -0.03em;
  margin-bottom: 2rem;
  line-height: 1.2;
}
.dd-faq__contact-card {
  background: #111;
  color: #fff;
  border-radius: 1.25rem;
  padding: 2rem;
}
.dd-faq__contact-card h4 {
  font-size: 1rem;
  font-weight: 800;
  margin-bottom: 1.25rem;
}
.dd-faq__contact-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(255,255,255,0.7);
  font-size: 0.85rem;
  font-weight: 500;
  text-decoration: none;
  padding: 0.5rem 0;
  transition: color 0.2s;
}
.dd-faq__contact-row:hover {
  color: #fff;
}
.dd-faq__item {
  border: 1px solid #e5e7eb;
  border-radius: 1rem;
  background: #fff;
  margin-bottom: 0.75rem;
  overflow: hidden;
}
.dd-faq__item--open {
  border-color: #111;
}
.dd-faq__q {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.15rem 1.5rem;
  background: none;
  border: none;
  font-size: 0.9rem;
  font-weight: 700;
  color: #111;
  cursor: pointer;
  text-align: left;
  gap: 1rem;
}
.dd-faq__a {
  padding: 0 1.5rem 1.25rem;
}
.dd-faq__a p {
  font-size: 0.85rem;
  color: #6b7280;
  font-weight: 500;
  line-height: 1.7;
}

@media (max-width: 768px) {
  .dd-faq__inner { grid-template-columns: 1fr; gap: 2rem; }
}

/* ============================
   FOOTER
   ============================ */
.dd-footer {
  background: #111;
  color: #fff;
  padding: 4rem 0 2rem;
}
.dd-footer__inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 2rem;
}
.dd-footer__top {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 3rem;
  padding-bottom: 3rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.dd-footer__logo {
  height: 54px;
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 0 1px rgba(255,255,255,0.55));
  margin-bottom: 1rem;
}
.dd-footer__brand p {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.5);
  line-height: 1.6;
  max-width: 280px;
}
.dd-footer__col h4 {
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(255,255,255,0.4);
  margin-bottom: 1.25rem;
}
.dd-footer__col a {
  display: block;
  font-size: 0.85rem;
  color: rgba(255,255,255,0.6);
  text-decoration: none;
  padding: 0.3rem 0;
  transition: color 0.2s;
}
.dd-footer__col a:hover {
  color: #fff;
}
.dd-footer__socials {
  display: flex;
  gap: 0.75rem;
}
.dd-footer__socials a {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.5);
  transition: all 0.2s;
  padding: 0;
}
.dd-footer__socials a:hover {
  background: #fff;
  color: #111;
  border-color: #fff;
}
.dd-footer__bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 2rem;
  font-size: 0.75rem;
  color: rgba(255,255,255,0.35);
}
.dd-footer__legal {
  display: flex;
  gap: 1.5rem;
}
.dd-footer__legal a {
  color: rgba(255,255,255,0.35);
  text-decoration: none;
  transition: color 0.2s;
}
.dd-footer__legal a:hover {
  color: #fff;
}

@media (max-width: 768px) {
  .dd-footer__top { grid-template-columns: 1fr 1fr; gap: 2rem; }
  .dd-footer__bottom { flex-direction: column; gap: 1rem; text-align: center; }
}

@media (prefers-reduced-motion: reduce) {
  .dd-page *,
  .dd-page *::before,
  .dd-page *::after {
    scroll-behavior: auto !important;
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>
