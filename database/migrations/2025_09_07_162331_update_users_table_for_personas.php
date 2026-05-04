<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- ETAPA 1: Añadir la columna persona_id como NULABLE ---
        // Esto evita el error "not null violation" con los datos existentes.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('persona_id')
                ->nullable() // <-- La clave para evitar el error inicial
                ->after('municipio_id')
                ->constrained('personas')
                ->onDelete('cascade');
        });

        // --- ETAPA 2: Poblar los datos para usuarios existentes ---
        // Buscamos todos los usuarios que ya están en la base de datos (tu Super-Admin).
        $existingUsers = User::all();

        foreach ($existingUsers as $user) {
            // Dividimos el nombre en partes. Asumimos "Nombre Apellido".
            $nameParts = explode(' ', $user->name, 2);
            $nombre = $nameParts[0];
            $apellido = $nameParts[1] ?? 'SuperAdmin'; // Apellido por defecto si no hay

            // Creamos una persona para este usuario
            $persona = Persona::create([
                'nombre' => $nombre,
                'primer_apellido' => $apellido,
                // Usamos un carnet temporal único para evitar conflictos.
                // Es importante que luego actualices esto manualmente desde la app.
                'carnet' => 'SA-' . $user->id,
            ]);

            // Actualizamos al usuario con el ID de la persona recién creada
            $user->persona_id = $persona->id;
            $user->save();
        }

        // --- ETAPA 3: Hacer la columna NO NULABLE ---
        // Ahora que todos los usuarios tienen un persona_id, podemos aplicar la restricción.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('persona_id')->nullable(false)->change();
        });

        // --- ETAPA 4: Eliminar la columna 'name' ---
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Para revertir, hacemos lo opuesto en orden inverso

            // 1. Volvemos a añadir la columna 'name'
            $table->string('name')->after('id')->nullable();

            // 2. Repoblamos el nombre desde la tabla personas antes de eliminar la relación
            // Usamos DB::table para evitar problemas con los modelos durante el rollback
            $usersToUpdate = DB::table('users')->get();
            foreach ($usersToUpdate as $user) {
                $persona = DB::table('personas')->find($user->persona_id);
                if ($persona) {
                    DB::table('users')->where('id', $user->id)->update([
                        'name' => $persona->nombre . ' ' . $persona->primer_apellido
                    ]);
                }
            }

            // 3. Eliminamos la clave foránea y la columna 'persona_id'
            $table->dropForeign(['persona_id']);
            $table->dropColumn('persona_id');
        });
    }
};

