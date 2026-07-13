<?php

namespace App\Observers;

use App\Models\LayananTerapi;
use App\Services\JadwalGeneratorService;

class LayananTerapiObserver
{
    /**
     * Handle the LayananTerapi "created" event.
     */
    public function created(LayananTerapi $layanan): void
    {
        if ($layanan->status === 'aktif') {
            app(JadwalGeneratorService::class)->generateUntukLayanan($layanan);
        }
    }

    /**
     * Handle the LayananTerapi "updated" event.
     */
    public function updated(LayananTerapi $layanan): void
    {
        if ($layanan->wasChanged('status')) {
            $service = app(JadwalGeneratorService::class);
            if ($layanan->status === 'aktif') {
                $service->generateUntukLayanan($layanan);
            } elseif ($layanan->status === 'nonaktif') {
                $service->nonaktifkanSlotKosong($layanan);
            }
        }
    }
}
