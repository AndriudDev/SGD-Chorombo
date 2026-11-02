<?php

namespace Tests\Feature;

use App\Models\Memo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Fase2CorrespondenciaAlmacenamientoQaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $originalPublicDisk = null;

    private ?string $isolatedStorageRoot = null;

    protected function beforeRefreshingDatabase(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $activeDatabase = DB::selectOne('select database() as database_name');

        $this->assertSame('testing', $activeDatabase->database_name);
    }

    protected function tearDown(): void
    {
        if ($this->isolatedStorageRoot !== null) {
            File::deleteDirectory($this->isolatedStorageRoot);
        }

        if ($this->originalPublicDisk !== null) {
            config(['filesystems.disks.public' => $this->originalPublicDisk]);
            Storage::forgetDisk('public');
        }

        parent::tearDown();
    }

    public function test_carga_y_descarga_archivo_en_almacenamiento_local_aislado(): void
    {
        $this->configureIsolatedPublicDisk();
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');
        $archivo = UploadedFile::fake()->create('memo-ficticio.pdf', 1, 'application/pdf');

        $response = $this->actingAs($secretaria)->post(route('memos.store'), [
            'folio' => 'QA-ALM-001',
            'tipo' => 'memo',
            'fecha' => '2026-11-01',
            'destinatario' => 'Equipo ficticio',
            'asunto' => 'Carga física aislada',
            'archivo' => $archivo,
        ]);

        $response->assertRedirect(route('memos.index'));
        $response->assertSessionHasNoErrors();
        $memo = Memo::where('folio', 'QA-ALM-001')->firstOrFail();
        $this->assertNotNull($memo->archivo);
        Storage::disk('public')->assertExists($memo->archivo);
        $this->assertTrue(File::isFile($this->isolatedStorageRoot.DIRECTORY_SEPARATOR.$memo->archivo));

        $this->actingAs($secretaria)
            ->get(route('memos.download', $memo))
            ->assertDownload(basename($memo->archivo));
    }

    public function test_reemplaza_archivo_y_elimina_anterior_en_almacenamiento_local_aislado(): void
    {
        $this->configureIsolatedPublicDisk();
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');
        $memo = $this->createMemo($secretaria, 'QA-ALM-002', 'memos/archivo-anterior.pdf');
        Storage::disk('public')->put($memo->archivo, 'contenido ficticio anterior');
        $archivoNuevo = UploadedFile::fake()->create(
            'oficio-ficticio.docx',
            1,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );

        $response = $this->actingAs($secretaria)->put(route('memos.update', $memo), [
            'folio' => $memo->folio,
            'tipo' => 'oficio',
            'fecha' => '2026-11-02',
            'destinatario' => 'Equipo ficticio actualizado',
            'asunto' => 'Reemplazo físico aislado',
            'archivo' => $archivoNuevo,
        ]);

        $response->assertRedirect(route('memos.index'));
        $response->assertSessionHasNoErrors();
        $memo->refresh();
        $this->assertNotNull($memo->archivo);
        $this->assertNotSame('memos/archivo-anterior.pdf', $memo->archivo);
        Storage::disk('public')->assertMissing('memos/archivo-anterior.pdf');
        Storage::disk('public')->assertExists($memo->archivo);
    }

    public function test_elimina_archivo_fisico_al_eliminar_memo(): void
    {
        $this->configureIsolatedPublicDisk();
        $administrador = $this->createUserWithRole('administracion', 'Administración');
        $secretaria = User::factory()->create();
        $memo = $this->createMemo($secretaria, 'QA-ALM-003', 'memos/archivo-a-eliminar.pdf');
        Storage::disk('public')->put($memo->archivo, 'contenido ficticio a eliminar');

        $response = $this->actingAs($administrador)->delete(route('memos.destroy', $memo));

        $response->assertRedirect(route('memos.index'));
        $response->assertSessionHas('success', 'Memo/Oficio eliminado correctamente.');
        $this->assertModelMissing($memo);
        Storage::disk('public')->assertMissing('memos/archivo-a-eliminar.pdf');
    }

    public function test_rechaza_archivo_pdf_con_mime_no_permitido(): void
    {
        $this->configureIsolatedPublicDisk();
        $secretaria = $this->createUserWithRole('secretaria', 'Secretaría');
        $archivo = UploadedFile::fake()->create('contenido-ficticio.pdf', 1, 'text/plain');

        $response = $this->actingAs($secretaria)
            ->from(route('memos.create'))
            ->post(route('memos.store'), [
                'folio' => 'QA-MIME-001',
                'tipo' => 'memo',
                'fecha' => '2026-11-03',
                'destinatario' => 'Equipo ficticio',
                'asunto' => 'MIME incompatible',
                'archivo' => $archivo,
            ]);

        $response->assertRedirect(route('memos.create'));
        $response->assertSessionHasErrors([
            'archivo' => 'Solo se permiten archivos PDF o Word.',
        ]);
        $this->assertDatabaseMissing('memos', [
            'folio' => 'QA-MIME-001',
        ]);
        Storage::disk('public')->assertDirectoryEmpty('memos');
    }

    private function configureIsolatedPublicDisk(): void
    {
        $this->originalPublicDisk = config('filesystems.disks.public');
        $this->isolatedStorageRoot = storage_path('framework/testing/qa-correspondencia-'.Str::uuid());
        File::ensureDirectoryExists($this->isolatedStorageRoot);

        $isolatedDisk = $this->originalPublicDisk;
        $isolatedDisk['root'] = $this->isolatedStorageRoot;
        $isolatedDisk['throw'] = true;

        config(['filesystems.disks.public' => $isolatedDisk]);
        Storage::forgetDisk('public');
    }

    private function createUserWithRole(string $name, string $displayName): User
    {
        $role = Role::create([
            'name' => $name,
            'display_name' => $displayName,
        ]);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function createMemo(User $user, string $folio, string $archivo): Memo
    {
        return Memo::create([
            'folio' => $folio,
            'tipo' => 'memo',
            'fecha' => '2026-11-01',
            'destinatario' => 'Equipo ficticio',
            'asunto' => 'Documento de almacenamiento aislado',
            'archivo' => $archivo,
            'user_id' => $user->id,
        ]);
    }
}
