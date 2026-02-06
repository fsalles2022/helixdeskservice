<?php

namespace App\Http\Controllers;

use App\Models\FailedEvent;
use App\Messaging\RabbitPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FailedEventController extends Controller
{
    /**
     * Carrega a View principal do DLQ
     */
    public function page()
    {
        return view('dlq.index');
    }

    /**
     * Lista os eventos falhos (API)
     * Ajustado para 10 itens por página conforme pedido no layout
     */
    public function index(Request $request)
    {
        $query = FailedEvent::query();

        if ($request->filled('search')) {
            $query->where('routing_key', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('retries')) {
            if ($request->retries === '3') {
                $query->where('attempts', '>=', 3);
            } elseif ($request->retries === '5') {
                $query->where('attempts', '>=', 5);
            }
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate(10)
        );
    }



    /**
     * Status em tempo real para os Cards do Painel
     */
    public function stats()
    {
        $total = FailedEvent::count();

        return response()->json([
            'total'   => $total, // Para o Card DLQ
            'today'   => FailedEvent::whereDate('created_at', Carbon::today())->count(), // Para o Card Hoje
            'status'  => [
                'total_retries' => (int) FailedEvent::sum('attempts'), // Para o Card Retries
            ]
        ]);
    }


    /**
     * Lógica de Retry: Reprocessa e marca a tentativa
     */
    public function retry($id, RabbitPublisher $publisher)
    {
        return DB::transaction(function () use ($id, $publisher) {
            $event = FailedEvent::findOrFail($id);

            try {
                // Publica novamente no RabbitMQ
                $publisher->publish(
                    'tickets.events',
                    $event->routing_key,
                    json_decode($event->payload, true)
                );

                // Incrementa a contagem de retries para controle visual
                $event->increment('attempts');

                return response()->json([
                    'status' => 'requeued',
                    'attempts' => $event->attempts,
                    'message' => "Evento #{$id} enviado para a fila novamente."
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Falha ao conectar com o Broker: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Remove o registro da DLQ (Descarte manual)
     */
    public function destroy($id)
    {
        $event = FailedEvent::findOrFail($id);
        $event->delete();

        return response()->json([
            'status' => 'deleted',
            'message' => "Evento #{$id} removido da base."
        ]);
    }


    public function charts()
    {
        try {
            // 1. Falhas por Hora
            $byHour = FailedEvent::selectRaw('DATE_FORMAT(created_at, "%H") as hour, count(*) as total')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            // 2. Falhas por Routing Key
            $byRouting = FailedEvent::selectRaw('routing_key, count(*) as total')
                ->whereNotNull('routing_key')
                ->groupBy('routing_key')
                ->get();

            // 3. Severidade baseada em tentativas (Ajustado para evitar erro de agrupamento)
            // Usamos uma Subquery ou repetimos a lógica no Group By para compatibilidade total
            $bySeverity = FailedEvent::selectRaw('
                CASE
                    WHEN attempts >= 5 THEN "High"
                    WHEN attempts >= 3 THEN "Mid"
                    ELSE "Low"
                END as severity,
                count(*) as total')
                ->groupBy(DB::raw('CASE
                    WHEN attempts >= 5 THEN "High"
                    WHEN attempts >= 3 THEN "Mid"
                    ELSE "Low"
                END'))
                ->get();

            return response()->json([
                'byHour' => $byHour,
                'byRouting' => $byRouting,
                'bySeverity' => $bySeverity,
            ]);
        } catch (\Exception $e) {
            // Se der erro, retorna o motivo real para você ver no console do navegador
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
