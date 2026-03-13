<template>
  <AppLayout>
    <Head :title="`Gestion Evento: ${event.name}`" />

    <div class="page-content admin-event-show">
      <div class="page-header mb-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-md">
        <div>
          <h1 class="page-title text-gradient">Panel de Evento</h1>
          <p class="text-secondary m-0">{{ event.name }} | {{ event.season?.name }}</p>
          <span class="badge mt-xs" :class="isTournament ? 'badge-amber' : 'badge-blue'">{{ isTournament ? 'TORNEO' : 'LIGA' }}</span>
        </div>
        <div class="flex items-center gap-md w-full md:w-auto mt-sm md:mt-0">
          <button @click="handleDeleteEvent" class="btn btn-error btn-sm whitespace-nowrap" type="button">🗑️ Eliminar</button>
          <Link :href="route('admin.league.index')" class="btn btn-secondary whitespace-nowrap">Volver</Link>
        </div>
      </div>

      <div v-if="isTournament" class="grid grid-cols-1 lg:grid-cols-3 gap-xl">
        <section class="card lg:col-span-2 p-0 overflow-hidden border-white/5">
          <div class="p-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-md border-b border-white/5 bg-white/[0.02]">
            <div>
              <h2 class="text-lg font-black text-white">Preinscritos al Torneo</h2>
              <p class="text-xs text-secondary italic">Gestión de pre-registros y validación de pagos.</p>
            </div>
            <button v-if="event.registrations && event.registrations.length" @click="exportRegistrations" class="btn btn-outline btn-sm whitespace-nowrap">⬇️ Exportar CSV</button>
          </div>

          <div class="p-lg pb-0">
            <div class="flex flex-col md:flex-row gap-md mb-lg">
              <div class="registration-filters flex flex-wrap gap-xs">
                <button @click="regFilterStatus = 'all'" class="filter-pill" :class="{ active: regFilterStatus === 'all' }">Todos</button>
                <button @click="regFilterStatus = 'confirmed'" class="filter-pill" :class="{ active: regFilterStatus === 'confirmed' }">Validado</button>
                <button @click="regFilterStatus = 'pending'" class="filter-pill" :class="{ active: regFilterStatus === 'pending' }">Pendiente</button>
                <button @click="regFilterStatus = 'rejected'" class="filter-pill" :class="{ active: regFilterStatus === 'rejected' }">Rechazado</button>
              </div>
              <div class="flex-1">
                <input type="text" v-model="regSearch" class="form-input form-input-sm w-full" placeholder="Buscar por blader..." />
              </div>
            </div>
          </div>

          <div v-if="filteredRegistrations.length" class="overflow-x-auto">
            <table class="gx-table">
              <thead>
                <tr>
                  <th>Blader</th>
                  <th>Contacto</th>
                  <th class="text-center">Tipo Pago</th>
                  <th class="text-center">REX</th>
                  <th class="text-center">Estado</th>
                  <th class="text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="reg in filteredRegistrations" :key="reg.id" class="table-row">
                  <td>
                    <div class="flex items-center gap-sm">
                      <div class="mini-avatar-text">{{ reg.blader_name.charAt(0) }}</div>
                      <div>
                        <div class="font-black text-white leading-tight uppercase">{{ reg.blader_name }}</div>
                        <div class="text-[10px] text-secondary">{{ reg.first_name }} {{ reg.last_name }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="text-[10px] leading-tight text-secondary">
                      <div>{{ reg.email }}</div>
                      <div class="font-bold text-white/60">{{ reg.whatsapp }}</div>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-xs" :class="reg.payment_option === 'now' ? 'badge-blue' : 'badge-outline'">
                      {{ reg.payment_option === 'now' ? 'AHORA' : 'DESPUÉS' }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-xs" :class="reg.is_rex_registered ? 'badge-blue' : 'badge-amber'">
                      {{ reg.is_rex_registered ? 'SÍ' : 'NO' }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge badge-xs" :class="regStatusBadge(reg.status)">
                      {{ reg.status === 'confirmed' ? 'VALIDADO' : (reg.status === 'pending' ? 'PENDIENTE' : 'RECHAZADO') }}
                    </span>
                  </td>
                  <td class="text-right">
                    <div class="flex gap-xs justify-end">
                      <button @click="openReviewModal(reg)" class="btn btn-primary btn-xs px-2" :title="reg.payment_option === 'now' ? 'Validar Pago' : 'Confirmar Inscripción'">
                        {{ reg.payment_option === 'now' ? 'VALIDAR' : 'AUTO-OK' }}
                      </button>
                      <button @click="openEditRegModal(reg)" class="btn btn-outline btn-xs px-2" title="Editar">✎</button>
                      <button @click="handleDeleteRegistration(reg.id)" class="btn btn-error btn-xs px-2" title="Eliminar">🗑️</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="m-lg text-center text-secondary py-xl italic bg-black/10 rounded-xl border border-dashed border-white/5">
            No se encontraron preinscripciones con estos filtros.
          </div>
        </section>

        <section class="card space-y-lg border-white/5">
          <div>
            <h2 class="text-xs font-black uppercase tracking-widest text-secondary mb-md">Resumen de Torneo</h2>
            <div class="p-md bg-black/40 rounded-xl border border-white/5 space-y-md">
              <div class="flex justify-between items-center text-sm">
                <span class="opacity-70 uppercase tracking-tighter">Preinscritos</span>
                <span class="font-black text-gx-red">{{ event.registrations?.length || 0 }}</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="opacity-70 uppercase tracking-tighter">Validados</span>
                <span class="font-black text-accent-green">{{ event.registrations?.filter(r => r.status === 'confirmed').length || 0 }}</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="opacity-70 uppercase tracking-tighter">Pendientes</span>
                <span class="font-black text-amber-500">{{ event.registrations?.filter(r => r.status === 'pending').length || 0 }}</span>
              </div>
            </div>
          </div>

          <div class="p-md bg-gx-red/5 border border-gx-red/20 rounded-xl">
            <p class="text-[10px] uppercase font-black text-gx-red leading-tight mb-xs">Aviso importante</p>
            <p class="text-[10px] text-secondary leading-normal">
              Este panel solo gestiona el control de pagos y la base de datos de contacto. 
              El bracket y los resultados en tiempo real deben manejarse en el panel de Administrador de **R.E.X**.
            </p>
          </div>
        </section>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-4 gap-xl">
        <div class="lg:col-span-1 space-y-xl">
          <section class="card p-lg border-white/5 overflow-hidden">
            <div class="flex justify-between items-center mb-md">
              <div>
                <h2 class="text-sm font-black text-white">Asistencia y Pagos</h2>
                <p class="text-[10px] text-secondary uppercase tracking-widest">Control presencial del día del evento</p>
              </div>
              <button type="button" class="btn btn-primary btn-sm" @click="saveAttendance" :disabled="attendanceForm.processing">
                {{ attendanceForm.processing ? 'Guardando...' : '💾 Guardar Cambios' }}
              </button>
            </div>

            <div class="overflow-x-auto mx-[-var(--space-lg)]">
              <table class="gx-table attendance-table">
                <thead>
                  <tr>
                    <th>Blader</th>
                    <th class="text-center">Asistencia</th>
                    <th class="text-center">Pago</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="entry in attendanceForm.entries" :key="entry.player_id" class="table-row" :class="{ 'inactive-row': !entry.present }">
                    <td>
                      <div class="flex items-center gap-sm">
                        <div class="mini-avatar-text">{{ entry.blader_name.charAt(0) }}</div>
                        <div>
                          <span class="font-bold text-sm block leading-tight">{{ entry.blader_name }}</span>
                          <span class="text-[9px] text-secondary uppercase">ID #{{ entry.player_id }}</span>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <button type="button" class="attendance-btn" :class="{ 'active': entry.present }" @click="togglePresent(entry)">
                        {{ entry.present ? 'PRESENTE' : 'AUSENTE' }}
                      </button>
                    </td>
                    <td class="text-center">
                      <button type="button" class="payment-btn" :class="{ 'active': entry.paid, 'disabled': !entry.present }" :disabled="!entry.present" @click="entry.paid = !entry.paid">
                        {{ entry.paid ? 'PAGADO' : 'PENDIENTE' }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div class="lg:col-span-2 space-y-xl">
          <section class="card p-md md:p-lg border-white/5">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-md gap-md">
              <div>
                <h2 class="text-lg font-black flex items-center gap-xs">⚔️ Matches del Evento <span class="badge badge-blue ml-xs">{{ cAll }}</span></h2>
                <p class="text-[10px] text-secondary uppercase font-bold tracking-widest mt-xs">{{ presentCount }} jugadores listos</p>
              </div>
              <div class="flex gap-sm w-full md:w-auto flex-wrap">
                <button @click="toggleLock" class="btn btn-sm whitespace-nowrap" :class="event.matches_locked ? 'btn-outline !border-gx-red/50 !text-gx-red' : 'btn-outline !border-accent-green/50 !text-accent-green'" type="button">
                  {{ event.matches_locked ? '🔒 Desblo­quear' : '🔓 Bloquear' }} Partidas
                </button>
                <button @click="generateMatches" class="btn btn-outline btn-sm flex-1" :disabled="presentCount < 2 || generating || event.matches_locked" type="button">🎲 Generar</button>
                <button @click="openRefModal" class="btn btn-secondary btn-sm flex-1" :disabled="!event.matches || event.matches.length === 0" type="button">🎯 Auto-Árbitros</button>
              </div>
            </div>

            <!-- Late Player -->
            <div v-if="event.matches_locked && event.matches?.length > 0" class="flex gap-sm mb-md pb-md border-b border-white/10 items-end flex-wrap">
              <div class="form-group flex-1 m-0">
                <label class="form-label text-xs">Añadir jugador tardío</label>
                <select v-model="latePlayerId" class="form-input form-input-sm">
                  <option value="">Seleccionar jugador...</option>
                  <option v-for="p in availableLatePlayers" :key="p.id" :value="p.id">{{ p.blader_name || p.user?.name }}</option>
                </select>
              </div>
              <button @click="addLatePlayer" class="btn btn-primary btn-sm whitespace-nowrap" :disabled="!latePlayerId" type="button">+ Añadir tardío</button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-sm mb-md pb-md border-b border-white/10">
              <button @click="statusFilter = 'all'" :class="['filter-pill', statusFilter === 'all' ? 'active' : '']">Todos <span class="badge-sm">{{ cAll }}</span></button>
              <button @click="statusFilter = 'pending'" :class="['filter-pill', statusFilter === 'pending' ? 'active' : '']">⏳ Pendientes <span class="badge-sm">{{ cPend }}</span></button>
              <button @click="statusFilter = 'finished'" :class="['filter-pill', statusFilter === 'finished' ? 'active' : '']">✓ Finalizados <span class="badge-sm">{{ cDone }}</span></button>
              <button @click="statusFilter = 'no_ref'" :class="['filter-pill', statusFilter === 'no_ref' ? 'active' : '']">🚫 Sin Árbitro <span class="badge-sm">{{ cNoRef }}</span></button>
            </div>
            
            <div class="mb-md">
              <input type="text" v-model="matchSearch" class="form-input form-input-sm w-full md:w-64" placeholder="Filtrar por blader..." />
            </div>

            <div v-if="filteredMatches && filteredMatches.length > 0" class="match-grid">
              <div v-for="(m, index) in filteredMatches" :key="m.id" :class="['mc', m.concluded ? 'mc-done' : '']">
                <div class="mc-top">
                  <span class="mc-round text-xs font-bold text-secondary">R{{ m.round_no || 1 }} · #{{ index + 1 }}</span>
                  <span v-if="m.concluded" class="pill pill-done px-xs py-[2px] rounded text-[10px] font-black tracking-wider uppercase bg-gx-success/20 text-gx-success border border-gx-success/30">✓ Finalizado</span>
                  <span v-else-if="!m.referee_user_id" class="pill pill-noref px-xs py-[2px] rounded text-[10px] font-black tracking-wider uppercase bg-gx-red/20 text-gx-red border border-gx-red/30">Sin Árbitro</span>
                  <span v-else class="pill pill-pending px-xs py-[2px] rounded text-[10px] font-black tracking-wider uppercase bg-gx-amber/20 text-gx-amber border border-gx-amber/30">⏳ Pendiente</span>
                </div>
                
                <div class="mc-players mt-sm flex justify-between items-center bg-black/30 rounded-lg p-sm border border-white/5">
                  <div class="mc-player flex flex-col items-center flex-1 text-center" :class="[(m.concluded && m.winner_id === m.player_a_id) ? 'mc-winner' : (m.concluded ? 'mc-loser' : '')]">
                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 mb-xs" :class="(m.concluded && m.winner_id === m.player_a_id) ? 'border-gx-success' : 'border-white/10'">
                      <img v-if="playerAvatar(m.player_a_id)" :src="playerAvatar(m.player_a_id)" class="w-full h-full object-cover" @error="handleImageError">
                      <div v-else class="w-full h-full bg-white/10 flex items-center justify-center font-bold">{{ (m.player_a?.blader_name || 'A').charAt(0) }}</div>
                    </div>
                    <span class="text-xs font-bold leading-tight" :class="{'text-gx-success': m.concluded && m.winner_id === m.player_a_id}">{{ m.player_a?.blader_name || '-' }}</span>
                  </div>
                  
                  <div class="mc-vs flex flex-col items-center px-sm gap-xs shrink-0">
                    <span class="text-lg font-display text-white" :class="{'text-gx-success': m.concluded && m.winner_id === m.player_a_id}">{{ m.score_a }}</span>
                    <span class="text-[10px] font-black text-secondary tracking-widest uppercase bg-white/5 px-2 py-1 rounded-full border border-white/10">VS</span>
                    <span class="text-lg font-display text-white" :class="{'text-gx-success': m.concluded && m.winner_id === m.player_b_id}">{{ m.score_b }}</span>
                  </div>
                  
                  <div class="mc-player flex flex-col items-center flex-1 text-center" :class="[(m.concluded && m.winner_id === m.player_b_id) ? 'mc-winner' : (m.concluded ? 'mc-loser' : '')]">
                    <div class="w-12 h-12 rounded-full overflow-hidden border-2 mb-xs" :class="(m.concluded && m.winner_id === m.player_b_id) ? 'border-gx-success' : 'border-white/10'">
                      <img v-if="playerAvatar(m.player_b_id)" :src="playerAvatar(m.player_b_id)" class="w-full h-full object-cover" @error="handleImageError">
                      <div v-else class="w-full h-full bg-white/10 flex items-center justify-center font-bold">{{ (m.player_b?.blader_name || 'B').charAt(0) }}</div>
                    </div>
                    <span class="text-xs font-bold leading-tight" :class="{'text-gx-success': m.concluded && m.winner_id === m.player_b_id}">{{ m.player_b?.blader_name || '-' }}</span>
                  </div>
                </div>

                <div class="mc-ref mt-sm flex items-center gap-sm bg-white/5 p-xs rounded border border-white/5 text-xs">
                  <span class="opacity-70">🧑‍⚖️</span>
                  <select v-if="!m.concluded" @change="handleAssignReferee(m.id, $event.target.value)" class="bg-transparent border-none text-xs p-0 focus:ring-0 outline-none w-full max-w-[150px]">
                    <option value="">Sin asignar</option>
                    <option v-for="ref in referees" :key="ref.id" :value="ref.id" :selected="m.referee_user_id === ref.id">{{ ref.blader_name || ref.name }}</option>
                  </select>
                  <span v-else class="text-secondary truncate grow">{{ m.referee?.blader_name || m.referee?.name || 'Arbitro' }}</span>
                  
                  <span v-if="m.winner_id" class="ml-auto text-gx-success font-bold flex items-center gap-xs"><span class="opacity-80">🏆</span> {{ m.winner_id === m.player_a_id ? (m.player_a?.blader_name) : (m.player_b?.blader_name) }}</span>
                </div>

                <div class="mc-actions mt-sm flex gap-xs">
                  <Link :href="route('referee.match.panel', m.id)" class="btn btn-primary btn-xs flex-1 text-[10px] tracking-wider font-bold">
                    {{ m.concluded ? '👁️ Ver' : '🎯 Arbitrar' }}
                  </Link>
                  <button v-if="m.concluded" @click="resetMatch(m.id)" class="btn btn-outline btn-xs !border-accent-blue/30 !text-accent-blue hover:!bg-accent-blue/20" title="Reiniciar Partida" type="button">🔄</button>
                  <button @click="deleteMatch(m.id)" class="btn btn-outline btn-xs !border-gx-red/30 !text-gx-red hover:!bg-gx-red/20" title="Eliminar Cruze" type="button">🗑️</button>
                </div>
              </div>
            </div>
            <div v-else-if="matchSearch" class="text-center py-xl text-secondary">No se encontraron partidas.</div>
            <div v-else class="text-center py-2xl text-secondary bg-black/20 rounded-xl border border-dashed border-white/20 mt-md">Guarda asistencia y genera partidas para empezar.</div>
          </section>
        </div>

        <div class="lg:col-span-1 space-y-xl">
          <section class="card p-lg">
            <h2 class="text-sm font-black mb-md">Estadísticas</h2>
            <div class="grid grid-cols-2 gap-md">
              <div class="p-md bg-black/20 rounded-xl border border-white/10 text-center">
                <div class="text-2xl font-black text-accent-blue">{{ event.matches?.length || 0 }}</div>
                <div class="text-[9px] uppercase font-bold text-secondary mt-1">Total Partidas</div>
              </div>
              <div class="p-md bg-black/20 rounded-xl border border-white/10 text-center">
                <div class="text-2xl font-black text-accent-green">{{ event.matches?.filter(m => m.concluded).length || 0 }}</div>
                <div class="text-[9px] uppercase font-bold text-secondary mt-1">Batallas Jugadas</div>
              </div>
              <div class="p-md bg-black/20 rounded-xl border border-white/10 text-center">
                <div class="text-2xl font-black text-white">{{ presentCount }}</div>
                <div class="text-[9px] uppercase font-bold text-secondary mt-1">Asistentes</div>
              </div>
              <div class="p-md bg-black/20 rounded-xl border border-white/10 text-center">
                <div class="text-2xl font-black text-gold">{{ paidCount }}</div>
                <div class="text-[9px] uppercase font-bold text-secondary mt-1">Pagos OK</div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
    <!-- Modal: Asignar Árbitros en Masa -->
    <div v-if="showRefModal" class="fixed inset-0 z-50 flex items-center justify-center p-md bg-black/80 backdrop-blur-sm">
      <div class="bg-surface border border-white/10 rounded-2xl p-lg w-full max-w-md shadow-2xl relative animate-in fade-in zoom-in duration-200">
        <button @click="showRefModal = false" class="absolute top-md right-md text-secondary hover:text-white transition-colors">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <h3 class="text-xl font-display font-black mb-sm text-white">Asignar Árbitros</h3>
        <p class="text-sm text-secondary mb-md">Selecciona quienes van a arbitrar en este evento. Se asignarán equitativamente evitando que se arbitren a sí mismos.</p>

        <div class="space-y-sm max-h-60 overflow-y-auto pr-2 mb-md custom-scrollbar">
          <label v-for="ref in referees" :key="ref.id" class="flex items-center gap-sm p-sm rounded-lg bg-white/5 border border-white/5 cursor-pointer hover:bg-white/10 transition-colors">
            <input type="checkbox" v-model="selectedRefs" :value="ref.id" class="form-checkbox rounded text-gx-blue bg-black/50 border-white/20 focus:ring-gx-blue focus:ring-offset-surface">
            <div class="flex-1">
              <span class="block font-bold text-sm">{{ ref.blader_name || ref.name }}</span>
            </div>
          </label>
        </div>

        <div class="flex gap-sm">
          <button @click="showRefModal = false" class="btn btn-outline flex-1">Cancelar</button>
          <button @click="submitAutoAssign" class="btn btn-primary flex-1" :disabled="selectedRefs.length === 0">Asignar a todos</button>
        </div>
      </div>
    </div>

    <!-- Modal: Revisar Registro de Torneo -->
    <div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-md bg-black/80 backdrop-blur-sm">
      <div class="bg-surface border border-white/10 rounded-2xl p-lg w-full max-w-2xl shadow-2xl relative animate-in fade-in zoom-in duration-200">
        <button @click="closeReviewModal" class="absolute top-md right-md text-secondary hover:text-white transition-colors">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        <h3 class="text-xl font-display font-black mb-md text-white">Revisar Registro: {{ selectedReg.blader_name }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-xl max-h-[70vh] overflow-y-auto pr-2 custom-scrollbar">
          <div class="space-y-lg">
            <div class="info-group">
              <span class="text-[10px] text-secondary uppercase font-bold tracking-widest block mb-1">Datos del Blader</span>
              <p class="text-lg font-black m-0">{{ selectedReg.first_name }} {{ selectedReg.last_name }}</p>
              <p class="text-secondary m-0">{{ selectedReg.email }}</p>
              <p class="text-secondary m-0">{{ selectedReg.whatsapp }}</p>
            </div>

            <div class="info-group">
              <span class="text-[10px] text-secondary uppercase font-bold tracking-widest block mb-1">Registro REX</span>
              <p class="m-0 font-bold" :class="selectedReg.is_rex_registered ? 'text-gx-blue' : 'text-amber-500'">
                {{ selectedReg.is_rex_registered ? 'YA REGISTRADO EN REX' : 'NECESITA CUENTA AUTOMÁTICA' }}
              </p>
            </div>

            <div class="info-group">
              <span class="text-[10px] text-secondary uppercase font-bold tracking-widest block mb-1">Estado de Pago</span>
              <div class="flex items-center gap-sm mt-1">
                <span class="badge" :class="regStatusBadge(selectedReg.status)">{{ selectedReg.status }}</span>
                <span class="text-xs text-secondary" v-if="selectedReg.payment_date">Recibido: {{ new Date(selectedReg.payment_date).toLocaleString('es-CL') }}</span>
              </div>
            </div>

            <div v-if="selectedReg.status === 'pending'" class="mt-xl space-y-md border-t border-white/5 pt-lg">
              <button @click="approveReg(selectedReg.id)" class="btn btn-primary w-full" :disabled="processingValidation">
                ✅ Validar y Confirmar
              </button>
              
              <div class="space-y-sm">
                <textarea v-model="rejectReason" class="form-input text-sm" placeholder="Motivo de rechazo (se enviará por correo)..." rows="2"></textarea>
                <button @click="rejectReg(selectedReg.id)" class="btn btn-outline btn-error w-full text-xs" :disabled="processingValidation || !rejectReason.trim()">
                  ❌ Rechazar Registro
                </button>
              </div>
            </div>
            <div v-else-if="selectedReg.validation_notes" class="mt-md p-md bg-red-500/5 rounded-lg border border-red-500/20 text-xs text-secondary">
              <strong class="text-red-400 block mb-1 uppercase">Notas de validación:</strong>
              {{ selectedReg.validation_notes }}
            </div>
          </div>

          <div class="space-y-sm">
            <span class="text-[10px] text-secondary uppercase font-bold tracking-widest block">Comprobante de Pago</span>
            <div class="proof-preview rounded-xl border border-white/10 bg-black/40 overflow-hidden min-h-[300px] flex items-center justify-center cursor-zoom-in" @click="openProofFull(proofUrl(selectedReg.proof_path))">
              <img v-if="selectedReg.proof_path" :src="proofUrl(selectedReg.proof_path)" class="max-w-full hover:scale-105 transition-transform duration-300" @error="handleImageError" />
              <div v-else class="text-secondary text-center p-md">
                <p class="text-4xl mb-4">🚫</p>
                <p class="text-xs uppercase font-bold tracking-widest">Sin comprobante</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showFullProof" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/95 p-xl" @click="showFullProof = false">
      <img :src="fullProofUrl" class="max-w-full max-h-full object-contain shadow-2xl animate-in zoom-in duration-300" />
      <button class="absolute top-md right-md text-white/50 hover:text-white text-4xl">&times;</button>
    </div>

    <!-- Modal: Editar Registro -->
    <div v-if="showEditRegModal" class="fixed inset-0 z-50 flex items-center justify-center p-md bg-black/80 backdrop-blur-sm">
      <div class="bg-surface border border-white/10 rounded-2xl p-lg w-full max-w-md shadow-2xl relative">
        <h3 class="text-xl font-display font-black mb-md text-white">Editar Preinscripción</h3>
        <form @submit.prevent="submitEditReg" class="space-y-md">
          <div class="form-group font-bold">
            <label class="form-label">Blader</label>
            <input type="text" v-model="editRegForm.blader_name" class="form-input" required />
          </div>
          <div class="grid grid-cols-2 gap-md">
            <div class="form-group">
              <label class="form-label">Nombre</label>
              <input type="text" v-model="editRegForm.first_name" class="form-input" required />
            </div>
            <div class="form-group">
              <label class="form-label">Apellido</label>
              <input type="text" v-model="editRegForm.last_name" class="form-input" required />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">WhatsApp</label>
            <input type="text" v-model="editRegForm.whatsapp" class="form-input" />
          </div>
          <div class="form-group">
            <label class="form-label">Estado</label>
            <select v-model="editRegForm.status" class="form-input">
              <option value="pending">Pendiente</option>
              <option value="confirmed">Confirmado</option>
              <option value="rejected">Rechazado</option>
            </select>
          </div>
          <div class="flex gap-sm mt-lg">
            <button type="button" @click="showEditRegModal = false" class="btn btn-ghost flex-1">Cancelar</button>
            <button type="submit" class="btn btn-primary flex-1" :disabled="editRegForm.processing">Guardar Cambios</button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  event: Object,
  players: Array,
  attendances: Object,
  referees: Array,
});

const { success: toastSuccess, warning: toastWarning } = useToast();
const { ask } = useConfirm();
const generating = ref(false);
const matchSearch = ref('');
const regSearch = ref('');
const regFilterStatus = ref('all');

const eventTypeValue = computed(() => props.event?.event_type?.value || props.event?.event_type || 'liga');
const isTournament = computed(() => eventTypeValue.value === 'torneo');

const attendanceForm = useForm({
  entries: props.players.map((player) => ({
    player_id: player.id,
    blader_name: player.blader_name,
    present: !!props.attendances?.[player.id]?.present,
    paid: !!props.attendances?.[player.id]?.paid,
  })),
});

const presentCount = computed(() => attendanceForm.entries.filter((entry) => entry.present).length);
const paidCount = computed(() => attendanceForm.entries.filter((entry) => entry.paid).length);

const statusFilter = ref('all');

const cAll = computed(() => props.event.matches?.length || 0);
const cPend = computed(() => props.event.matches?.filter(m => !m.concluded).length || 0);
const cDone = computed(() => props.event.matches?.filter(m => m.concluded).length || 0);
const cNoRef = computed(() => props.event.matches?.filter(m => !m.concluded && !m.referee_user_id).length || 0);

const filteredMatches = computed(() => {
  if (!props.event.matches) return [];
  
  let matches = props.event.matches;
  
  if (statusFilter.value === 'pending') matches = matches.filter(m => !m.concluded);
  else if (statusFilter.value === 'finished') matches = matches.filter(m => m.concluded);
  else if (statusFilter.value === 'no_ref') matches = matches.filter(m => !m.concluded && !m.referee_user_id);
  
  if (!matchSearch.value) return matches;
  
  const s = matchSearch.value.toLowerCase();
  return matches.filter(m => 
    m.player_a?.blader_name?.toLowerCase().includes(s) || 
    m.player_b?.blader_name?.toLowerCase().includes(s)
  );
});

const filteredRegistrations = computed(() => {
  if (!props.event.registrations) return [];
  
  let res = props.event.registrations;
  
  if (regFilterStatus.value !== 'all') {
    res = res.filter(r => r.status === regFilterStatus.value);
  }
  
  if (regSearch.value) {
    const s = regSearch.value.toLowerCase();
    res = res.filter(r => 
      r.blader_name?.toLowerCase().includes(s) || 
      r.first_name?.toLowerCase().includes(s) || 
      r.last_name?.toLowerCase().includes(s)
    );
  }
  
  return res;
});

const exportRegistrations = () => {
  if (!props.event.registrations) return;
  const headers = ['Blader', 'Nombre', 'Email', 'WhatsApp', 'Plataforma', 'Registrado en REX'];
  const rows = props.event.registrations.map(r => [
    `"${r.blader_name}"`, 
    `"${r.first_name} ${r.last_name}"`, 
    `"${r.email}"`, 
    `"${r.whatsapp || ''}"`, 
    r.is_rex_registered ? 'REX' : 'GX',
    r.is_rex_registered ? 'Sí' : 'No'
  ]);
  const csvContent = "\uFEFF" + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `Preinscritos_${props.event.name}.csv`;
  link.click();
};

const togglePresent = (entry) => {
  entry.present = !entry.present;
  if (!entry.present) entry.paid = false;
};

const saveAttendance = () => {
  attendanceForm.post(route('admin.events.attendance.update', props.event.id), {
    preserveScroll: true,
    onSuccess: () => toastSuccess('Asistencia actualizada.'),
  });
};

const generateMatches = async () => {
  const confirmed = await ask({
    title: 'Generar cruces',
    message: 'Se regenerarán partidas no jugadas. ¿Continuar?',
    confirmText: 'Generar',
    tone: 'primary',
  });

  if (!confirmed) return;

  generating.value = true;
  router.post(route('admin.events.matches.generate', props.event.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      generating.value = false;
      toastSuccess('Cruces generados.');
    },
  });
};

const showRefModal = ref(false);
const selectedRefs = ref([]);

const openRefModal = () => {
  selectedRefs.value = props.referees.map(r => r.id);
  showRefModal.value = true;
};

const submitAutoAssign = () => {
  if (selectedRefs.value.length === 0) {
    toastWarning('Debes seleccionar al menos un árbitro.');
    return;
  }

  ask({
    title: 'Auto-asignar árbitros',
    message: `¿Asignar a los ${selectedRefs.value.length} árbitros seleccionados en cruces pendientes?`,
    confirmText: 'Asignar',
    tone: 'primary',
  }).then((confirmed) => {
    if (!confirmed) return;

    router.post(route('admin.events.matches.auto-assign', props.event.id), {
      selected_referees: selectedRefs.value,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        showRefModal.value = false;
        toastSuccess('Árbitros asignados correctamente.');
      },
    });
  });
};

const autoAssignReferees = () => {
  router.post(route('admin.events.matches.auto-assign', props.event.id), {}, {
    preserveScroll: true,
    onSuccess: () => toastSuccess('Árbitros asignados automáticamente.'),
  });
};

const toggleLock = () => {
  router.post(route('admin.events.toggle-lock', props.event.id), {}, {
    preserveScroll: true,
    onSuccess: () => toastSuccess(props.event.matches_locked ? 'Partidas desbloqueadas.' : 'Partidas bloqueadas.'),
  });
};

const latePlayerId = ref('');

const availableLatePlayers = computed(() => {
  const attendeeIds = new Set(attendanceForm.entries.filter(e => e.present).map(e => e.player_id));
  return props.players.filter(p => !attendeeIds.has(p.id));
});

const addLatePlayer = async () => {
  if (!latePlayerId.value) return;
  const confirmed = await ask({
    title: 'Añadir jugador tardío',
    message: 'Se generarán partidas solo entre este jugador y los jugadores ya presentes. Las partidas existentes no se tocarán.',
    confirmText: 'Añadir',
    tone: 'primary',
  });
  if (!confirmed) return;

  router.post(route('admin.events.add-late-player', props.event.id), {
    player_id: latePlayerId.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toastSuccess('Jugador tardío añadido con sus partidas.');
      latePlayerId.value = '';
    },
  });
};

