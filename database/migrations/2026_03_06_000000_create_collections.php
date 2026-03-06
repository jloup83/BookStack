<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the pivot table for shelf-collection relationships
        if (!Schema::hasTable('bookshelves_collections')) {
            Schema::create('bookshelves_collections', function (Blueprint $table) {
                $table->unsignedBigInteger('bookshelf_id');
                $table->unsignedBigInteger('collection_id');
                $table->unsignedInteger('order');
                $table->primary(['bookshelf_id', 'collection_id']);
            });
        }

        // Create the pivot table for collection-book relationships
        if (!Schema::hasTable('collection_books')) {
            Schema::create('collection_books', function (Blueprint $table) {
                $table->unsignedBigInteger('collection_id');
                $table->unsignedBigInteger('book_id');
                $table->unsignedInteger('order');
                $table->primary(['collection_id', 'book_id']);
            });
        }

        // Copy existing role permissions from Books for Collections
        $ops = ['View All', 'View Own', 'Create All', 'Create Own', 'Update All', 'Update Own', 'Delete All', 'Delete Own'];
        foreach ($ops as $op) {
            $dbOpName = strtolower(str_replace(' ', '-', $op));
            $permName = 'collection-' . $dbOpName;

            // Skip if already exists
            if (DB::table('role_permissions')->where('name', $permName)->exists()) {
                continue;
            }

            $roleIdsWithBookPermission = DB::table('role_permissions')
                ->leftJoin('permission_role', 'role_permissions.id', '=', 'permission_role.permission_id')
                ->leftJoin('roles', 'roles.id', '=', 'permission_role.role_id')
                ->where('role_permissions.name', '=', 'book-' . $dbOpName)->get(['roles.id'])->pluck('id');

            $permId = DB::table('role_permissions')->insertGetId([
                'name'         => $permName,
                'created_at'   => Carbon::now()->toDateTimeString(),
                'updated_at'   => Carbon::now()->toDateTimeString(),
            ]);

            $rowsToInsert = $roleIdsWithBookPermission->filter(function ($roleId) {
                return !is_null($roleId);
            })->map(function ($roleId) use ($permId) {
                return [
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                ];
            })->toArray();

            DB::table('permission_role')->insert($rowsToInsert);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop created permissions
        $ops = ['collection-create-all', 'collection-create-own', 'collection-delete-all', 'collection-delete-own', 'collection-update-all', 'collection-update-own', 'collection-view-all', 'collection-view-own'];
        $permissionIds = DB::table('role_permissions')->whereIn('name', $ops)
            ->get(['id'])->pluck('id')->toArray();
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('role_permissions')->whereIn('id', $permissionIds)->delete();

        Schema::dropIfExists('collection_books');
        Schema::dropIfExists('bookshelves_collections');

        // Drop related polymorphic items
        DB::table('entities')->where('type', '=', 'collection')->delete();
        DB::table('entity_container_data')->where('entity_type', '=', 'collection')->delete();
    }
};
