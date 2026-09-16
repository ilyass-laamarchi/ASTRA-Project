<!-- StaticView — About + Contact pages, matching HomeView design -->
<script setup>
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../lib/api'
import AstraLogo from '../components/AstraLogo.vue'
import {
  MapPin, Phone, Mail, Clock, ShieldCheck, Wrench, HeartHandshake,
  Star, Users, Award, Menu, X, Send, ArrowRight, CheckCircle2,
  CarFront, CalendarCheck2, Plane, MessageCircle, Route, KeyRound
} from 'lucide-vue-next'

defineProps({ page: String })
const auth = useAuthStore()
const mobileNav = ref(false)
const form = reactive({ name: '', email: '', phone: '', message: '' })
const sent = ref(false)
const sending = ref(false)
const contactError = ref('')
const selectedTopic = ref('')
const contactTopics = [
  { title: 'Choisir un véhicule', text: 'Être conseillé selon mon trajet', icon: CarFront, prompt: 'Bonjour, je souhaite être conseillé pour choisir le véhicule adapté à mon trajet.' },
  { title: 'Organiser une location', text: 'Dates, livraison et options', icon: CalendarCheck2, prompt: 'Bonjour, je souhaite organiser une location et préciser mes dates, la livraison et les options.' },
  { title: 'Accueil aéroport', text: 'Préparer une arrivée sereine', icon: Plane, prompt: 'Bonjour, je souhaite organiser une prise en charge ou une livraison à l’aéroport.' }
]

/** Selects a contact topic and focuses the message form. */
function chooseTopic(topic) {
  selectedTopic.value = topic.title
  form.message = topic.prompt
}

/** Sends a validated public inquiry to Laravel and shows submission feedback. */
async function submit() {
  sending.value = true; sent.value = false; contactError.value = ''
  try { await api.post('/contact', form); sent.value = true; selectedTopic.value = ''; Object.assign(form, { name: '', email: '', phone: '', message: '' }) }
  catch(error) { contactError.value = error.response?.data?.message || 'Votre message n’a pas pu être envoyé.' }
  finally { sending.value = false }
}
</script>