const handleAssignReferee = (matchId, refereeId) => {
  router.post(route('admin.events.matches.referee', { match: matchId }), {
    referee_id: refereeId || null,
  }, {
    preserveScroll: true,
    onSuccess: () => toastSuccess('Árbitro asignado.'),
  });
};

const handleDeleteEvent = async () => {
  const confirmed = await ask({
    title: 'Eliminar evento',
    message: 'Esta acción eliminará todo el evento. ¿Confirmar?',
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;

  router.delete(route('admin.events.destroy', props.event.id), {
    onSuccess: () => toastSuccess('Evento eliminado.'),
  });
};

const deleteMatch = async (matchId) => {
  const confirmed = await ask({
    title: 'Eliminar cruce',
    message: '¿Estás seguro de eliminar esta partida del evento?',
    confirmText: 'Eliminar',
  });

  if (!confirmed) return;

  router.delete(route('admin.events.matches.destroy', matchId), {
    preserveScroll: true,
    onSuccess: () => toastSuccess('Partida eliminada.'),
  });
};

const resetMatch = async (matchId) => {
  const confirmed = await ask({
    title: 'Reiniciar partida',
    message: 'Se borrarán todos los puntos y acciones registradas. ¿Continuar?',
    confirmText: 'Reiniciar',
    tone: 'danger',
  });

  if (!confirmed) return;

  router.post(route('referee.match.reset', matchId), {}, {
    preserveScroll: true,
    onSuccess: () => toastSuccess('Partida reiniciada.'),
  });
};

const playerAvatar = (playerId) => {
  const player = props.players.find(p => p.id === playerId);
  if (!player) return null;
  const path = player.avatar_path || player.user?.avatar_path;
  return path ? `/storage/${path}` : null;
};

const showReviewModal = ref(false);
const selectedReg = ref(null);
const processingValidation = ref(false);
const rejectReason = ref('');
const showFullProof = ref(false);
const fullProofUrl = ref('');

const openReviewModal = (reg) => {
  selectedReg.value = reg;
  showReviewModal.value = true;
};

const closeReviewModal = () => {
  showReviewModal.value = false;
  selectedReg.value = null;
  rejectReason.value = '';
};

const proofUrl = (path) => path ? `/storage/${path}` : null;

const regStatusBadge = (status) => {
  if (status === 'confirmed') return 'badge-green';
  if (status === 'rejected') return 'badge-red';
  return 'badge-amber';
};

const openProofFull = (url) => {
  if (!url) return;
  fullProofUrl.value = url;
  showFullProof.value = true;
};

const approveReg = (id) => {
  processingValidation.value = true;
  router.post(route('admin.tournament-registrations.approve', id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toastSuccess('Registro confirmado correctamente.');
      closeReviewModal();
    },
    onFinish: () => processingValidation.value = false,
  });
};

const rejectReg = (id) => {
  if (!rejectReason.value.trim()) {
    toastWarning('Indica un motivo para el rechazo.');
    return;
  }

  processingValidation.value = true;
  router.post(route('admin.tournament-registrations.reject', id), {
    reason: rejectReason.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      toastWarning('Registro rechazado.');
      closeReviewModal();
    },
    onFinish: () => processingValidation.value = false,
  });
};

