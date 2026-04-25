<?php

namespace App\Console\Commands;

use App\Models\LeagueEvent;
use App\Models\LeagueMatch;
use App\Models\LeaguePlayer;
use App\Services\LeagueService;
use Illuminate\Console\Command;

class FixJKazamiMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:j-kazami';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agrega las partidas faltantes de J Kazami y recalcula puntos';

    /**
     * Execute the console command.
     */
    public function handle(LeagueService $leagueService)
    {
        // Encontrar a J Kazami
        $jKazami = LeaguePlayer::where('display_name', 'like', '%Kazami%')->first();
        if (!$jKazami) {
            $this->error('No se encontró al jugador J Kazami.');
            return;
        }

        // Encontrar a Byakko
        $byakko = LeaguePlayer::where('display_name', 'like', '%Byakko%')->first();
        if (!$byakko) {
            $this->error('No se encontró al jugador Byakko.');
            return;
        }

        // Encontrar a Mente Loka
        $menteLoka = LeaguePlayer::where('display_name', 'like', '%Mente%Loka%')->orWhere('display_name', 'like', '%MenteLoka%')->first();
        if (!$menteLoka) {
            $this->error('No se encontró al jugador Mente Loka.');
            return;
        }

        // Obtener la liga actual (temporada 3 por ejemplo o el último evento de liga)
        // Ya que fueron partidas pasadas, las pondremos en el último evento de liga para que sumen a la temporada actual.
        $evento = LeagueEvent::where('event_type', 'liga')->latest('event_date')->first();
        if (!$evento) {
            $this->error('No hay un evento de liga donde registrar las partidas.');
            return;
        }

        $this->info("Usando evento: {$evento->name} (Temporada {$evento->season_id})");

        // Partida 1: Byakko vs J (1 - 5)
        $match1 = LeagueMatch::create([
            'league_event_id' => $evento->id,
            'player_a_id' => $byakko->id,
            'player_b_id' => $jKazami->id,
            'score_a' => 1,
            'score_b' => 5,
            'winner_id' => $jKazami->id,
            'concluded' => true,
            'is_draw' => false,
        ]);
        $this->info("Creada partida: Byakko (1) vs J Kazami (5)");

        // Partida 2: Mente Loka vs J (5 - 0)
        $match2 = LeagueMatch::create([
            'league_event_id' => $evento->id,
            'player_a_id' => $menteLoka->id,
            'player_b_id' => $jKazami->id,
            'score_a' => 5,
            'score_b' => 0,
            'winner_id' => $menteLoka->id,
            'concluded' => true,
            'is_draw' => false,
        ]);
        $this->info("Creada partida: Mente Loka (5) vs J Kazami (0)");

        // Recalcular los puntos de la temporada actual
        $this->info("Recalculando tabla de posiciones para la temporada {$evento->season_id}...");
        $leagueService->recalculateStandings($evento->season_id);

        $this->info("¡Completado con éxito! Los puntos de J Kazami deberían estar corregidos.");
    }
}