<template>
  <div class="sv">
    <!-- NAV -->
    <nav class="sv-nav">
      <div class="sv-nav__inner">
        <RouterLink to="/" class="sv-nav__logo"><AstraLogo /></RouterLink>
        <div class="sv-nav__links">
          <RouterLink to="/" class="sv-nav__link">Accueil</RouterLink>
          <RouterLink to="/cars" class="sv-nav__link">Véhicules</RouterLink>
          <RouterLink to="/about" :class="['sv-nav__link', { 'sv-nav__link--active': page === 'about' }]">À propos</RouterLink>
          <RouterLink to="/contact" :class="['sv-nav__link', { 'sv-nav__link--active': page === 'contact' }]">Contact</RouterLink>
        </div>
        <div class="sv-nav__actions">
          <template v-if="!auth.isAuthenticated">
            <RouterLink to="/login" class="sv-nav__login">Connexion</RouterLink>
            <RouterLink to="/register" class="sv-nav__signup">S'inscrire</RouterLink>
          </template>
          <template v-else>
            <RouterLink :to="`/${auth.user.role}/dashboard`" class="sv-nav__signup">Mon Espace</RouterLink>
          </template>
        </div>
        <button class="sv-nav__burger" @click="mobileNav = !mobileNav"><Menu v-if="!mobileNav" :size="24" /><X v-else :size="24" /></button>
      </div>
      <div v-if="mobileNav" class="sv-nav__mobile" @click="mobileNav = false">
        <RouterLink to="/" class="sv-nav__mlink">Accueil</RouterLink>
        <RouterLink to="/cars" class="sv-nav__mlink">Véhicules</RouterLink>
        <RouterLink to="/about" class="sv-nav__mlink">À propos</RouterLink>
        <RouterLink to="/contact" class="sv-nav__mlink">Contact</RouterLink>
      </div>
    </nav>

    <!-- ══════════ ABOUT — immersive brand story ══════════ -->
    <template v-if="page === 'about'">
      <section class="av-hero">
        <div class="av-hero__copy">
          <span class="sv-hero__label">L’EXPÉRIENCE ASTRA</span>
          <h1>Nous ne louons pas simplement une voiture.</h1>
          <p>Nous préparons le moment où votre trajet devient plus simple, plus fluide et vraiment mémorable.</p>
          <div class="av-hero__actions"><RouterLink to="/cars">Vivre l’expérience <ArrowRight :size="18" /></RouterLink><RouterLink to="/contact">Parler à un conseiller</RouterLink></div>
        </div>
        <div class="av-portal">
          <span class="av-portal__ring av-portal__ring--one"></span><span class="av-portal__ring av-portal__ring--two"></span>
          <img src="/assets/images/about-experience-hero.jpg" width="1536" height="1024" fetchpriority="high" decoding="async" alt="Expérience ASTRA dans un pavillon méditerranéen" />
          <div class="av-portal__note"><span>ASTRA MOMENT</span><strong>Votre arrivée, parfaitement orchestrée.</strong></div>
        </div>
        <svg class="av-wave av-wave--bottom" viewBox="0 0 1440 160" preserveAspectRatio="none" aria-hidden="true"><path d="M0,96 C180,170 350,12 580,75 C820,142 1010,20 1440,85 L1440,160 L0,160 Z" fill="#fff"/></svg>
      </section>

      <section class="av-manifesto">
        <div class="av-manifesto__word" aria-hidden="true">ASTRA</div>
        <div class="av-manifesto__copy"><span class="sv-hero__label">NOTRE MANIFESTE</span><h2>Le luxe, c’est de ne penser à rien.</h2><p>Un véhicule prêt. Une information claire. Un humain disponible. Nous retirons la friction pour laisser toute la place au voyage.</p></div>
        <div class="av-manifesto__principles">
          <article v-for="(item,index) in [
            {icon:ShieldCheck,title:'Clair dès le départ',text:'Disponibilités, prix et conditions sont présentés sans détour.'},
            {icon:Wrench,title:'Prêt avant votre arrivée',text:'Chaque véhicule actif est vérifié et préparé pour son prochain trajet.'},
            {icon:HeartHandshake,title:'Humain jusqu’au retour',text:'Un interlocuteur accompagne votre réservation du début à la fin.'}
          ]" :key="item.title"><span>0{{index+1}}</span><component :is="item.icon" :size="25"/><h3>{{item.title}}</h3><p>{{item.text}}</p></article>
        </div>
      </section>

      <section class="av-road">
        <div class="av-road__heading"><span class="sv-hero__label">VOTRE PARCOURS</span><h2>Quatre moments.<br/>Une seule sensation de fluidité.</h2></div>
        <div class="av-road__track">
          <svg viewBox="0 0 1200 260" preserveAspectRatio="none" aria-hidden="true"><path d="M30 160 C200 10 330 240 500 105 S820 20 940 150 S1100 225 1170 75" fill="none" stroke="#3bc8f5" stroke-width="3" stroke-linecap="round" stroke-dasharray="8 14"/></svg>
          <article v-for="(step,index) in [
            {icon:Route,title:'Imaginez',text:'Parlez-nous de votre trajet.'},
            {icon:CarFront,title:'Choisissez',text:'Trouvez le véhicule juste.'},
            {icon:KeyRound,title:'Partez',text:'Tout est prêt à l’heure convenue.'},
            {icon:HeartHandshake,title:'Profitez',text:'Nous restons disponibles.'}
          ]" :key="step.title" :class="`av-road__stop av-road__stop--${index+1}`"><span><component :is="step.icon" :size="22"/></span><h3>{{step.title}}</h3><p>{{step.text}}</p></article>
        </div>
      </section>

      <section class="av-bento">
        <article class="av-bento__lead"><span class="sv-hero__label">NOTRE DIFFÉRENCE</span><h2>Une flotte réelle.<br/>Un service vivant.</h2><p>ASTRA gère ses véhicules et suit chaque réservation dans une même expérience.</p><RouterLink to="/cars">Explorer les véhicules <ArrowRight :size="17"/></RouterLink></article>
        <article class="av-bento__visual"><img src="/assets/images/fleet-sedan-900.webp" width="900" height="522" loading="lazy" decoding="async" alt="Véhicule premium ASTRA"/><span>Préparé avec exigence</span></article>
        <article v-for="v in [{icon:Star,title:'Excellence',text:'Le soin se voit dans chaque détail.'},{icon:Users,title:'Proximité',text:'Une vraie équipe, jamais un parcours anonyme.'},{icon:Award,title:'Fiabilité',text:'Des décisions basées sur la flotte réelle.'}]" :key="v.title" class="av-bento__value"><component :is="v.icon" :size="23"/><h3>{{v.title}}</h3><p>{{v.text}}</p></article>
      </section>

      <section class="av-finale"><svg viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0,60 C260,-10 430,120 720,48 C1020,-25 1160,105 1440,28 L1440,0 L0,0 Z" fill="#fff"/></svg><div><span>PRÊT POUR LA SUITE ?</span><h2>Votre prochain trajet commence ici.</h2><RouterLink to="/cars">Choisir mon véhicule <ArrowRight :size="18"/></RouterLink></div></section>
    </template>

    <!-- ══════════ CONTACT — concierge journey ══════════ -->
    <template v-else>
      <section class="ct-hero">
        <div class="ct-hero__waves" aria-hidden="true"><i></i><i></i><i></i></div>
        <div class="ct-hero__copy"><span>CONCIERGERIE ASTRA</span><h1>Dites-nous où vous voulez aller.</h1><p>Nous nous occupons de rendre le chemin simple.</p></div>
        <div class="ct-hero__channels">
          <a href="tel:+212539000000"><Phone :size="21"/><span><small>Appelez-nous</small><strong>+212 5 39 00 00 00</strong></span></a>
          <a href="mailto:contact@astra.ma"><Mail :size="21"/><span><small>Écrivez-nous</small><strong>contact@astra.ma</strong></span></a>
          <div><Clock :size="21"/><span><small>Disponibilité</small><strong>Lun–Sam · 08h–19h</strong></span></div>
        </div>
        <svg class="ct-hero__wave" viewBox="0 0 1440 150" preserveAspectRatio="none" aria-hidden="true"><path d="M0,80 C200,5 390,150 650,70 C920,-12 1130,145 1440,45 L1440,150 L0,150 Z" fill="#f3f8fd"/></svg>
      </section>

      <section class="ct-intent">
        <div class="ct-intent__copy"><span class="sv-hero__label">VOTRE BESOIN</span><h2>Un clic pour mieux vous comprendre.</h2><p>Choisissez un point de départ : nous préparons le message, vous gardez la main.</p></div>
        <div class="ct-intent__list">
          <button v-for="(topic,index) in contactTopics" :key="topic.title" type="button" :class="{'ct-intent__active':selectedTopic===topic.title}" @click="chooseTopic(topic)"><b>0{{index+1}}</b><span><component :is="topic.icon" :size="25"/><strong>{{topic.title}}</strong><small>{{topic.text}}</small></span><ArrowRight :size="19"/></button>
        </div>
      </section>

      <section class="ct-concierge">
        <aside class="ct-concierge__guide">
          <span class="sv-hero__label">SERVICE PERSONNEL</span><h2>Une conversation, pas un ticket.</h2><p>Votre demande arrive directement à l’équipe ASTRA.</p>
          <ol><li><span>1</span><p><strong>Vous écrivez</strong><small>Quelques détails suffisent.</small></p></li><li><span>2</span><p><strong>Nous vérifions</strong><small>Flotte, dates et options réelles.</small></p></li><li><span>3</span><p><strong>Nous vous répondons</strong><small>Avec une proposition claire.</small></p></li></ol>
          <div class="ct-concierge__promise"><MessageCircle :size="22"/><p><strong>Réponse humaine</strong><small>Sous 24 heures ouvrées.</small></p></div>
        </aside>
        <form class="sv-form ct-form" @submit.prevent="submit">
          <div class="sv-form__head"><span class="sv-hero__label">VOTRE MESSAGE</span><h3>Construisons votre trajet.</h3><p>Les champs marqués sont nécessaires pour vous répondre.</p></div>
          <div class="sv-form__field"><label>Nom complet</label><input v-model="form.name" required placeholder="Jean Dupont" /></div>
          <div class="sv-form__row"><div class="sv-form__field"><label>Adresse e-mail</label><input v-model="form.email" type="email" required placeholder="nom@exemple.com" /></div><div class="sv-form__field"><label>Téléphone</label><input v-model="form.phone" type="tel" placeholder="+212 6XX XX XX XX" /></div></div>
          <div class="sv-form__field"><label>Votre message</label><textarea v-model="form.message" rows="6" required placeholder="Parlez-nous de votre besoin..."></textarea></div>
          <button type="submit" class="sv-form__submit" :disabled="sending"><Send :size="16" /> {{sending ? 'Envoi en cours…' : 'Envoyer à la conciergerie'}}</button>
          <p v-if="sent" class="sv-form__success">✓ Votre message a bien été envoyé. Nous vous répondrons sous 24h.</p><p v-if="contactError" class="sv-form__success" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c">{{contactError}}</p>
        </form>
      </section>

      <section class="ct-location">
        <div class="ct-location__map"><iframe title="Localisation ASTRA à Tanger" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=-5.90%2C35.70%2C-5.72%2C35.82&amp;layer=mapnik&amp;marker=35.7595%2C-5.8340"></iframe></div>
        <div class="ct-location__card"><span class="sv-hero__label">NOUS TROUVER</span><h2>ASTRA Tanger</h2><p><MapPin :size="18"/> Tanger, Maroc</p><p><Clock :size="18"/> Lun–Sam · 08h00–19h00</p><a href="https://www.openstreetmap.org/?mlat=35.7595&amp;mlon=-5.8340#map=14/35.7595/-5.8340" target="_blank" rel="noopener">Ouvrir l’itinéraire <ArrowRight :size="17"/></a></div>
      </section>
    </template>

    <!-- FOOTER -->
    <footer class="sv-footer">
      <div class="sv-footer__inner">
        <div class="sv-footer__top">
          <div class="sv-footer__brand">
            <AstraLogo variant="inverse" class="sv-footer__logo" loading="lazy" />
            <p>Mobilité premium, service d'exception et confiance pour chaque trajet.</p>
          </div>
          <div class="sv-footer__col"><h4>Pages</h4>
            <RouterLink to="/">Accueil</RouterLink><RouterLink to="/cars">Véhicules</RouterLink><RouterLink to="/about">À propos</RouterLink>
          </div>
          <div class="sv-footer__col"><h4>Espace Client</h4>
            <RouterLink to="/login">Connexion</RouterLink><RouterLink to="/register">Inscription</RouterLink>
          </div>
          <div class="sv-footer__col"><h4>Nous contacter</h4><RouterLink to="/contact">Formulaire de contact</RouterLink><a href="mailto:contact@astra.ma">contact@astra.ma</a></div>
        </div>
        <div class="sv-footer__bottom"><span>© {{ new Date().getFullYear() }} ASTRA Premium. Tous droits réservés.</span></div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.sv { font-family: 'Inter', system-ui, sans-serif; color: #111; background: #fff; }

/* NAV */
.sv-nav { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #f0f0f0; }
.sv-nav__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; height: 72px; display: flex; align-items: center; justify-content: space-between; }
.sv-nav__logo img { height: 28px; object-fit: contain; }
.sv-nav__links { display: flex; gap: 2rem; }
.sv-nav__link { font-size: .875rem; font-weight: 600; color: #6b7280; text-decoration: none; transition: color .2s; }
.sv-nav__link:hover, .sv-nav__link--active { color: #111; }
.sv-nav__actions { display: flex; align-items: center; gap: 1rem; }
.sv-nav__login { font-size: .875rem; font-weight: 600; color: #111; text-decoration: none; }
.sv-nav__signup { font-size: .875rem; font-weight: 700; color: #fff; background: #111; padding: .6rem 1.5rem; border-radius: 100px; text-decoration: none; }
.sv-nav__burger { display: none; background: none; border: none; cursor: pointer; }
.sv-nav__mobile { display: none; flex-direction: column; padding: 1rem 2rem 2rem; border-top: 1px solid #f0f0f0; }
.sv-nav__mlink { padding: .75rem 0; font-weight: 600; color: #111; text-decoration: none; border-bottom: 1px solid #f5f5f5; }
@media (max-width: 768px) {
  .sv-nav__links, .sv-nav__actions { display: none; }
  .sv-nav__burger { display: block; }
  .sv-nav__mobile { display: flex; }
}

/* HERO */
.sv-hero { max-width: 1280px; margin: 0 auto; padding: 4rem 2rem 3rem; text-align: center; background: #fafafa; }
.sv-hero__label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: #9ca3af; }
.sv-hero__title { font-size: clamp(2rem, 4.5vw, 3.2rem); font-weight: 900; letter-spacing: -.03em; margin: .5rem 0 1rem; line-height: 1.15; }
.sv-hero__sub { color: #6b7280; font-weight: 500; font-size: .95rem; max-width: 520px; margin: 0 auto; line-height: 1.6; }

/* MISSION */
.sv-mission { padding: 5rem 0; }
.sv-mission__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; }
.sv-mission__img { border-radius: 1.25rem; overflow: hidden; background: #fafafa; }
.sv-mission__img img { width: 100%; height: 400px; object-fit: contain; }
.sv-section__title { font-size: clamp(1.75rem, 3vw, 2.25rem); font-weight: 900; letter-spacing: -.03em; margin: .5rem 0 1rem; line-height: 1.2; }
.sv-text { color: #6b7280; font-weight: 500; line-height: 1.7; margin-bottom: 2rem; }
.sv-features { display: flex; flex-direction: column; gap: 1.25rem; }
.sv-feature { display: flex; gap: 1rem; align-items: start; }
.sv-feature__icon { width: 48px; height: 48px; border-radius: 14px; background: #fafafa; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #111; }
.sv-feature strong { font-size: .9rem; font-weight: 800; display: block; margin-bottom: .2rem; }
.sv-feature p { font-size: .82rem; color: #6b7280; font-weight: 500; line-height: 1.5; }
@media (max-width: 768px) { .sv-mission__inner { grid-template-columns: 1fr; } }

/* STATS */
.sv-stats { background: #fafafa; padding: 4rem 0; border-top: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0; }
.sv-stats__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 2rem; }
.sv-stat { text-align: center; }
.sv-stat__val { font-size: 2.5rem; font-weight: 900; letter-spacing: -.03em; display: block; }
.sv-stat__label { font-size: .75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .08em; }

/* VALUES */
.sv-values { padding: 5rem 0; }
.sv-values__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.sv-value { background: #fafafa; border: 1px solid #f0f0f0; border-radius: 1.25rem; padding: 2.5rem 2rem; text-align: center; }
.sv-value__icon { width: 56px; height: 56px; border-radius: 16px; background: #fff; border: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; color: #111; }
.sv-value h3 { font-size: 1.1rem; font-weight: 800; margin-bottom: .5rem; }
.sv-value p { font-size: .85rem; color: #6b7280; font-weight: 500; line-height: 1.6; }
@media (max-width: 768px) { .sv-values__inner { grid-template-columns: 1fr; } }

/* CONTACT */
.sv-contact { padding: 0 0 5rem; }
.sv-contact__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; display: grid; grid-template-columns: 1fr 1.5fr; gap: 3rem; align-items: start; }
.sv-contact__info { display: flex; flex-direction: column; gap: 1rem; }
.sv-info-card { display: flex; gap: 1rem; align-items: center; background: #fff; border: 1px solid #f0f0f0; border-radius: 1rem; padding: 1.25rem; }
.sv-info-card__icon { width: 44px; height: 44px; border-radius: 12px; background: #fafafa; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #111; }
.sv-info-card strong { font-size: .85rem; font-weight: 800; display: block; }
.sv-info-card p { font-size: .8rem; color: #6b7280; font-weight: 500; }
.sv-map-placeholder { height: 180px; overflow: hidden; background: #fafafa; border: 1px solid #f0f0f0; border-radius: 1rem; }
.sv-map-placeholder iframe { width: 100%; height: 100%; border: 0; }

/* FORM */
.sv-form { background: #fff; border: 1px solid #f0f0f0; border-radius: 1.25rem; padding: 2rem; }
.sv-form h3 { font-size: 1.15rem; font-weight: 800; margin-bottom: 1.5rem; }
.sv-form__field { display: flex; flex-direction: column; gap: .35rem; margin-bottom: 1rem; }
.sv-form__field label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #9ca3af; }
.sv-form__field input, .sv-form__field textarea {
  padding: .7rem .85rem; border: 1.5px solid #e5e7eb; border-radius: .75rem;
  font-size: .85rem; font-weight: 600; color: #111; background: #fafafa; outline: none;
  font-family: inherit; transition: border-color .2s; resize: vertical;
}
.sv-form__field input:focus, .sv-form__field textarea:focus { border-color: #111; background: #fff; }
.sv-form__field input::placeholder, .sv-form__field textarea::placeholder { color: #c0bcc8; }
.sv-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.sv-form__submit {
  display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
  padding: .85rem; background: #111; color: #fff; border: none; border-radius: 100px;
  font-size: .85rem; font-weight: 700; cursor: pointer; transition: background .2s; margin-top: .5rem;
}
.sv-form__submit:hover { background: #333; }
.sv-form__success { margin-top: 1rem; padding: .75rem 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: .75rem; color: #166534; font-size: .82rem; font-weight: 600; }
@media (max-width: 768px) {
  .sv-contact__inner { grid-template-columns: 1fr; }
  .sv-form__row { grid-template-columns: 1fr; }
}

/* FOOTER */
.sv-footer { background: #111; color: #fff; padding: 4rem 0 2rem; }
.sv-footer__inner { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }
.sv-footer__top { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 3rem; padding-bottom: 3rem; border-bottom: 1px solid rgba(255,255,255,.1); }
.sv-footer__logo { height: 54px; width: auto; object-fit: contain; margin-bottom: 1rem; filter: drop-shadow(0 0 1px rgba(255,255,255,.55)); }
.sv-footer__brand p { font-size: .85rem; color: rgba(255,255,255,.5); line-height: 1.6; max-width: 280px; }
.sv-footer__col h4 { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.4); margin-bottom: 1.25rem; }
.sv-footer__col a { display: block; font-size: .85rem; color: rgba(255,255,255,.6); text-decoration: none; padding: .3rem 0; }
.sv-footer__col a:hover { color: #fff; }
.sv-footer__socials { display: flex; gap: .75rem; }
.sv-footer__socials a { width: 36px; height: 36px; border-radius: 50%; border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.5); padding: 0; }
.sv-footer__socials a:hover { background: #fff; color: #111; border-color: #fff; }
.sv-footer__bottom { padding-top: 2rem; text-align: center; font-size: .75rem; color: rgba(255,255,255,.35); }
@media (max-width: 768px) { .sv-footer__top { grid-template-columns: 1fr 1fr; gap: 2rem; } }

/* Premium ASTRA refinement — shared About / Contact visual language */
.sv {
  --sv-navy: #071a3b;
  --sv-blue: #0d4f89;
  --sv-cyan: #3bc8f5;
  --sv-ink: #0b1f3d;
  --sv-muted: #64748b;
  --sv-mist: #f3f8fd;
  --sv-line: #dbe7f1;
  color: var(--sv-ink);
  background: #fff;
}
.sv-nav { background: rgba(255,255,255,.92); border-color: rgba(188,211,228,.64); box-shadow: 0 8px 28px rgba(7,26,59,.05); backdrop-filter: blur(18px); }
.sv-nav__inner { max-width: 1380px; height: 92px; padding-inline: clamp(1.25rem,4vw,3.5rem); }
.sv-nav__logo { position: relative; width: 220px; display: inline-block; overflow: visible; line-height: 0; }
.sv-nav__logo img { display: block; width: 220px; height: auto; object-fit: contain; }
.sv-nav__links { gap: clamp(1.4rem,3vw,2.6rem); }
.sv-nav__link { position: relative; padding: 1.85rem 0; color: #52627a; font-size: .84rem; font-weight: 700; }
.sv-nav__link::after { content: ''; position: absolute; right: 0; bottom: 1.25rem; left: 0; height: 2px; background: var(--sv-cyan); border-radius: 10px; transform: scaleX(0); transition: transform .2s ease; }
.sv-nav__link:hover,.sv-nav__link--active { color: var(--sv-navy); }
.sv-nav__link--active::after,.sv-nav__link:hover::after { transform: scaleX(1); }
.sv-nav__login { color: var(--sv-navy); }
.sv-nav__signup { color: #fff; background: linear-gradient(135deg,var(--sv-navy),var(--sv-blue)); border: 1px solid rgba(59,200,245,.25); box-shadow: 0 8px 22px rgba(7,42,84,.18); transition: transform .2s ease,box-shadow .2s ease; }
.sv-nav__signup:hover { transform: translateY(-1px); box-shadow: 0 11px 28px rgba(7,42,84,.25); }

.sv-hero { position: relative; max-width: none; min-height: 430px; display: grid; place-items: center; overflow: hidden; padding: clamp(4.5rem,8vw,7rem) 2rem; color: #fff; text-align: left; background: radial-gradient(circle at 82% 20%,rgba(59,200,245,.2),transparent 28%),linear-gradient(125deg,#061630 0%,#0a315f 60%,#0b477b 100%); }
.sv-hero::before { content: ''; position: absolute; inset: 0; opacity: .2; background-image: linear-gradient(rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px); background-size: 54px 54px; mask-image: linear-gradient(90deg,transparent,#000); }
.sv-hero__glow { position: absolute; top: -160px; right: -80px; width: 560px; height: 560px; border: 1px solid rgba(95,216,255,.22); border-radius: 50%; box-shadow: 0 0 0 70px rgba(75,202,245,.04),0 0 0 145px rgba(75,202,245,.025); }
.sv-hero__content { position: relative; z-index: 2; width: min(1180px,100%); margin: 0 auto; }
.sv-hero__label { color: var(--sv-cyan); font-size: .68rem; font-weight: 850; letter-spacing: .18em; }
.sv-hero__title { max-width: 850px; margin: .8rem 0 1.1rem; color: #fff; font-size: clamp(2.65rem,5.2vw,4.75rem); font-weight: 780; line-height: 1.01; letter-spacing: -.045em; text-wrap: balance; }
.sv-hero--contact .sv-hero__title { max-width: 940px; }
.sv-hero__sub { max-width: 650px; margin: 0; color: rgba(231,244,255,.74); font-size: clamp(.94rem,1.5vw,1.08rem); line-height: 1.7; }
.sv-hero__actions { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 2rem; }
.sv-btn { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: .55rem; padding: 0 1.25rem; border-radius: 12px; font-size: .8rem; font-weight: 800; text-decoration: none; transition: transform .2s ease,background .2s ease; }
.sv-btn:hover { transform: translateY(-2px); }
.sv-btn--primary { color: var(--sv-navy); background: linear-gradient(135deg,#fff,#dff6ff); box-shadow: 0 12px 30px rgba(0,10,30,.18); }
.sv-btn--ghost { color: #fff; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.22); }
.sv-btn--ghost:hover { background: rgba(255,255,255,.13); }
.sv-hero__trust { display: flex; flex-wrap: wrap; gap: .7rem 1.6rem; margin-top: 2rem; padding-top: 1.4rem; border-top: 1px solid rgba(133,218,251,.18); color: rgba(235,247,255,.82); font-size: .72rem; font-weight: 700; }
.sv-hero__trust span { display: inline-flex; align-items: center; gap: .45rem; }
.sv-hero__trust svg { color: var(--sv-cyan); }

.sv-mission { padding: clamp(4.5rem,8vw,7rem) 0; background: linear-gradient(180deg,#fff,var(--sv-mist)); }
.sv-mission__inner { max-width: 1240px; gap: clamp(3rem,7vw,6.5rem); }
.sv-mission__img { position: relative; min-height: 440px; display: grid; place-items: center; overflow: hidden; background: radial-gradient(circle at 50% 42%,#fff 0,#e9f5fc 52%,#dcebf6 100%); border: 1px solid rgba(163,198,222,.55); border-radius: 28px; box-shadow: 0 28px 70px rgba(7,37,71,.12); }
.sv-mission__img::after { content: ''; position: absolute; right: 8%; bottom: 10%; left: 8%; height: 18px; background: rgba(7,26,59,.2); border-radius: 50%; filter: blur(16px); }
.sv-mission__img img { position: relative; z-index: 1; width: 94%; height: auto; object-fit: contain; filter: drop-shadow(0 24px 20px rgba(7,26,59,.18)); }
.sv-mission__badge { position: absolute; z-index: 3; right: 18px; bottom: 18px; display: grid; gap: 2px; padding: 13px 16px; color: #fff; background: rgba(7,26,59,.86); border: 1px solid rgba(91,211,250,.28); border-radius: 15px; backdrop-filter: blur(12px); }
.sv-mission__badge span { color: var(--sv-cyan); font-size: .55rem; font-weight: 900; letter-spacing: .15em; }
.sv-mission__badge strong { font-size: .78rem; }
.sv-section__title { color: var(--sv-navy); font-size: clamp(2rem,3.4vw,3rem); line-height: 1.08; }
.sv-text { color: var(--sv-muted); font-size: .94rem; }
.sv-feature { padding: .85rem; border: 1px solid transparent; border-radius: 16px; transition: border-color .2s ease,background .2s ease,transform .2s ease; }
.sv-feature:hover { background: rgba(255,255,255,.74); border-color: var(--sv-line); transform: translateX(4px); }
.sv-feature__icon { color: var(--sv-blue); background: #e7f6fc; border: 1px solid #ccecf8; }
.sv-feature strong { color: var(--sv-navy); }

.sv-stats { padding: 3.5rem 0; color: #fff; background: linear-gradient(120deg,var(--sv-navy),#0b3c70); border: 0; }
.sv-stats__inner { max-width: 1180px; }
.sv-stat { min-width: 170px; }
.sv-stat__val { color: #fff; font-size: clamp(2.2rem,4vw,3.15rem); }
.sv-stat__val::after { content: ''; display: block; width: 28px; height: 2px; margin: .7rem auto; background: var(--sv-cyan); }
.sv-stat__label { color: rgba(226,241,251,.62); }

.sv-values { padding: clamp(4.5rem,8vw,7rem) 0; background: #fff; }
.sv-values__heading { max-width: 760px; margin: 0 auto 2.5rem; padding: 0 2rem; text-align: center; }
.sv-values__inner { max-width: 1180px; gap: 1.25rem; }
.sv-value { position: relative; overflow: hidden; padding: 2.35rem 2rem; text-align: left; background: linear-gradient(145deg,#fff,#f4f9fd); border-color: var(--sv-line); border-radius: 22px; box-shadow: 0 14px 38px rgba(9,44,80,.06); transition: transform .25s ease,box-shadow .25s ease,border-color .25s ease; }
.sv-value::after { content: ''; position: absolute; top: 0; right: 0; width: 72px; height: 72px; background: radial-gradient(circle at 100% 0,rgba(59,200,245,.2),transparent 68%); }
.sv-value:hover { transform: translateY(-6px); border-color: #b9e8f8; box-shadow: 0 22px 48px rgba(9,44,80,.11); }
.sv-value__icon { margin: 0 0 1.35rem; color: var(--sv-blue); background: #e5f7fe; border-color: #c7ecfa; }
.sv-value h3 { color: var(--sv-navy); font-size: 1.15rem; }
.sv-value p { color: var(--sv-muted); }

.sv-contact { padding: clamp(3.5rem,6vw,5.5rem) 0 clamp(5rem,8vw,7rem); background: var(--sv-mist); }
.sv-contact__inner { max-width: 1240px; grid-template-columns: minmax(300px,.8fr) minmax(480px,1.35fr); gap: 1.5rem; }
.sv-contact__info { display: grid; grid-template-columns: 1fr 1fr; gap: .85rem; }
.sv-info-card { min-height: 112px; align-items: flex-start; padding: 1.2rem; border-color: var(--sv-line); border-radius: 18px; box-shadow: 0 10px 28px rgba(9,44,80,.05); transition: transform .2s ease,border-color .2s ease,box-shadow .2s ease; }
.sv-info-card:hover { transform: translateY(-3px); border-color: #b6e7f7; box-shadow: 0 16px 34px rgba(9,44,80,.09); }
.sv-info-card__icon { color: var(--sv-blue); background: #e5f7fe; border: 1px solid #c8edf9; }
.sv-info-card strong { color: var(--sv-navy); }
.sv-info-card p { margin: .28rem 0 0; color: var(--sv-muted); line-height: 1.45; }
.sv-map-placeholder { grid-column: 1/-1; height: 235px; border-color: var(--sv-line); border-radius: 20px; box-shadow: 0 14px 36px rgba(9,44,80,.07); }
.sv-form { padding: clamp(1.7rem,3vw,2.7rem); border-color: var(--sv-line); border-radius: 24px; box-shadow: 0 22px 60px rgba(7,37,71,.1); }
.sv-form__head { margin-bottom: 1.7rem; }
.sv-form__head h3 { margin: .45rem 0 .45rem; color: var(--sv-navy); font-size: clamp(1.45rem,2.5vw,2rem); }
.sv-form__head p { max-width: 530px; margin: 0; color: var(--sv-muted); font-size: .82rem; line-height: 1.6; }
.sv-form__field { gap: .48rem; margin-bottom: 1.05rem; }
.sv-form__field label { color: #557089; font-size: .65rem; letter-spacing: .09em; }
.sv-form__field input,.sv-form__field textarea { border-color: #d6e4ef; background: #f7fbfe; border-radius: 13px; color: var(--sv-navy); transition: border-color .2s ease,box-shadow .2s ease,background .2s ease; }
.sv-form__field input { min-height: 50px; }
.sv-form__field textarea { min-height: 150px; }
.sv-form__field input:focus,.sv-form__field textarea:focus { border-color: var(--sv-cyan); box-shadow: 0 0 0 4px rgba(59,200,245,.12); }
.sv-form__submit { min-height: 52px; margin-top: .75rem; background: linear-gradient(135deg,var(--sv-navy),var(--sv-blue)); border: 1px solid rgba(59,200,245,.24); border-radius: 13px; box-shadow: 0 12px 28px rgba(7,42,84,.2); transition: transform .2s ease,box-shadow .2s ease; }
.sv-form__submit:hover:not(:disabled) { background: linear-gradient(135deg,#0a2855,#1169aa); transform: translateY(-2px); box-shadow: 0 16px 34px rgba(7,42,84,.28); }
.sv-form__submit:disabled { opacity: .65; cursor: wait; }

.sv-footer { background: #061630; border-top: 1px solid rgba(70,198,242,.2); }
.sv-footer__logo { filter: drop-shadow(0 0 1px rgba(255,255,255,.55)); }
.sv-footer__col h4 { color: var(--sv-cyan); }
.sv-footer__col a:hover { color: var(--sv-cyan); }

@media (max-width: 900px) {
  .sv-contact__inner { grid-template-columns: 1fr; }
  .sv-contact__info { grid-template-columns: repeat(2,1fr); }
  .sv-mission__inner { gap: 3rem; }
}
@media (max-width: 768px) {
  .sv-nav__inner { height: 70px; }
  .sv-nav__logo,.sv-nav__logo img { width: 175px; }
  .sv-nav__burger { color: var(--sv-navy); }
  .sv-nav__mobile { background: #fff; border-color: var(--sv-line); }
  .sv-nav__mlink { color: var(--sv-navy); border-color: var(--sv-line); }
  .sv-hero { min-height: auto; padding: 4.25rem 1.25rem; }
  .sv-hero__title { font-size: clamp(2.45rem,11vw,3.4rem); }
  .sv-hero__title br { display: none; }
  .sv-hero__trust { gap: .7rem 1rem; }
  .sv-mission { padding: 4rem 0; }
  .sv-mission__inner { padding-inline: 1.25rem; }
  .sv-mission__img { min-height: 310px; }
  .sv-stats__inner { display: grid; grid-template-columns: 1fr 1fr; }
  .sv-stat { min-width: 0; }
  .sv-values__inner { padding-inline: 1.25rem; }
  .sv-contact__inner { padding-inline: 1.25rem; }
  .sv-contact__info { grid-template-columns: 1fr 1fr; }
  .sv-footer__inner { padding-inline: 1.25rem; }
}
@media (max-width: 520px) {
  .sv-hero { padding-block: 3.65rem; }
  .sv-hero__title { font-size: 2.55rem; }
  .sv-hero__actions { display: grid; grid-template-columns: 1fr; }
  .sv-hero__trust { display: grid; }
  .sv-contact__info { grid-template-columns: 1fr; }
  .sv-map-placeholder { grid-column: auto; }
  .sv-form { padding: 1.25rem; border-radius: 18px; }
  .sv-stats__inner { gap: 2.4rem 1rem; }
  .sv-stat__val { font-size: 2rem; }
  .sv-stat__label { font-size: .62rem; }
  .sv-footer__top { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .sv-btn,.sv-info-card,.sv-value,.sv-feature,.sv-nav__signup { transition: none; }
}

/* Immersive brand-story About experience */
.sv-hero--about { min-height: 680px; align-items: end; padding-top: 9rem; padding-bottom: 6rem; background: #061630; }
.sv-hero__photo { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: 54% 55%; }
.sv-hero__veil { position: absolute; inset: 0; background: linear-gradient(90deg,rgba(3,15,35,.95) 0%,rgba(3,19,43,.78) 41%,rgba(3,19,43,.12) 73%),linear-gradient(0deg,rgba(2,13,31,.66),transparent 55%); }
.sv-hero--about::before { z-index: 1; opacity: .09; }
.sv-hero--about .sv-hero__glow { display: none; }
.sv-hero--about .sv-hero__content { align-self: end; }
.sv-hero--about .sv-hero__title { max-width: 700px; font-size: clamp(3.2rem,6.2vw,5.75rem); }
.sv-hero--about .sv-hero__sub { max-width: 600px; }
.sv-experience-bar { position: relative; z-index: 5; width: min(1160px,calc(100% - 4rem)); display: grid; grid-template-columns: repeat(3,1fr); margin: -42px auto 0; padding: 1.15rem; color: var(--sv-navy); background: rgba(255,255,255,.94); border: 1px solid rgba(180,215,235,.7); border-radius: 22px; box-shadow: 0 24px 60px rgba(6,31,62,.17); backdrop-filter: blur(16px); }
.sv-experience-bar>div { min-width: 0; display: flex; align-items: center; gap: 1rem; padding: .8rem 1.2rem; border-right: 1px solid var(--sv-line); }
.sv-experience-bar>div:last-child { border-right: 0; }
.sv-experience-bar>div>span { width: 38px; height: 38px; flex: 0 0 auto; display: grid; place-items: center; color: var(--sv-cyan); background: var(--sv-navy); border-radius: 12px; font-size: .67rem; font-weight: 900; }
.sv-experience-bar p { display: grid; gap: .2rem; margin: 0; }
.sv-experience-bar strong { font-size: .8rem; }
.sv-experience-bar small { overflow: hidden; color: var(--sv-muted); font-size: .67rem; text-overflow: ellipsis; white-space: nowrap; }
.sv-journey { max-width: 1240px; margin: 0 auto; padding: clamp(6rem,10vw,9rem) 2rem clamp(5rem,8vw,7rem); }
.sv-journey__intro { display: grid; grid-template-columns: .42fr 1.1fr .8fr; gap: 2.4rem; align-items: end; margin-bottom: 3rem; }
.sv-journey__intro .sv-hero__label { align-self: start; margin-top: .65rem; }
.sv-journey__intro h2 { margin: 0; }
.sv-journey__intro>p { margin: 0 0 .3rem; color: var(--sv-muted); font-size: .86rem; line-height: 1.75; }
.sv-journey__steps { display: grid; grid-template-columns: repeat(4,1fr); border: 1px solid var(--sv-line); border-radius: 26px; overflow: hidden; box-shadow: 0 22px 55px rgba(9,44,80,.08); }
.sv-journey__step { position: relative; min-height: 300px; padding: 2rem 1.6rem; background: linear-gradient(150deg,#fff,#f5faff); border-right: 1px solid var(--sv-line); transition: color .25s ease,background .25s ease,transform .25s ease; }
.sv-journey__step:last-child { border-right: 0; }
.sv-journey__step:hover { z-index: 2; color: #fff; background: linear-gradient(145deg,var(--sv-navy),var(--sv-blue)); transform: translateY(-8px); box-shadow: 0 18px 42px rgba(7,35,70,.25); }
.sv-journey__number { display: block; color: #91a8bb; font-size: .62rem; font-weight: 900; letter-spacing: .12em; }
.sv-journey__icon { width: 52px; height: 52px; display: grid; place-items: center; margin: 3.4rem 0 1.25rem; color: var(--sv-blue); background: #e4f7fe; border: 1px solid #c4eaf8; border-radius: 16px; }
.sv-journey__step h3 { margin: 0 0 .7rem; color: var(--sv-navy); font-size: 1rem; }
.sv-journey__step p { margin: 0; color: var(--sv-muted); font-size: .75rem; line-height: 1.7; }
.sv-journey__step:hover .sv-journey__number,.sv-journey__step:hover p { color: rgba(234,247,255,.7); }
.sv-journey__step:hover h3 { color: #fff; }
.sv-journey__step:hover .sv-journey__icon { color: var(--sv-cyan); background: rgba(255,255,255,.08); border-color: rgba(83,207,248,.3); }
.sv-journey+.sv-mission { padding-top: 6rem; background: linear-gradient(135deg,#eef7fc,#fff); border-top: 1px solid var(--sv-line); }

/* Concierge-first Contact experience */
.sv-hero--contact { min-height: 540px; place-items: center; background: radial-gradient(circle at 76% 42%,rgba(47,188,238,.22),transparent 25%),linear-gradient(118deg,#04142f 0%,#082d58 58%,#0a4e83 100%); }
.sv-hero--contact .sv-hero__content { padding-right: 420px; }
.sv-hero--contact .sv-hero__title { max-width: 720px; font-size: clamp(3.1rem,5.6vw,5.2rem); }
.sv-contact-orbit { position: absolute; z-index: 3; top: 50%; right: max(4vw,calc((100vw - 1180px)/2)); width: 310px; display: flex; align-items: center; gap: 1rem; padding: 1.25rem; color: #fff; background: rgba(4,24,58,.55); border: 1px solid rgba(104,216,252,.32); border-radius: 20px; box-shadow: 0 24px 70px rgba(0,12,34,.32),0 0 0 18px rgba(70,199,243,.04),0 0 0 36px rgba(70,199,243,.025); backdrop-filter: blur(16px); transform: translateY(-50%); }
.sv-contact-orbit>span { width: 50px; height: 50px; flex: 0 0 auto; display: grid; place-items: center; color: var(--sv-cyan); background: rgba(59,200,245,.1); border: 1px solid rgba(93,216,255,.24); border-radius: 15px; }
.sv-contact-orbit p { display: grid; gap: .3rem; margin: 0; }
.sv-contact-orbit strong { font-size: .83rem; }
.sv-contact-orbit small { color: rgba(228,244,253,.65); font-size: .68rem; line-height: 1.5; }
.sv-contact-intent { max-width: 1240px; margin: 0 auto; padding: clamp(5rem,8vw,7rem) 2rem 3.5rem; }
.sv-contact-intent__head { max-width: 700px; margin-bottom: 2rem; }
.sv-contact-intent__head h2 { margin: .65rem 0 .55rem; color: var(--sv-navy); font-size: clamp(2rem,4vw,3.15rem); letter-spacing: -.04em; }
.sv-contact-intent__head p { margin: 0; color: var(--sv-muted); font-size: .86rem; }
.sv-contact-intent__grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; }
.sv-intent-card { min-width: 0; display: grid; grid-template-columns:auto 1fr auto; align-items:center; gap:1rem; padding:1.2rem; color:var(--sv-navy); text-align:left; background:#fff; border:1px solid var(--sv-line); border-radius:18px; box-shadow:0 12px 34px rgba(9,44,80,.06); cursor:pointer; transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease,background .2s ease; }
.sv-intent-card:hover,.sv-intent-card--active { transform:translateY(-4px); border-color:#8bdcf6; box-shadow:0 20px 42px rgba(9,44,80,.12); }
.sv-intent-card--active { background:linear-gradient(145deg,#f3fbff,#e7f7fd); }
.sv-intent-card>span { width:48px;height:48px;display:grid;place-items:center;color:var(--sv-blue);background:#e5f7fe;border-radius:15px; }
.sv-intent-card p { min-width:0;display:grid;gap:.25rem;margin:0; }
.sv-intent-card strong { font-size:.8rem; }
.sv-intent-card small { color:var(--sv-muted);font-size:.67rem; }
.sv-intent-card>svg { color:#7a93aa; }
.sv-contact-intent+.sv-contact { padding-top: 3.5rem; border-top: 1px solid var(--sv-line); }

@media (max-width: 980px) {
  .sv-journey__intro { grid-template-columns: 1fr; gap: .7rem; }
  .sv-journey__steps { grid-template-columns: 1fr 1fr; }
  .sv-journey__step:nth-child(2) { border-right: 0; }
  .sv-journey__step:nth-child(-n+2) { border-bottom: 1px solid var(--sv-line); }
  .sv-hero--contact .sv-hero__content { padding-right: 330px; }
  .sv-contact-orbit { right: 2rem; width: 280px; }
}
@media (max-width: 768px) {
  .sv-hero--about { min-height: 640px; padding: 6rem 1.25rem 4.5rem; align-items: end; }
  .sv-hero__photo { object-position: 64% center; }
  .sv-hero__veil { background: linear-gradient(0deg,rgba(3,15,35,.96) 0%,rgba(3,18,40,.68) 60%,rgba(3,18,40,.15)); }
  .sv-experience-bar { width: calc(100% - 2rem); grid-template-columns: 1fr; margin-top: -28px; }
  .sv-experience-bar>div { border-right: 0; border-bottom: 1px solid var(--sv-line); }
  .sv-experience-bar>div:last-child { border-bottom: 0; }
  .sv-journey { padding: 6rem 1.25rem 4.5rem; }
  .sv-journey__steps { grid-template-columns: 1fr; }
  .sv-journey__step,.sv-journey__step:nth-child(2) { min-height: auto; border-right: 0; border-bottom: 1px solid var(--sv-line); }
  .sv-journey__step:last-child { border-bottom: 0; }
  .sv-journey__icon { margin-top: 2rem; }
  .sv-hero--contact { min-height: 600px; align-items: start; padding-bottom: 11rem; }
  .sv-hero--contact .sv-hero__content { padding-right: 0; }
  .sv-contact-orbit { top: auto; right: 1.25rem; bottom: 2rem; left: 1.25rem; width: auto; transform: none; }
  .sv-contact-intent { padding: 4.5rem 1.25rem 3rem; }
  .sv-contact-intent__grid { grid-template-columns: 1fr; }
}
@media (max-width: 520px) {
  .sv-hero--about .sv-hero__title,.sv-hero--contact .sv-hero__title { font-size: 2.8rem; }
  .sv-hero--about .sv-hero__trust { display: none; }
  .sv-experience-bar small { white-space: normal; }
  .sv-contact-orbit { padding: 1rem; }
}

/* ==========================================================
   2026 EXPERIENCE SYSTEM — organic waves, portals and journeys
   ========================================================== */
.av-hero{position:relative;min-height:760px;overflow:hidden;color:#fff;background:linear-gradient(120deg,#031329 0%,#082b56 60%,#0b4a7d 100%)}
.av-hero:before{content:'';position:absolute;inset:0;z-index:1;background:radial-gradient(circle at 12% 18%,rgba(59,200,245,.18),transparent 24%),linear-gradient(90deg,rgba(3,16,36,.18),rgba(3,16,36,.02))}
.av-hero__copy{position:relative;z-index:5;box-sizing:border-box;width:min(58vw,820px);padding:clamp(7rem,12vw,10rem) 2.5rem 10rem clamp(2rem,6vw,7rem);margin:0}
.av-hero__copy h1{width:100%;max-width:760px;margin:.8rem 0 1.4rem;font-size:clamp(3.25rem,4.8vw,5.5rem);line-height:.96;letter-spacing:-.058em;text-wrap:normal;word-break:normal;overflow-wrap:normal}
.av-hero__copy>p{width:100%;max-width:570px;margin:0;color:rgba(230,244,254,.76);font-size:1.03rem;line-height:1.75;word-break:normal;overflow-wrap:normal}
.av-hero__actions{display:flex;flex-wrap:wrap;gap:.8rem;margin-top:2rem}.av-hero__actions a{min-height:50px;display:inline-flex;align-items:center;justify-content:center;gap:.55rem;padding:0 1.35rem;border-radius:14px;color:#fff;font-size:.77rem;font-weight:800;text-decoration:none;border:1px solid rgba(255,255,255,.23)}.av-hero__actions a:first-child{color:#061a38;background:#fff;border-color:#fff;box-shadow:0 18px 42px rgba(0,10,28,.25)}
.av-portal{position:absolute;z-index:2;top:60px;right:-4vw;width:min(62vw,940px);height:620px}.av-portal img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:60% center;border-radius:52% 0 0 45%/58% 0 0 42%;box-shadow:-35px 20px 90px rgba(0,10,30,.32)}
.av-portal:after{content:'';position:absolute;inset:0;border-radius:52% 0 0 45%/58% 0 0 42%;background:linear-gradient(90deg,rgba(3,17,38,.92),transparent 35%),linear-gradient(0deg,rgba(3,17,38,.45),transparent 45%)}
.av-portal__ring{position:absolute;z-index:3;pointer-events:none;border:1px solid rgba(88,211,251,.3);border-radius:50%}.av-portal__ring--one{inset:-40px 18% 40px -55px}.av-portal__ring--two{inset:55px 28% 120px 10px}
.av-portal__note{position:absolute;z-index:4;right:7vw;bottom:45px;width:250px;display:grid;gap:.35rem;padding:1.1rem 1.25rem;color:#fff;background:rgba(4,23,53,.64);border:1px solid rgba(91,213,253,.3);border-radius:16px;backdrop-filter:blur(16px)}.av-portal__note span{color:#59d4fc;font-size:.54rem;font-weight:900;letter-spacing:.16em}.av-portal__note strong{font-size:.75rem;line-height:1.5}
.av-wave{position:absolute;z-index:6;right:0;bottom:-1px;left:0;width:100%;height:150px}
.av-manifesto{position:relative;max-width:1240px;margin:0 auto;padding:9rem 2rem 7rem}.av-manifesto__word{position:absolute;top:3.5rem;left:50%;color:#eef7fc;font-size:clamp(8rem,22vw,19rem);font-weight:950;line-height:1;letter-spacing:-.09em;transform:translateX(-50%);user-select:none}.av-manifesto__copy{position:relative;z-index:2;display:grid;grid-template-columns:.34fr .95fr .72fr;gap:2.4rem;align-items:start}.av-manifesto__copy h2{margin:0;color:#071a3b;font-size:clamp(2.6rem,5vw,4.8rem);line-height:1;letter-spacing:-.055em}.av-manifesto__copy p{margin:.4rem 0 0;color:#64748b;font-size:.92rem;line-height:1.8}.av-manifesto__principles{position:relative;z-index:2;display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:5rem}.av-manifesto__principles article{position:relative;min-height:250px;padding:1.7rem;color:#071a3b;background:#fff;border:1px solid #dbe7f1;border-radius:4px 28px 4px 28px;box-shadow:0 18px 45px rgba(7,37,71,.07);transition:.25s ease}.av-manifesto__principles article:hover{color:#fff;background:#071a3b;border-color:#071a3b;transform:translateY(-8px)}.av-manifesto__principles article>span{position:absolute;top:1.4rem;right:1.4rem;color:#9bb0c2;font-size:.62rem;font-weight:900}.av-manifesto__principles svg{margin:3rem 0 1.2rem;color:#0d83bd}.av-manifesto__principles h3{margin:0 0 .6rem;font-size:1rem}.av-manifesto__principles p{margin:0;color:#64748b;font-size:.75rem;line-height:1.7}.av-manifesto__principles article:hover p,.av-manifesto__principles article:hover>span{color:rgba(229,244,253,.66)}.av-manifesto__principles article:hover svg{color:#3bc8f5}
.av-road{position:relative;overflow:hidden;padding:7rem 0 8rem;background:linear-gradient(180deg,#edf7fc,#dff1fa)}.av-road:before,.av-road:after{content:'';position:absolute;width:480px;height:480px;border:1px solid rgba(38,165,211,.17);border-radius:50%}.av-road:before{top:-280px;left:-120px}.av-road:after{right:-180px;bottom:-330px}.av-road__heading{max-width:1180px;margin:0 auto 2rem;padding:0 2rem}.av-road__heading h2{margin:.65rem 0 0;color:#071a3b;font-size:clamp(2.7rem,5vw,4.7rem);line-height:1;letter-spacing:-.055em}.av-road__track{position:relative;max-width:1200px;height:390px;margin:0 auto}.av-road__track>svg{position:absolute;top:80px;left:0;width:100%;height:260px}.av-road__stop{position:absolute;width:210px;padding:1rem;text-align:center}.av-road__stop>span{width:54px;height:54px;display:grid;place-items:center;margin:0 auto 1rem;color:#fff;background:#071a3b;border:6px solid #fff;border-radius:50%;box-shadow:0 0 0 3px #3bc8f5,0 12px 28px rgba(7,40,76,.18)}.av-road__stop h3{margin:0;color:#071a3b;font-size:.95rem}.av-road__stop p{margin:.35rem 0 0;color:#64748b;font-size:.7rem}.av-road__stop--1{top:145px;left:0}.av-road__stop--2{top:25px;left:27%}.av-road__stop--3{top:160px;left:55%}.av-road__stop--4{top:50px;right:0}
.av-bento{max-width:1240px;display:grid;grid-template-columns:repeat(12,1fr);gap:1rem;margin:0 auto;padding:8rem 2rem}.av-bento article{overflow:hidden;border:1px solid #dbe7f1;border-radius:24px}.av-bento__lead{grid-column:span 7;min-height:430px;padding:3rem;color:#fff;background:linear-gradient(145deg,#061630,#0b4a7c)}.av-bento__lead h2{margin:.8rem 0 1rem;font-size:clamp(2.5rem,4vw,4rem);line-height:1;letter-spacing:-.05em}.av-bento__lead p{max-width:500px;color:rgba(226,242,252,.7);line-height:1.7}.av-bento__lead a{display:inline-flex;align-items:center;gap:.5rem;margin-top:4rem;color:#3bc8f5;font-size:.78rem;font-weight:800;text-decoration:none}.av-bento__visual{position:relative;grid-column:span 5;min-height:430px;display:grid;place-items:center;background:radial-gradient(circle,#fff,#e3f3fb)}.av-bento__visual img{width:115%;max-width:none;filter:drop-shadow(0 24px 20px rgba(7,26,59,.23))}.av-bento__visual span{position:absolute;right:1.3rem;bottom:1.3rem;padding:.7rem 1rem;color:#fff;background:#071a3b;border-radius:999px;font-size:.64rem;font-weight:800}.av-bento__value{grid-column:span 4;padding:2rem;background:#f7fbfe}.av-bento__value svg{color:#0d83bd}.av-bento__value h3{margin:2.5rem 0 .6rem;color:#071a3b}.av-bento__value p{margin:0;color:#64748b;font-size:.78rem;line-height:1.65}
.av-finale{position:relative;min-height:440px;display:grid;place-items:center;padding:8rem 2rem 5rem;text-align:center;color:#fff;background:radial-gradient(circle at 50% 100%,#0e5a91,#061630 68%)}.av-finale>svg{position:absolute;top:-1px;left:0;width:100%;height:110px}.av-finale>div{position:relative}.av-finale span{color:#3bc8f5;font-size:.65rem;font-weight:900;letter-spacing:.16em}.av-finale h2{margin:.8rem 0 1.7rem;font-size:clamp(2.7rem,5vw,4.8rem);letter-spacing:-.055em}.av-finale a{display:inline-flex;align-items:center;gap:.55rem;padding:.95rem 1.3rem;color:#071a3b;background:#fff;border-radius:13px;font-size:.78rem;font-weight:850;text-decoration:none}

.ct-hero{position:relative;min-height:610px;display:grid;place-items:center;overflow:hidden;padding:7rem 2rem 11rem;color:#fff;text-align:center;background:linear-gradient(135deg,#05142e,#073865 60%,#0d6898)}.ct-hero__waves{position:absolute;inset:0}.ct-hero__waves i{position:absolute;left:50%;border:1px solid rgba(91,215,255,.2);border-radius:48% 52% 47% 53%;transform:translateX(-50%) rotate(-7deg)}.ct-hero__waves i:nth-child(1){top:-390px;width:1100px;height:780px}.ct-hero__waves i:nth-child(2){top:-250px;width:850px;height:620px}.ct-hero__waves i:nth-child(3){top:-110px;width:600px;height:470px}.ct-hero__copy{position:relative;z-index:2;max-width:920px}.ct-hero__copy>span{color:#4dd0fb;font-size:.65rem;font-weight:900;letter-spacing:.19em}.ct-hero__copy h1{margin:.8rem 0 1rem;font-size:clamp(3.5rem,7vw,6.8rem);line-height:.92;letter-spacing:-.065em}.ct-hero__copy p{margin:0;color:rgba(231,245,253,.7);font-size:1.05rem}.ct-hero__channels{position:absolute;z-index:4;bottom:55px;left:50%;width:min(1040px,calc(100% - 3rem));display:grid;grid-template-columns:repeat(3,1fr);gap:1px;overflow:hidden;background:rgba(103,214,249,.18);border:1px solid rgba(103,214,249,.25);border-radius:20px;box-shadow:0 22px 60px rgba(0,12,32,.26);transform:translateX(-50%);backdrop-filter:blur(18px)}.ct-hero__channels>a,.ct-hero__channels>div{display:flex;align-items:center;justify-content:center;gap:.9rem;min-height:90px;padding:1rem;color:#fff;background:rgba(4,24,56,.65);text-decoration:none}.ct-hero__channels svg{color:#4bd0fc}.ct-hero__channels span{display:grid;gap:.25rem;text-align:left}.ct-hero__channels small{color:rgba(226,242,252,.57);font-size:.6rem}.ct-hero__channels strong{font-size:.75rem}.ct-hero__wave{position:absolute;right:0;bottom:-1px;left:0;width:100%;height:110px}
.ct-intent{display:grid;grid-template-columns:.72fr 1.28fr;gap:5rem;max-width:1240px;margin:0 auto;padding:7rem 2rem;background:#f3f8fd}.ct-intent__copy{position:sticky;top:120px;align-self:start}.ct-intent__copy h2{margin:.7rem 0 1rem;color:#071a3b;font-size:clamp(2.7rem,4.5vw,4.2rem);line-height:1;letter-spacing:-.055em}.ct-intent__copy p{max-width:420px;color:#64748b;font-size:.86rem;line-height:1.75}.ct-intent__list{display:grid;gap:.8rem}.ct-intent__list button{display:grid;grid-template-columns:54px 1fr auto;align-items:center;gap:1rem;min-height:120px;padding:1rem 1.3rem;color:#071a3b;text-align:left;background:#fff;border:1px solid #dbe7f1;border-radius:8px 28px 8px 28px;box-shadow:0 14px 35px rgba(7,38,72,.06);cursor:pointer;transition:.24s ease}.ct-intent__list button:hover,.ct-intent__list .ct-intent__active{color:#fff;background:#071a3b;border-color:#071a3b;transform:translateX(-12px)}.ct-intent__list button>b{display:grid;place-items:center;width:44px;height:44px;color:#3bc8f5;background:#071a3b;border-radius:50%;font-size:.62rem}.ct-intent__list button:hover>b,.ct-intent__list .ct-intent__active>b{color:#071a3b;background:#3bc8f5}.ct-intent__list button>span{display:grid;grid-template-columns:auto 1fr;gap:.3rem 1rem;align-items:center}.ct-intent__list button>span svg{grid-row:span 2;color:#0d83bd}.ct-intent__list strong{font-size:.9rem}.ct-intent__list small{color:#64748b;font-size:.68rem}.ct-intent__list button:hover small,.ct-intent__list .ct-intent__active small{color:rgba(226,242,252,.65)}
.ct-concierge{display:grid;grid-template-columns:.78fr 1.22fr;max-width:1240px;margin:0 auto;padding:0 2rem 8rem}.ct-concierge__guide{position:relative;overflow:hidden;padding:3rem;color:#fff;background:linear-gradient(150deg,#061630,#0a4b7e);border-radius:36px 0 0 8px}.ct-concierge__guide:after{content:'';position:absolute;right:-140px;bottom:-160px;width:390px;height:390px;border:1px solid rgba(66,202,247,.2);border-radius:50%;box-shadow:0 0 0 45px rgba(66,202,247,.035),0 0 0 90px rgba(66,202,247,.02)}.ct-concierge__guide h2{position:relative;z-index:1;margin:.7rem 0 1rem;font-size:clamp(2.3rem,4vw,3.8rem);line-height:1;letter-spacing:-.05em}.ct-concierge__guide>p{position:relative;z-index:1;color:rgba(226,242,252,.66);font-size:.82rem;line-height:1.7}.ct-concierge__guide ol{position:relative;z-index:1;display:grid;gap:1.2rem;margin:3rem 0;padding:0;list-style:none}.ct-concierge__guide li{display:flex;align-items:center;gap:1rem}.ct-concierge__guide li>span{width:36px;height:36px;display:grid;place-items:center;flex:0 0 auto;color:#071a3b;background:#3bc8f5;border-radius:50%;font-size:.64rem;font-weight:900}.ct-concierge__guide li p,.ct-concierge__promise p{display:grid;gap:.2rem;margin:0}.ct-concierge__guide li strong,.ct-concierge__promise strong{font-size:.77rem}.ct-concierge__guide li small,.ct-concierge__promise small{color:rgba(224,241,251,.58);font-size:.64rem}.ct-concierge__promise{position:relative;z-index:1;display:flex;align-items:center;gap:.8rem;padding:1rem;border:1px solid rgba(91,211,251,.25);border-radius:14px;background:rgba(255,255,255,.05)}.ct-concierge__promise svg{color:#3bc8f5}.ct-form{border-radius:0 36px 8px 0;box-shadow:0 26px 65px rgba(7,37,71,.13)}
.ct-location{position:relative;max-width:1320px;height:540px;margin:0 auto 7rem;padding:0 2rem}.ct-location__map{width:100%;height:100%;overflow:hidden;border-radius:48px 8px 48px 8px;filter:saturate(.75) contrast(1.05);box-shadow:0 25px 65px rgba(7,37,71,.14)}.ct-location__map iframe{width:100%;height:100%;border:0}.ct-location__card{position:absolute;top:50%;left:5rem;width:310px;padding:2rem;background:rgba(255,255,255,.94);border:1px solid rgba(191,219,235,.8);border-radius:8px 30px 8px 30px;box-shadow:0 20px 50px rgba(7,37,71,.18);backdrop-filter:blur(14px);transform:translateY(-50%)}.ct-location__card h2{margin:.6rem 0 1.2rem;color:#071a3b;font-size:2rem}.ct-location__card p{display:flex;align-items:center;gap:.65rem;color:#64748b;font-size:.75rem}.ct-location__card svg{color:#0d83bd}.ct-location__card a{display:inline-flex;align-items:center;gap:.5rem;margin-top:1rem;color:#071a3b;font-size:.72rem;font-weight:850;text-decoration:none}

@media(max-width:900px){.av-hero{min-height:920px}.av-hero__copy{width:auto;padding:6rem 1.5rem 0}.av-portal{top:420px;right:-18%;width:110%;height:470px}.av-portal:after{background:linear-gradient(0deg,rgba(3,17,38,.65),transparent 48%)}.av-portal__note{right:12%;bottom:35px}.av-manifesto__copy{grid-template-columns:1fr}.av-manifesto__principles{grid-template-columns:1fr}.av-road__track{height:auto;display:grid;gap:1rem;padding:1rem 1.5rem}.av-road__track>svg{display:none}.av-road__stop{position:relative!important;top:auto!important;right:auto!important;left:auto!important;width:auto;display:grid;grid-template-columns:auto 1fr;gap:.15rem 1rem;text-align:left}.av-road__stop>span{grid-row:span 2;margin:0}.av-bento__lead,.av-bento__visual{grid-column:span 12}.av-bento__value{grid-column:span 4}.ct-intent{grid-template-columns:1fr;gap:2rem}.ct-intent__copy{position:relative;top:auto}.ct-concierge{grid-template-columns:1fr}.ct-concierge__guide{border-radius:30px 30px 0 0}.ct-form{border-radius:0 0 30px 30px}}
@media(max-width:640px){.av-hero{min-height:870px}.av-hero__copy h1{font-size:3.25rem}.av-portal{top:430px;height:420px}.av-portal__note{right:1rem;bottom:30px;width:220px}.av-manifesto{padding:7rem 1.25rem 5rem}.av-manifesto__word{top:4rem}.av-manifesto__principles{margin-top:3rem}.av-road{padding:5rem 0}.av-road__heading{padding:0 1.25rem}.av-bento{padding:5rem 1.25rem}.av-bento__lead{min-height:380px;padding:2rem}.av-bento__visual{min-height:320px}.av-bento__value{grid-column:span 12}.ct-hero{min-height:760px;padding:5rem 1.25rem 19rem}.ct-hero__copy h1{font-size:3.5rem}.ct-hero__channels{bottom:60px;grid-template-columns:1fr;width:calc(100% - 2rem)}.ct-hero__channels>a,.ct-hero__channels>div{min-height:66px;justify-content:flex-start;padding-left:1.4rem}.ct-intent{padding:5rem 1.25rem}.ct-intent__list button{grid-template-columns:44px 1fr auto}.ct-intent__list button:hover,.ct-intent__list .ct-intent__active{transform:none}.ct-concierge{padding:0 1.25rem 5rem}.ct-concierge__guide,.ct-form{padding:1.5rem}.ct-location{height:620px;padding:0 1.25rem;margin-bottom:5rem}.ct-location__map{height:380px}.ct-location__card{top:auto;right:2rem;bottom:0;left:2rem;width:auto;transform:none}}
</style>
