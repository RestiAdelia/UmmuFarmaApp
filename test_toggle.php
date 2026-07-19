<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create(
    '/api/jadwal/toggle', 'POST',
    ['tgl_jadwal' => '2026-07-20', 'jam_mulai' => '08:00:00', 'is_aktif' => false, 'force_close' => true]
);

$response = $kernel->handle($request);
echo $response->getContent();
