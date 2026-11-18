<?php

namespace App\Http\Controllers\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\ActaConfidencial;
use App\Models\Citacion;
use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use App\Models\ReunionComunal;
use App\Models\User;
use App\Services\DocumentosPermitidos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BuscadorController extends Controller
{
    /** Filas por página en los resultados combinados (HU-07). */
    private const POR_PAGINA = 15;

    /** Tipos de documento del filtro (HU-07: tipo de documento). */
    private const TIPOS = [
        'memo' => 'Memo',
        'oficio' => 'Oficio',
        'permiso' => 'Permiso administrativo',
        'citacion' => 'Citación a apoderado',
        'acta' => 'Acta confidencial',
        'reunion' => 'Reunión comunal',
    ];

    /** Estados válidos del filtro (HU-07: estado). */
    private const ESTADOS = [
        'pendiente' => 'Pendiente de V°B°',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'revocada' => 'Revocada',
        'programada' => 'Programada',
        'asistio' => 'Asistió',
        'no_asistio' => 'No asistió / Justificada',
        'reprogramada' => 'Reprogramada',
    ];

    /** Módulos con columna de estado; los demás se excluyen al filtrar por estado. */
    private const MODELOS_CON_ESTADO = [Citacion::class, PermisoAdministrativo::class];

    /**
     * Global search with combinable filters (HU-07).
     *
     * Results only include modules the user may read (HU-07 / HU-05: the
     * search never reveals reserved documents to excluded profiles).
     */
    public function index(Request $request): View
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'tipo' => ['nullable', Rule::in(array_keys(self::TIPOS))],
            'emisor' => ['nullable', 'integer', 'exists:users,id'],
            'estado' => ['nullable', Rule::in(array_keys(self::ESTADOS))],
        ]);

        $user = $request->user();

        $filas = collect();
        foreach ($this->modelosConsultables(DocumentosPermitidos::documentosLegiblesPara($user), $datos) as $clase) {
            $filas = $filas->merge($this->buscarEn($clase, $datos, $user));
        }

        $filas = $filas->sortBy([['fecha', 'desc'], ['id', 'desc']])->values();

        $pagina = max(1, (int) $request->input('page', 1));
        $resultados = new LengthAwarePaginator(
            $filas->forPage($pagina, self::POR_PAGINA)->all(),
            $filas->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('buscador.index', [
            'resultados' => $resultados,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'tipos' => self::TIPOS,
            'estados' => self::ESTADOS,
        ]);
    }

    /**
     * Model classes to query: readable document modules narrowed by the tipo/estado filters.
     *
     * @param  array<int, class-string<Model>>  $documentos
     * @param  array<string, mixed>  $datos
     * @return array<int, class-string<Model>>
     */
    private function modelosConsultables(array $documentos, array $datos): array
    {
        $modelos = $documentos;

        if ($tipo = ($datos['tipo'] ?? null)) {
            $claseDeTipo = match ($tipo) {
                'memo', 'oficio' => Memo::class,
                'permiso' => PermisoAdministrativo::class,
                'citacion' => Citacion::class,
                'acta' => ActaConfidencial::class,
                'reunion' => ReunionComunal::class,
            };

            $modelos = array_values(array_intersect($modelos, [$claseDeTipo]));
        }

        if ($datos['estado'] ?? null) {
            $modelos = array_values(array_intersect($modelos, self::MODELOS_CON_ESTADO));
        }

        return $modelos;
    }

    /**
     * Run the filtered query for one model class and map rows for display.
     *
     * @param  class-string<Model>  $clase
     * @param  array<string, mixed>  $datos
     * @return Collection<int, array<string, mixed>>
     */
    private function buscarEn(string $clase, array $datos, User $user): Collection
    {
        $query = $clase::query()
            ->with($clase === PermisoAdministrativo::class ? 'creador' : 'user')
            ->entreFechas($datos['fecha_desde'] ?? null, $datos['fecha_hasta'] ?? null);

        if ($texto = trim((string) ($datos['q'] ?? ''))) {
            $query->search($texto);
        }

        if ($emisor = $datos['emisor'] ?? null) {
            $query->byEmisor((int) $emisor);
        }

        if ($estado = $datos['estado'] ?? null) {
            $query->where('estado', $estado);
        }

        if (in_array($datos['tipo'] ?? null, ['memo', 'oficio'], true)) {
            $query->byTipo($datos['tipo']);
        }

        // Fila a nivel de permiso: los no supervisores solo ven sus propios permisos (HU-03)
        if ($clase === PermisoAdministrativo::class
            && ! $user->hasAnyRole(DocumentosPermitidos::PERFILES_ELIMINACION)) {
            $query->where('user_id', $user->id);
        }

        return $query->get()->map(fn (Model $modelo) => $this->fila($modelo, $clase));
    }

    /**
     * Build one display row of a search result (HU-07: muestra la referencia
     * que permite identificar el archivo).
     *
     * @param  class-string<Model>  $clase
     * @return array<string, mixed>
     */
    private function fila(Model $modelo, string $clase): array
    {
        $fecha = $clase === PermisoAdministrativo::class ? $modelo->fecha_inicio : $modelo->fecha;

        return [
            'id' => $modelo->getKey(),
            'clase' => $clase,
            'tipo' => $this->etiquetaTipo($modelo, $clase),
            'referencia' => $modelo->referencia(),
            'fecha' => $fecha->format('Y-m-d'),
            'titulo' => match ($clase) {
                Memo::class => $modelo->asunto,
                PermisoAdministrativo::class => $modelo->motivo,
                Citacion::class => "{$modelo->estudiante_nombre} — {$modelo->apoderado_nombre}",
                default => $modelo->titulo,
            },
            'emisor' => ($clase === PermisoAdministrativo::class ? $modelo->creador?->name : $modelo->user?->name) ?? '—',
            'estado' => $this->etiquetaEstado($modelo, $clase),
            'ruta_ver' => $this->rutaDetalle($modelo, $clase),
            'ruta_descarga' => $modelo->archivo ? $this->rutaDescarga($clase, $modelo->getKey()) : null,
        ];
    }

    /**
     * @param  class-string<Model>  $clase
     */
    private function etiquetaTipo(Model $modelo, string $clase): string
    {
        return match ($clase) {
            Memo::class => $modelo->tipo === 'oficio' ? 'Oficio' : 'Memo',
            PermisoAdministrativo::class => 'Permiso',
            Citacion::class => 'Citación',
            ActaConfidencial::class => 'Acta',
            ReunionComunal::class => 'Reunión',
        };
    }

    /**
     * @param  class-string<Model>  $clase
     */
    private function etiquetaEstado(Model $modelo, string $clase): ?string
    {
        if ($clase === Citacion::class) {
            return Citacion::ESTADOS[$modelo->estado] ?? $modelo->estado;
        }

        if ($clase === PermisoAdministrativo::class) {
            return self::ESTADOS[$modelo->estado] ?? $modelo->estado;
        }

        return null;
    }

    /**
     * Detail route for the document, when the module has one.
     *
     * @param  class-string<Model>  $clase
     */
    private function rutaDetalle(Model $modelo, string $clase): ?string
    {
        return match ($clase) {
            Memo::class => route('memos.show', $modelo->getKey()),
            Citacion::class => route('citaciones.show', $modelo->getKey()),
            ReunionComunal::class => route('comunales.show', $modelo->getKey()),
            PermisoAdministrativo::class => auth()->user()?->hasAnyRole(DocumentosPermitidos::PERFILES_ELIMINACION)
                ? route('permisos.edit', $modelo->getKey())
                : null,
            default => null,
        };
    }

    /**
     * @param  class-string<Model>  $clase
     */
    private function rutaDescarga(string $clase, int $id): ?string
    {
        return match ($clase) {
            Memo::class => route('memos.download', $id),
            Citacion::class => route('citaciones.download', $id),
            ActaConfidencial::class => route('actas.download', $id),
            ReunionComunal::class => route('comunales.download', $id),
            PermisoAdministrativo::class => route('permisos.download', $id),
            default => null,
        };
    }
}
