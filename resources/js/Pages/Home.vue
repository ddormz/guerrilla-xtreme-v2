<template>
  <AppLayout>
    <Head title="Guerrilla Xtrem - Inicio" />

    <div class="home-page">
      <section class="hero-section">
        <div class="hero-background">
          <div class="glow-orb orb-1"></div>
          <div class="glow-orb orb-2"></div>
        </div>

        <div class="hero-content full-width-container">
          <div class="hero-logo-wrapper">
            <div class="logo-glow"></div>
            <img src="/img/logo.png" alt="GX Logo" class="hero-logo" />
          </div>

          <h1 class="hero-title">
            <span class="text-gradient">GUERRILLA</span> XTREM
          </h1>
          <p class="hero-subtitle">MÁS QUE UN EQUIPO, UNA FAMILIA</p>
          <p class="hero-tagline">DOMINANDO LA ARENA XTREM</p>

          <div class="hero-actions mobile-hero-actions">
            <a href="#roster" class="btn btn-primary btn-lg">Conoce al Equipo</a>
            <Link v-if="$page.props.auth?.user?.role === 'miembro_gx' || $page.props.auth?.user?.role === 'admin'" href="/liga" class="btn btn-outline btn-lg">Ver Clasificación</Link>
          </div>
        </div>
      </section>

      <section v-if="featuredEvents && featuredEvents.length > 0" class="tournament-promo">
        <div class="scanline"></div>
        <div v-for="event in featuredEvents" :key="event.id" class="promo-content full-width-container mb-xl last:mb-0">
          <div class="promo-badge">PRÓXIMO EVENTO</div>
          <h2 class="promo-title uppercase">{{ event.name }}</h2>
          <div class="promo-details flex-wrap">
            <div class="detail-item">
              <span class="detail-icon">📅</span>
              <span class="detail-text">{{ new Date(event.event_date).toLocaleDateString('es-ES', { day:'numeric', month:'long', year:'numeric' }) }}</span>
            </div>
            <div v-if="event.time" class="detail-item">
              <span class="detail-icon">⏰</span>
              <span class="detail-text">{{ event.time }}</span>
            </div>
            <div v-if="event.location" class="detail-item">
              <span class="detail-icon">📍</span>
              <span class="detail-text">{{ event.location }}</span>
            </div>
          </div>
          
          <div class="promo-info-row mt-md mb-lg">
            <div
              v-for="(prize, prizeIndex) in prizeLines(event.prizes)"
              :key="`${event.id}-prize-${prizeIndex}`"
              class="info-tag prizes"
            >
              <span class="tag-icon">🏆</span>
              <span class="tag-text">{{ prize }}</span>
            </div>
            <div class="info-tag cost">
              <span class="tag-icon">💰</span>
              <span class="tag-text">
                {{ new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(event.registration_cost) }}
              </span>
            </div>
          </div>
          <Link :href="route('tournaments.register', event.id)" class="btn btn-glow-primary uppercase">Inscribirse Ahora</Link>
        </div>
      </section>

      <section id="roster" class="roster-section full-width-container">
        <div class="section-header text-center">
          <h2 class="section-title"><span class="text-gradient">TEAM</span> ROSTER</h2>
          <div class="section-divider"></div>
          <p class="section-subtitle">Los bladers que forjan nuestra leyenda</p>
        </div>

        <div class="roster-grid">
          <article
            v-for="member in teamMembers"
            :key="member.id"
            class="member-card"
            :class="{ 'has-flip': member.lock_chip_url, 'is-flipped': isMemberFlipped(member.id) }"
            @click="toggleMemberFlip(member, $event)"
          >
            <div v-if="member.role_title" class="role-ribbon" :class="getRoleClass(member.role_title)">
              {{ member.role_title }}
            </div>

            <div class="member-photo-wrapper flip-container">
              <div class="flipper">
                <div class="front">
                  <div class="member-photo-glow"></div>
                  <img
                    :src="member.photo_url || '/img/placeholders/blader.png'"
                    :alt="member.blader_name"
                    class="member-photo"
                    @error="handleImageError"
                  />
                </div>

                <div v-if="member.lock_chip_url" class="back">
                  <div class="member-photo-glow lock-chip-glow"></div>
                  <img
                    :src="member.lock_chip_url"
                    :alt="member.blader_name + ' Lock Chip'"
                    class="member-photo lock-chip-img"
                    @error="handleImageError"
                  />
                </div>
              </div>
            </div>

            <div class="member-info">
              <h3 class="member-name">{{ member.blader_name || member.name }}</h3>

              <div class="member-socials">
                <a v-if="member.instagram" :href="`https://instagram.com/${member.instagram}`" target="_blank" class="social-link instagram" title="Instagram">
                  <span class="social-icon">📸</span>
                </a>
                <a v-if="member.tiktok" :href="`https://tiktok.com/@${member.tiktok}`" target="_blank" class="social-link tiktok" title="TikTok">
                  <span class="social-icon">🎬</span>
                </a>
                <a href="#" class="social-link profile" title="Ver Perfil">
                  <span class="social-icon">👤</span>
                </a>
              </div>
            </div>
          </article>
        </div>
      </section>

      <section class="community-cta">
        <div class="cta-card">
          <img src="/img/logo.png" alt="GX" class="cta-heartbeat-logo" />
          <div class="cta-content">
            <h2 class="cta-title">¿QUIERES SER PARTE DE LA ÉLITE?</h2>
            <p class="cta-text">Únete a nuestra comunidad de WhatsApp y mantente al tanto de todos los entrenamientos, torneos y novedades.</p>
            <a href="https://chat.whatsapp.com/Kt6t6pmBxwFJc01iZn3hhs" target="_blank" class="btn btn-community btn-lg">
              <span class="btn-icon">💬</span> Únete a la Comunidad
            </a>
          </div>
        </div>
      </section>

      <footer class="home-footer">
        <div class="footer-shell full-width-container">
          <div class="footer-brand">
            <img src="/img/logo.png" alt="GX" class="footer-logo" />
            <p>El equipo más temido de la arena Xtrem. Pasión, técnica y hermandad en cada batalla.</p>
          </div>

          <div class="footer-columns">
            <div class="footer-group">
              <h4>Navegación</h4>
              <ul class="footer-list">
                <li><Link href="/liga">Liga</Link></li>
                <li><Link href="/torneos">Torneos</Link></li>
                <li><Link href="/rifas">Rifas</Link></li>
              </ul>
            </div>

            <div class="footer-group">
              <h4>Comunidad</h4>
              <div class="footer-social-icons">
                <a href="https://instagram.com/guerrilla_xtrem" target="_blank" class="icon-btn instagram" title="Instagram">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                </a>
                <a href="https://www.tiktok.com/@gxtreme_beyblade" target="_blank" class="icon-btn tiktok" title="TikTok">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path></svg>
                </a>
                <a href="https://chat.whatsapp.com/Kt6t6pmBxwFJc01iZn3hhs" target="_blank" class="icon-btn whatsapp" title="WhatsApp">
                  <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                </a>
              </div>
              <p class="mt-sm text-xs opacity-50 tracking-wider">@guerrilla_xtrem</p>
            </div>
          </div>
        </div>

        <div class="footer-bottom">
          <p>&copy; 2026 Guerrilla Xtrem. Todos los derechos reservados.</p>
        </div>
      </footer>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  teamMembers: Array,
  featuredEvents: Array
});

