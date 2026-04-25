<template>
  <div class="app-layout">
    <!-- Top Navbar -->
    <header class="navbar">
      <div class="navbar-container">
        <!-- Left: Logo -->
        <Link :href="route('home')" class="navbar-logo">
          <img src="/img/logo.png" alt="GX" class="logo-img" />
          <span class="logo-text desktop-only">
            <span class="text-gradient">GUERRILLA</span> XTREM
          </span>
        </Link>

        <!-- Center: Desktop Navigation -->
        <nav class="navbar-nav desktop-only centered-nav">
          <Link :href="route('home')" class="nav-link" :class="{ active: $page.component === 'Home' }">
            <span class="nav-icon">🏠</span> Inicio
          </Link>
          <Link href="/rifas" class="nav-link" :class="{ active: $page.url.startsWith('/rifas') }">
            <span class="nav-icon">🎟️</span> Rifas
          </Link>
          <Link v-if="auth?.user?.role === 'miembro_gx' || auth?.user?.role === 'admin'" href="/liga" class="nav-link" :class="{ active: $page.url.startsWith('/liga') }">
            <span class="nav-icon">🏆</span> Liga
          </Link>
          <Link v-if="auth?.user?.role === 'miembro_gx' || auth?.user?.role === 'admin'" href="/ranking" class="nav-link" :class="{ active: $page.url.startsWith('/ranking') }">
            <span class="nav-icon">🏅</span> Ranking
          </Link>
          <Link :href="route('tournaments.index')" class="nav-link" :class="{ active: $page.url.startsWith('/torneos') }">
            <span class="nav-icon">⚔️</span> Torneos
          </Link>
          
          <template v-if="auth?.user?.role && ['miembro', 'miembro_gx', 'arbitro_gx', 'admin'].includes(auth.user.role)">
            <div class="nav-divider"></div>
            <Link href="/finanzas" class="nav-link" :class="{ active: $page.url.startsWith('/finanzas') }">
              <span class="nav-icon">💰</span> Finanzas
            </Link>
            <Link v-if="['arbitro_gx', 'admin'].includes(auth.user.role)" :href="route('referee.dashboard')" class="nav-link" :class="{ active: $page.url.startsWith('/arbitrio') }">
              <span class="nav-icon">⚖️</span> Arbitraje
            </Link>
          </template>
        </nav>

        <!-- Right Side: Auth/User & Mobile Toggle -->
        <div class="navbar-right">
          <!-- Theme Toggle -->
          <button @click="toggleTheme" class="theme-toggle-btn" :title="isDark ? 'Modo Claro' : 'Modo Oscuro'">
            <span v-if="isDark">☀️</span>
            <span v-else>🌙</span>
          </button>

          <div v-if="auth?.user?.role === 'admin'" class="desktop-only text-sm">
            <Link href="/admin" class="nav-link" :class="{ active: $page.url.startsWith('/admin') }">
              <span class="nav-icon">⚙️</span> Admin
            </Link>
          </div>

          <!-- Auth Navigation -->
          <div class="header-auth">
            <template v-if="auth?.user">
              <div class="user-menu-trigger">
                <span class="user-name">{{ auth.user.name }}</span>
                <span class="role-badge" v-if="auth.user.role !== 'miembro'">{{ auth.user.role }}</span>
              </div>
              <Link :href="route('profile.edit')" class="btn btn-outline btn-sm desktop-only">Mi Perfil</Link>
              <Link :href="route('logout')" method="post" as="button" class="btn btn-primary btn-sm desktop-only">Salir</Link>
            </template>
            <template v-else>
              <Link :href="route('login')" class="btn btn-primary btn-sm">Ingresar</Link>
            </template>
          </div>
          
          <!-- Mobile Menu Toggle -->
          <button class="mobile-only menu-toggle" @click="mobileMenuOpen = !mobileMenuOpen">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="6" x2="21" y2="6" v-if="!mobileMenuOpen"/><line x1="3" y1="12" x2="21" y2="12" v-if="!mobileMenuOpen"/><line x1="3" y1="18" x2="21" y2="18" v-if="!mobileMenuOpen"/>
              <line x1="18" y1="6" x2="6" y2="18" v-if="mobileMenuOpen"/><line x1="6" y1="6" x2="18" y2="18" v-if="mobileMenuOpen"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Menu Navigation -->
      <Transition name="slide-down">
        <nav v-if="mobileMenuOpen" class="mobile-nav mobile-only">
          <Link :href="route('home')" class="mobile-nav-link" @click="mobileMenuOpen = false">🏠 Inicio</Link>
          <Link href="/rifas" class="mobile-nav-link" @click="mobileMenuOpen = false">🎟️ Rifas</Link>
          <Link v-if="auth?.user?.role === 'miembro_gx' || auth?.user?.role === 'admin'" href="/liga" class="mobile-nav-link" @click="mobileMenuOpen = false">🏆 Liga</Link>
          <Link v-if="auth?.user?.role === 'miembro_gx' || auth?.user?.role === 'admin'" href="/ranking" class="mobile-nav-link" @click="mobileMenuOpen = false">🏅 Ranking</Link>
          <Link href="/torneos" class="mobile-nav-link" @click="mobileMenuOpen = false">⚔️ Torneos</Link>
          <button class="mobile-nav-link theme-switch-item" @click="toggleTheme">
            <span>{{ isDark ? '☀️ Modo Claro' : '🌙 Modo Oscuro' }}</span>
          </button>
          
          <template v-if="auth?.user?.role && ['miembro', 'miembro_gx', 'arbitro_gx', 'admin'].includes(auth.user.role)">
            <div class="mobile-nav-divider"></div>
            <Link :href="route('profile.edit')" class="mobile-nav-link" @click="mobileMenuOpen = false">👤 Mi Perfil</Link>
            <Link href="/finanzas" class="mobile-nav-link" @click="mobileMenuOpen = false">💰 Finanzas</Link>
            <Link v-if="auth.user.role !== 'miembro'" :href="route('referee.dashboard')" class="mobile-nav-link" @click="mobileMenuOpen = false">⚖️ Arbitrar</Link>
          </template>

          <template v-if="auth?.user">
            <Link :href="route('logout')" method="post" as="button" class="mobile-nav-link text-left" style="width: 100%; color: var(--gx-red);" @click="mobileMenuOpen = false">🚪 Salir</Link>
          </template>

          <template v-if="auth?.user?.role === 'admin'">
            <Link href="/admin" class="mobile-nav-link" @click="mobileMenuOpen = false">⚙️ Panel Admin</Link>
          </template>
        </nav>
      </Transition>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-wrapper">
        <slot />
      </div>
    </main>

    <!-- Bottom Nav (Mobile Only) -->
    <nav class="bottom-nav mobile-only">
      <BottomNavItem icon="🏠" label="Inicio" :href="route('home')" :active="$page.component === 'Home'" />
      <BottomNavItem v-if="auth?.user?.role === 'miembro_gx' || auth?.user?.role === 'admin'" icon="🏆" label="Liga" href="/liga" :active="$page.url.startsWith('/liga')" />
      <BottomNavItem v-if="auth?.user?.role === 'miembro_gx' || auth?.user?.role === 'admin'" icon="🏅" label="Ranking" href="/ranking" :active="$page.url.startsWith('/ranking')" />
      <BottomNavItem icon="⚔️" label="Torneos" href="/torneos" :active="$page.url.startsWith('/torneos')" />
      <BottomNavItem v-if="auth?.user && ['admin', 'arbitro_gx'].includes(auth.user.role)" icon="⚖️" label="Arbitrar" :href="route('referee.dashboard')" :active="$page.url.startsWith('/arbitrio')" />
      <BottomNavItem icon="🎟️" label="Rifas" href="/rifas" :active="$page.url.startsWith('/rifas')" />
      <BottomNavItem icon="☰" label="Menú" @click="mobileMenuOpen = !mobileMenuOpen" :active="mobileMenuOpen" is-button />
    </nav>

    <ToastContainer />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import BottomNavItem from '@/Components/BottomNavItem.vue';
