<?php
// database/seeders/DatabaseSeeder.php
// Ensure Phase 1 seeder is invoked:

$this->call([
    \Database\Seeders\RolePermissionSeeder::class,
]);