const showEditRegModal = ref(false);
const editRegForm = useForm({
  id: null,
  blader_name: '',
  first_name: '',
  last_name: '',
  whatsapp: '',
  status: 'pending',
});

const openEditRegModal = (reg) => {
  editRegForm.id = reg.id;
  editRegForm.blader_name = reg.blader_name;
  editRegForm.first_name = reg.first_name;
  editRegForm.last_name = reg.last_name;
  editRegForm.whatsapp = reg.whatsapp;
  editRegForm.status = reg.status;
  showEditRegModal.value = true;
};

const submitEditReg = () => {
  editRegForm.put(route('admin.tournament-registrations.update', editRegForm.id), {
    onSuccess: () => {
      showEditRegModal.value = false;
      toastSuccess('Preinscripción actualizada.');
    }
  });
};

const handleDeleteRegistration = async (id) => {
  const confirmed = await ask({
    title: 'Eliminar preinscripción',
    message: '¿Estás seguro de eliminar este registro permanentemente?',
    confirmText: 'Eliminar',
  });
  if (!confirmed) return;

  router.delete(route('admin.tournament-registrations.destroy', id), {
    preserveScroll: true,
    onSuccess: () => toastSuccess('Registro eliminado.'),
  });
};

const handleImageError = (e) => {
  e.target.src = '/img/logo.png';
};
</script>