import ToastContainer from '@/Components/ToastContainer.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const { success, error } = useToast();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash || {});

// Watch for inertia flash messages and show toasts
watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.success) success(newFlash.success);
    if (newFlash?.error) error(newFlash.error);
}, { deep: true, immediate: true });

const mobileMenuOpen = ref(false);
const profileOpen = ref(false);
const isDark = ref(true);

function toggleTheme() {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.remove('light-mode');
  } else {
    document.documentElement.classList.add('light-mode');
  }
  localStorage.setItem('gx-theme', isDark.value ? 'dark' : 'light');
}

// Close dropdown when clicking outside
function handleClickOutside(event) {
  if (profileOpen.value && !event.target.closest('.user-menu-wrapper')) {
    profileOpen.value = false;
  }
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside);
  const savedTheme = localStorage.getItem('gx-theme');
  if (savedTheme === 'light') {
    isDark.value = false;
    document.documentElement.classList.add('light-mode');
  }
});

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: var(--header-height);
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--glass-border);
  z-index: var(--z-sidebar);
}

.navbar-container {
  height: 100%;
  width: 100%;
  max-width: 100%;
  margin: 0;
  padding: 0 var(--space-xl);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.navbar-logo {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  text-decoration: none;
  flex-shrink: 0;
  z-index: 10;
}

.logo-img {
  height: 42px;
  width: auto;
  filter: drop-shadow(0 0 10px rgba(225, 6, 0, 0.4));
}

.logo-text {
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--text-primary);
}

