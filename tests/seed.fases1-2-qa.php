<?php

declare(strict_types=1);

use App\Models\Memo;
use App\Models\PermisoAdministrativo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('testing')) {
    throw new RuntimeException('QA fictitious data refused: APP_ENV is not testing.');
}

if (DB::connection()->getDatabaseName() !== 'testing') {
    throw new RuntimeException('QA fictitious data refused: configured database is not testing.');
}

$activeDatabase = DB::selectOne('select database() as database_name');

if ($activeDatabase?->database_name !== 'testing') {
    throw new RuntimeException('QA fictitious data refused: active database is not testing.');
}

$qaBrowserPassword = getenv('QA_BROWSER_PASSWORD');

if (! is_string($qaBrowserPassword) || $qaBrowserPassword === '') {
    throw new RuntimeException('QA fictitious data refused: QA_BROWSER_PASSWORD is not configured.');
}

$accounts = DB::transaction(function () use ($qaBrowserPassword): array {
    $roleDefinitions = [
        'administracion' => 'Administración',
        'directora' => 'Directora',
        'secretaria' => 'Secretaría',
        'utp' => 'UTP',
        'convivencia' => 'Convivencia',
    ];

    $roles = [];

    foreach ($roleDefinitions as $name => $displayName) {
        $roles[$name] = Role::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => 'Rol ficticio para recorrido QA aislado',
        ]);
    }

    $accountDefinitions = [
        'administracion' => ['Administración QA', 'administracion.qa@example.test'],
        'directora' => ['Directora QA', 'directora.qa@example.test'],
        'secretaria' => ['Secretaría QA', 'secretaria.qa@example.test'],
        'utp' => ['UTP QA', 'utp.qa@example.test'],
        'convivencia' => ['Convivencia QA', 'convivencia.qa@example.test'],
    ];

    $users = [];

    foreach ($accountDefinitions as $roleName => [$name, $email]) {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $qaBrowserPassword,
        ]);
        $user->roles()->attach($roles[$roleName]);
        $users[$roleName] = $user;
    }

    Memo::create([
        'folio' => 'QA-MEM-001',
        'tipo' => 'memo',
        'fecha' => '2026-11-01',
        'destinatario' => 'Comunidad educativa ficticia',
        'asunto' => 'Memo ficticio para recorrido manual QA',
        'archivo' => null,
        'user_id' => $users['secretaria']->id,
    ]);

    Memo::create([
        'folio' => 'QA-OFI-001',
        'tipo' => 'oficio',
        'fecha' => '2026-11-02',
        'destinatario' => 'Institución externa ficticia',
        'asunto' => 'Oficio ficticio para filtros QA',
        'archivo' => null,
        'user_id' => $users['secretaria']->id,
    ]);

    PermisoAdministrativo::create([
        'user_id' => $users['secretaria']->id,
        'fecha_inicio' => '2026-11-03',
        'fecha_fin' => '2026-11-03',
        'tipo_jornada' => 'completa',
        'motivo' => 'Solicitud ficticia pendiente de Secretaría',
        'estado' => 'pendiente',
        'created_by' => $users['secretaria']->id,
    ]);

    PermisoAdministrativo::create([
        'user_id' => $users['utp']->id,
        'fecha_inicio' => '2026-11-04',
        'fecha_fin' => '2026-11-04',
        'tipo_jornada' => 'media',
        'motivo' => 'Solicitud ficticia pendiente de UTP',
        'estado' => 'pendiente',
        'created_by' => $users['utp']->id,
    ]);

    PermisoAdministrativo::create([
        'user_id' => $users['convivencia']->id,
        'fecha_inicio' => '2026-10-15',
        'fecha_fin' => '2026-10-15',
        'tipo_jornada' => 'media',
        'motivo' => 'Solicitud ficticia aprobada de Convivencia',
        'estado' => 'aprobado',
        'aprobado_por' => $users['directora']->id,
        'created_by' => $users['convivencia']->id,
    ]);

    return array_map(
        static fn (User $user): array => [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles()->value('name'),
        ],
        $users,
    );
});

fwrite(STDOUT, 'QA_BROWSER_SEED_OK '.json_encode([
    'database' => 'testing',
    'accounts' => array_values($accounts),
    'memos' => Memo::count(),
    'permisos' => PermisoAdministrativo::count(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
