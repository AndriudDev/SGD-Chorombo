<?php

namespace App\Services;

use App\Models\ActaConfidencial;
use App\Models\Citacion;
use App\Models\CompromisoReunion;
use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use App\Models\ReunionComunal;
use App\Models\User;

/**
 * Matriz única de permisos por módulo documental.
 *
 * Lectura: reglas vigentes de las Fases 1-3 (HU-01 a HU-06).
 * Eliminación/papelera: HU-08 restringe a Dirección e Inspectoría General
 * (y Administración como gestor técnica del sistema), intersectada con la
 * lectura del módulo para no permitir borrados a ciegas ni fugas HU-05.
 */
class DocumentosPermitidos
{
    /** Perfiles con derecho de eliminación, papelera y bitácora (HU-08). */
    public const PERFILES_ELIMINACION = ['administracion', 'directora', 'inspectoria'];

    /** Claves de módulo usadas por las rutas de papelera/buscador. */
    public const MODULOS = [
        'memos' => Memo::class,
        'permisos' => PermisoAdministrativo::class,
        'citaciones' => Citacion::class,
        'actas' => ActaConfidencial::class,
        'reuniones' => ReunionComunal::class,
    ];

    /** @var array<string, string> etiquetas legibles de los módulos */
    public const ETIQUETAS = [
        'memos' => 'Memos y Oficios',
        'permisos' => 'Permisos Administrativos',
        'citaciones' => 'Citaciones a Apoderados',
        'actas' => 'Actas Confidenciales',
        'reuniones' => 'Reuniones Comunales',
    ];

    /**
     * Roles con lectura por modelo. Lista vacía = cualquier sesión autenticada.
     *
     * @var array<string, array<int, string>>
     */
    private const LECTURA = [
        Memo::class => [],
        PermisoAdministrativo::class => ['administracion', 'directora', 'inspectoria', 'secretaria', 'utp', 'convivencia'],
        Citacion::class => ['administracion', 'directora', 'secretaria', 'utp', 'convivencia'],
        ReunionComunal::class => ['administracion', 'directora', 'secretaria', 'utp', 'convivencia'],
        CompromisoReunion::class => ['administracion', 'directora', 'secretaria', 'utp', 'convivencia'],
        ActaConfidencial::class => ['convivencia', 'directora'],
    ];

    /**
     * Whether the user may read (list/search/download) documents of the given model class.
     *
     * @param  class-string  $modelo
     */
    public static function puedeLeer(User $user, string $modelo): bool
    {
        if (! array_key_exists($modelo, self::LECTURA)) {
            return false;
        }

        $roles = self::LECTURA[$modelo];

        return $roles === [] || $user->hasAnyRole($roles);
    }

    /**
     * Whether the user may delete/restore/purge documents of the given model class.
     *
     * @param  class-string  $modelo
     */
    public static function puedeEliminar(User $user, string $modelo): bool
    {
        return $user->hasAnyRole(self::PERFILES_ELIMINACION)
            && self::puedeLeer($user, $modelo);
    }

    /**
     * Model classes whose documents the user may read.
     *
     * @return array<string, string>
     */
    public static function legiblesPara(User $user): array
    {
        return array_filter(
            self::LECTURA,
            fn (array $roles, string $modelo) => $roles === [] || $user->hasAnyRole($roles),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Document model classes the user may read (excludes child records such
     * as compromisos, which are searched through their parent module).
     *
     * @return array<int, string>
     */
    public static function documentosLegiblesPara(User $user): array
    {
        return array_values(array_intersect(
            array_values(self::MODULOS),
            array_keys(self::legiblesPara($user))
        ));
    }

    /**
     * Module keys the user may read, keyed by module.
     *
     * @return array<string, string>
     */
    public static function modulosLegiblesPara(User $user): array
    {
        $legibles = self::legiblesPara($user);

        return array_filter(
            self::MODULOS,
            fn (string $modelo) => array_key_exists($modelo, $legibles)
        );
    }

    /**
     * Module key for a model class, or null when the class is not a document module.
     *
     * @param  class-string  $modelo
     */
    public static function moduloDe(string $modelo): ?string
    {
        if ($modelo === CompromisoReunion::class) {
            return 'reuniones';
        }

        $modulo = array_search($modelo, self::MODULOS, true);

        return $modulo === false ? null : $modulo;
    }

    /**
     * Model classes belonging to a module key (reuniones also covers its compromisos).
     *
     * @return array<int, string>
     */
    public static function clasesDeModulo(string $modulo): array
    {
        $clase = self::MODULOS[$modulo] ?? null;

        if ($clase === null) {
            return [];
        }

        return $clase === ReunionComunal::class
            ? [$clase, CompromisoReunion::class]
            : [$clase];
    }
}