<style scoped>
.registration-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: var(--space-sm);
}

.registration-card {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-md);
  padding: var(--space-md);
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  transition: all 0.2s;
}

.registration-card:hover {
  border-color: rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.03);
}

.registration-card.confirmed {
  border-left: 3px solid #22c55e;
}

.registration-card.rejected {
  border-left: 3px solid #ef4444;
  opacity: 0.8;
}

.registration-card.pending {
  border-left: 3px solid #f59e0b;
}

.registration-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-xs);
}

.summary-box {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-sm);
}

.summary-box > div {
  padding: var(--space-sm);
  border-radius: var(--radius-sm);
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.2);
  display: grid;
  gap: 2px;
}

.summary-box .k {
  font-size: 0.68rem;
  text-transform: uppercase;
  color: var(--text-secondary);
}

/* New Attendance Table Styles */
.attendance-table th, .attendance-table td {
  padding: 12px 16px;
}

.inactive-row {
  opacity: 0.5;
  filter: grayscale(0.5);
  background: rgba(0, 0, 0, 0.1);
}

.attendance-btn, .payment-btn {
  width: 100%;
  max-width: 120px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  transition: all 0.2s;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.03);
  color: var(--text-secondary);
}

.attendance-btn.active {
  background: rgba(16, 185, 129, 0.15);
  border-color: #10b981;
  color: #10b981;
  box-shadow: 0 0 10px rgba(16, 185, 129, 0.2);
}

