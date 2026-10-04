<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migration 009 originally used "staff" as a default; never grant roles implicitly.
        DB::table('users')
            ->where('is_admin', false)
            ->where('role', 'staff')
            ->update(['role' => null]);

        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            try {
                DB::statement('CREATE TABLE users_rebuilt (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    email_verified_at DATETIME NULL,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    is_admin TINYINT(1) NOT NULL DEFAULT 0,
                    role VARCHAR(20) NULL DEFAULT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    last_login_at DATETIME NULL
                )');
                DB::statement('INSERT INTO users_rebuilt
                    (id, name, email, email_verified_at, password, remember_token, created_at,
                     updated_at, is_admin, role, is_active, last_login_at)
                    SELECT id, name, email, email_verified_at, password, remember_token, created_at,
                     updated_at, is_admin, role, is_active, last_login_at FROM users');
                Schema::drop('users');
                Schema::rename('users_rebuilt', 'users');
                DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
            } finally {
                Schema::enableForeignKeyConstraints();
            }
            return;
        }

        DB::statement('ALTER TABLE users MODIFY role VARCHAR(20) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // Deliberately keep implicit role grants disabled on rollback.
    }
};