.navbar-nav {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
}

.centered-nav {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.nav-link {
  color: var(--text-secondary);
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 10px 4px;
  transition: all var(--transition-fast);
  position: relative;
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-icon {
  font-size: 1.1rem;
}

.nav-link:hover, .nav-link.active {
  color: var(--gx-red-light);
}

.nav-link.active::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--gx-red);
  box-shadow: 0 0 10px var(--gx-red);
}

.nav-divider {
  width: 1px;
  height: 24px;
  background: var(--border-color);
  margin: 0 var(--space-xs);
}
/* Auth Actions */
.header-auth {
  display: flex;
  align-items: center;
  gap: var(--space-md);
}

.user-menu-wrapper {
  position: relative;
}

.user-menu-trigger {
  background: none;
  border: none;
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  color: var(--text-primary);
  cursor: pointer;
  padding: 4px 12px;
  border-radius: var(--radius-full);
  transition: background var(--transition-fast);
  font-weight: 500;
}

.user-menu-trigger:hover {
  background: rgba(255, 255, 255, 0.05);
}

.role-badge {
  background: var(--primary-color, rgba(225, 6, 0, 0.1));
  color: var(--gx-red);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: bold;
  text-transform: uppercase;
}

.user-avatar-sm {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gx-red), var(--gx-red-dark));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 0.9rem;
}

.navbar-right {
  display: flex;
  align-items: center;
  gap: var(--space-lg);
  flex: 1;
  justify-content: flex-end;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 200px;
  padding: var(--space-sm);
  z-index: var(--z-modal);
}

.dropdown-header {
  padding: var(--space-sm);
  display: flex;
  flex-direction: column;
}

.dropdown-name {
  font-weight: 600;
  font-size: 0.95rem;
  color: var(--text-primary);
}

.dropdown-role {
  font-size: 0.75rem;
  color: var(--text-muted);
  text-transform: capitalize;
}

.dropdown-divider {
  height: 1px;
  background: var(--border-color);
  margin: var(--space-sm) 0;
}

.dropdown-item {
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  padding: 12px var(--space-sm);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-size: 0.9rem;
  cursor: pointer;
  transition: background var(--transition-fast);
}

.dropdown-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.dropdown-item.logout {
  color: var(--gx-red-light);
}

.menu-toggle {
  background: none;
  border: none;
  color: var(--text-primary);
  cursor: pointer;
  padding: 8px;
}

.theme-toggle-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--glass-border);
  color: var(--text-primary);
  width: 36px;
  height: 36px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all var(--transition-fast);
}

.theme-toggle-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--gx-red);
}

.theme-switch-item {
  background: none;
  border: none;
  width: 100%;
  text-align: left;
}

/* Mobile Nav */
.mobile-nav {
  position: absolute;
  top: var(--header-height);
  left: 0;
  right: 0;
  background: var(--bg-secondary);
  border-bottom: 2px solid var(--gx-red);
  padding: var(--space-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  box-shadow: var(--shadow-lg);
}

.mobile-nav-link {
  padding: 14px;
  font-family: var(--font-display);
  color: var(--text-primary);
  font-size: 1.1rem;
  text-transform: uppercase;
  font-weight: 600;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  gap: 10px;
}

.mobile-nav-link:active {
  background: rgba(225, 6, 0, 0.1);
  color: var(--gx-red-light);
}

.mobile-nav-divider {
  height: 1px;
  background: var(--border-color);
  margin: var(--space-sm) 0;
}

/* Main Content */
.main-content {
  min-height: 100vh;
  padding-top: var(--header-height);
  padding-bottom: var(--bottom-nav-height);
}

.content-wrapper {
  width: 100%;
  max-width: 100%;
  margin: 0;
}

@media (min-width: 768px) {
  .main-content {
    padding-bottom: 0 !important;
  }
  .desktop-only { display: flex !important; }
  .mobile-only { display: none !important; }
}

@media (max-width: 767px) {
  .desktop-only { display: none !important; }
  .mobile-only { display: flex !important; }
  
  .navbar-container {
    justify-content: space-between;
    padding: 0 var(--space-md);
  }

  .navbar-logo {
    position: static;
    transform: none;
    left: auto;
  }
}

/* Bottom Nav */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: var(--bottom-nav-height);
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-top: 1px solid var(--glass-border);
  display: flex;
  align-items: center;
  justify-content: space-around;
  z-index: var(--z-bottom-nav);
  padding-bottom: env(safe-area-inset-bottom, 0px);
}

/* Transitions */
.fade-enter-active, .fade-leave-active {
  transition: opacity var(--transition-base);
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-down-enter-active, .slide-down-leave-active {
  transition: transform var(--transition-base), opacity var(--transition-base);
}
.slide-down-enter-from, .slide-down-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}
</style>