function getRoleClass(role) {
  if (!role) return '';
  const r = role.toLowerCase();
  if (r.includes('fundador')) return 'role-founder';
  if (r.includes('co-fundador')) return 'role-co-founder';
  if (r.includes('arbitro') || r.includes('árbitro')) return 'role-referee';
  if (r.includes('gx')) return 'role-gx';
  return '';
}

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};

const flippedMemberIds = ref([]);

const isMemberFlipped = (memberId) => flippedMemberIds.value.includes(memberId);

const toggleMemberFlip = (member, event) => {
  if (!member?.lock_chip_url) return;
  if (event?.target?.closest?.('.member-socials')) return;

  if (isMemberFlipped(member.id)) {
    flippedMemberIds.value = flippedMemberIds.value.filter((id) => id !== member.id);
    return;
  }

  flippedMemberIds.value = [...flippedMemberIds.value, member.id];
};

const prizeLines = (prizes) => {
  if (!prizes) return [];
  const normalized = String(prizes).replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();
  if (!normalized) return [];

  const lines = normalized
    .split(/\n+/)
    .map((line) => line.trim())
    .filter(Boolean);

  if (lines.length > 1) return lines;

  const fallbackLines = normalized
    .split(/\s*(?:\||;|•|·|\/)\s*|\.\s+(?=[A-ZÁÉÍÓÚÑ0-9])/g)
    .map((line) => line.trim())
    .filter(Boolean);

  return fallbackLines.length > 1 ? fallbackLines : [normalized];
};
</script>