.payment-btn.active {
  background: rgba(59, 130, 246, 0.15);
  border-color: #3b82f6;
  color: #3b82f6;
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
}

.payment-btn.disabled {
  opacity: 0.3;
  cursor: not-allowed;
  pointer-events: none;
}

.mini-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  overflow: hidden;
  background: rgba(255,255,255,0.1);
  border: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 800;
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.fighter-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.fighter-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  overflow: hidden;
  background: rgba(0,0,0,0.3);
  border: 2px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  font-weight: 900;
}

.fighter-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.fighter-name {
  font-weight: 800;
  font-size: 0.9rem;
  line-height: 1.1;
}

.fighter-name.winner {
  color: var(--accent-green);
}

.vs-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  width: 60px;
}

.match-score {
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 900;
  color: var(--gx-red);
}

.filter-pill {
  padding: 6px 14px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
}
.filter-pill:hover, .filter-pill.active {
  background: var(--gx-blue);
  color: white;
  border-color: var(--gx-blue);
}
.badge-sm {
  background: rgba(0,0,0,0.3);
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.7rem;
}

.match-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.mc {
    background: rgba(0,0,0,0.2);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: all 0.2s ease;
}

.mc:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.mc-done {
    background: rgba(74, 222, 128, 0.03); 
    border-color: rgba(74, 222, 128, 0.15);
}

.mc-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mc-loser {
    opacity: 0.5;
    filter: grayscale(1);
}

</style>