<style scoped>
.flip-container {
  perspective: 1000px;
  position: relative;
  width: 100%;
  height: 100%;
}

.member-card.has-flip.is-flipped .flipper {
  transform: rotateY(180deg);
}

@media (hover: hover) and (pointer: fine) {
  .member-card.has-flip:hover .flipper {
    transform: rotateY(180deg);
  }
}

.flipper {
  transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1);
  transform-style: preserve-3d;
  width: 100%;
  height: 100%;
  position: relative;
}

.front,
.back {
  backface-visibility: hidden;
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.front {
  z-index: 2;
  transform: rotateY(0deg);
}

.back {
  transform: rotateY(180deg);
}

.member-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  border-radius: 50%;
  border: 4px solid #222;
  display: block;
  margin: 0 auto;
}

.lock-chip-img {
  object-fit: cover;
  background: var(--bg-card);
  border: 3px solid var(--gx-red);
}

.lock-chip-glow {
  background: var(--gx-red) !important;
  opacity: 0.2;
}

.full-width-container {
  width: 100%;
  max-width: 100%;
  padding-left: var(--space-xl);
  padding-right: var(--space-xl);
}

.hero-section {
  position: relative;
  min-height: 85vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  text-align: center;
  padding: var(--space-2xl) 0;
}

.hero-background {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at center, rgba(225, 6, 0, 0.15) 0%, transparent 70%);
  z-index: 0;
}

.glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.3;
  z-index: 0;
}

.orb-1 {
  width: 400px;
  height: 400px;
  background: var(--gx-red);
  top: -100px;
  right: -100px;
  animation: float 20s infinite alternate;
}

.orb-2 {
  width: 300px;
  height: 300px;
  background: var(--gx-red-dark);
  bottom: -50px;
  left: -50px;
  animation: float 15s infinite alternate-reverse;
}

.hero-content {
  position: relative;
  z-index: 1;
}

.hero-logo-wrapper {
  position: relative;
  display: inline-block;
  margin-bottom: var(--space-xl);
}

.hero-logo {
  height: 200px;
  width: auto;
  filter: drop-shadow(0 0 30px rgba(225, 6, 0, 0.5));
  animation: pulse-glow 4s infinite;
}

.logo-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 150%;
  height: 150%;
  background: radial-gradient(circle, rgba(225, 6, 0, 0.2) 0%, transparent 70%);
  z-index: -1;
}

.hero-title {
  font-size: clamp(3rem, 8vw, 6rem);
  font-weight: 800;
  margin-bottom: var(--space-xs);
  letter-spacing: -0.02em;
  line-height: 1;
}

.hero-subtitle {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gx-red-light);
  margin-bottom: var(--space-xs);
  letter-spacing: 0.2em;
}

.hero-tagline {
  font-size: 1.1rem;
  color: var(--text-muted);
  margin-bottom: var(--space-2xl);
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.hero-actions {
  display: flex;
  gap: var(--space-md);
  justify-content: center;
}

.tournament-promo {
  position: relative;
  background: linear-gradient(135deg, #0a0a0a 0%, #1a0000 50%, #0a0a0a 100%);
  padding: var(--space-2xl) 0;
  overflow: hidden;
  border-top: 1px solid rgba(225, 6, 0, 0.3);
  border-bottom: 1px solid rgba(225, 6, 0, 0.3);
}

.tournament-promo::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(225, 6, 0, 0.15) 0%, transparent 70%);
  pointer-events: none;
  animation: neonPulse 4s ease-in-out infinite alternate;
}

@keyframes neonPulse {
  0% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
  100% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
}

.scanline {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 50%, rgba(0, 0, 0, 0.5) 50%);
  background-size: 100% 4px;
  pointer-events: none;
  opacity: 0.1;
}

.promo-content {
  position: relative;
  z-index: 1;
  text-align: center;
}

.promo-badge {
  display: inline-block;
  background: var(--gx-red);
  color: white;
  padding: 4px 16px;
  font-weight: 700;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: var(--space-md);
  clip-path: polygon(10% 0%, 100% 0%, 90% 100%, 0% 100%);
  box-shadow: 0 0 20px rgba(225, 6, 0, 0.5), 0 0 40px rgba(225, 6, 0, 0.2);
  animation: badgePulse 2s ease-in-out infinite alternate;
}

@keyframes badgePulse {
  0% { box-shadow: 0 0 20px rgba(225, 6, 0, 0.5), 0 0 40px rgba(225, 6, 0, 0.2); }
  100% { box-shadow: 0 0 30px rgba(225, 6, 0, 0.7), 0 0 60px rgba(225, 6, 0, 0.3); }
}

.promo-title {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: var(--space-lg);
  color: white;
  text-shadow: 0 0 20px rgba(225, 6, 0, 0.5), 0 0 40px rgba(225, 6, 0, 0.2);
}

.promo-details {
  display: flex;
  justify-content: center;
  gap: var(--space-xl);
  margin-bottom: var(--space-xl);
  flex-wrap: wrap;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.detail-icon {
  font-size: 1.25rem;
}

.detail-text {
  font-weight: 500;
  color: var(--text-secondary);
}

.promo-info-row {
  display: flex;
  justify-content: center;
  gap: var(--space-md);
  flex-wrap: wrap;
}

.info-tag {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 6px 12px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9rem;
}

.info-tag.prizes {
  border-color: rgba(250, 204, 21, 0.3);
  background: rgba(250, 204, 21, 0.05);
  color: #facc15;
}

.info-tag.cost {
  border-color: rgba(34, 197, 94, 0.3);
  background: rgba(34, 197, 94, 0.05);
  color: #22c55e;
}

.roster-section {
  padding: var(--space-2xl) var(--space-xl);
}

.roster-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--space-xl);
  margin-top: var(--space-2xl);
}

.member-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--space-xl);
  text-align: center;
  transition: all var(--transition-base);
  position: relative;
  overflow: hidden;
}

.member-card.has-flip {
  cursor: pointer;
}

.member-card:hover {
  transform: translateY(-8px);
  background: var(--bg-card-hover);
  border-color: var(--gx-red);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.member-photo-wrapper {
  position: relative;
  width: 180px;
  height: 180px;
  margin: 0 auto var(--space-lg);
}

.member-photo-glow {
  position: absolute;
  inset: -10px;
  background: radial-gradient(circle, var(--gx-red) 0%, transparent 70%);
  opacity: 0;
  transition: opacity var(--transition-base);
  border-radius: 50%;
}

.member-card:hover .member-photo-glow {
  opacity: 0;
}

.role-ribbon {
  position: absolute;
  top: 14px;
  left: -40px;
  min-width: 180px;
  text-align: center;
  padding: 4px 10px;
  transform: rotate(-35deg);
  font-size: 0.68rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #fff;
  background: linear-gradient(90deg, var(--gx-red-dark), var(--gx-red));
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.35);
  z-index: 3;
}

.role-founder {
  background: linear-gradient(90deg, #b8860b, #ffd700) !important;
  color: #141414 !important;
}

.role-co-founder {
  background: linear-gradient(90deg, #708090, #d1d5db) !important;
  color: #111 !important;
}

.role-referee {
  background: linear-gradient(90deg, #1d4ed8, #3b82f6) !important;
}

.role-gx {
  background: linear-gradient(90deg, #7f1d1d, var(--gx-red)) !important;
}

.member-name {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: var(--space-sm);
}

.member-socials {
  display: flex;
  justify-content: center;
  gap: var(--space-sm);
}

.social-link {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--bg-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
  color: var(--text-primary);
  text-decoration: none;
}

.social-link:hover {
  transform: translateY(-2px);
  background: var(--gx-red-dark);
}

.community-cta {
  padding: 80px 20px;
  display: flex;
  justify-content: center;
  background: linear-gradient(180deg, transparent 0%, rgba(225, 6, 0, 0.05) 100%);
}

.cta-card {
  position: relative;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  padding: 60px 40px;
  border-radius: var(--radius-lg);
  max-width: 860px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 40px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

.cta-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, var(--gx-red), var(--gx-red-dark));
}

.cta-heartbeat-logo {
  flex-shrink: 0;
  width: 140px;
  height: 140px;
  animation: heartbeat 1.8s ease-in-out infinite;
  filter: drop-shadow(0 0 18px rgba(225, 6, 0, 0.5));
}

.cta-content {
  flex: 1;
  text-align: left;
  position: relative;
  z-index: 2;
}

.cta-title {
  color: var(--gx-red);
  font-size: 2rem;
  margin-bottom: 1rem;
}

.cta-text {
  color: var(--text-secondary);
  margin-bottom: var(--space-lg);
  max-width: 500px;
}

.btn-community {
  background: linear-gradient(135deg, #16a34a, #22c55e);
  color: #fff;
  border: 1px solid rgba(34, 197, 94, 0.65);
  box-shadow: 0 12px 24px rgba(22, 163, 74, 0.22);
}

.btn-community:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 30px rgba(22, 163, 74, 0.32);
}

.home-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  background: radial-gradient(circle at top, rgba(225, 6, 0, 0.08), transparent 45%), #050505;
}

.footer-shell {
  padding-top: var(--space-2xl);
  padding-bottom: var(--space-xl);
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
  gap: var(--space-2xl);
  align-items: start;
}

.footer-brand p {
  color: var(--text-secondary);
  max-width: 420px;
}

.footer-logo {
  height: 76px;
  margin-bottom: var(--space-md);
  filter: drop-shadow(0 0 10px var(--gx-red));
}

.footer-columns {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-xl);
}

.footer-group h4 {
  margin-bottom: var(--space-sm);
}

.footer-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 8px;
}

.footer-list a {
  color: var(--text-secondary);
}

.footer-list a:hover {
  color: var(--text-primary);
}

.footer-social-icons {
  display: flex;
  gap: var(--space-sm);
}

.icon-btn {
  width: 42px;
  height: 42px;
  background: var(--bg-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
  color: white;
  transition: all var(--transition-base);
  border: 1px solid var(--border-color);
}

.icon-btn:hover {
  background: var(--gx-red);
  border-color: var(--gx-red);
  transform: translateY(-4px);
  box-shadow: 0 5px 15px rgba(225, 6, 0, 0.3);
}

.icon-btn.instagram:hover { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285aeb 90%); border-color: transparent; }
.icon-btn.tiktok:hover { background: #000; color: #00f2ea; border-color: #ff0050; }
.icon-btn.whatsapp:hover { background: #25d366; border-color: #25d366; }

.btn-glow-primary {
  background: var(--gx-red);
  color: white;
  border: none;
  box-shadow: 0 0 20px rgba(225, 6, 0, 0.4);
  padding: 14px 28px;
  font-weight: 800;
  transition: all 0.3s ease;
}

.btn-glow-primary:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 0 35px rgba(225, 6, 0, 0.6);
  filter: brightness(1.1);
}

.footer-bottom {
  padding: var(--space-md) 0 calc(var(--space-md) + env(safe-area-inset-bottom, 0px));
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  text-align: center;
  color: var(--text-muted);
  font-size: 0.85rem;
}

@keyframes rotate-logo {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes heartbeat {
  0%, 100% { transform: scale(1); }
  25% { transform: scale(1.06); }
  45% { transform: scale(0.97); }
  65% { transform: scale(1.08); }
}

@media (max-width: 1024px) {
  .footer-shell {
    grid-template-columns: 1fr;
    text-align: center;
    gap: var(--space-xl);
  }
  
  .footer-brand p {
    margin-left: auto;
    margin-right: auto;
  }

  .footer-social-icons {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .hero-logo {
    height: 140px;
  }

  .hero-title {
    font-size: 3.5rem;
  }

  .hero-subtitle {
    font-size: 1.1rem;
    letter-spacing: 0.1em;
  }

  .cta-card {
    flex-direction: column;
    text-align: center;
    padding: var(--space-xl);
  }

  .cta-heartbeat-logo {
    width: 100px;
    height: 100px;
  }

  .cta-title {
    font-size: 1.5rem;
  }

  .footer-columns {
    grid-template-columns: 1fr;
    gap: var(--space-lg);
  }
}

@media (max-width: 480px) {
  .hero-title {
    font-size: 2.8rem;
  }

  .hero-actions {
    flex-direction: column;
    padding: 0 var(--space-md);
  }

  .promo-title {
    font-size: 1.8rem;
  }

  .promo-details {
    flex-direction: column;
    align-items: center;
    gap: var(--space-sm);
  }

  .full-width-container {
    padding-left: var(--space-md);
    padding-right: var(--space-md);
  }

  .roster-grid {
    grid-template-columns: 1fr;
  }

  .member-photo-wrapper {
    width: 150px;
    height: 150px;
  }
}
</style>
