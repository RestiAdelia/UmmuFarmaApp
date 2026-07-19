<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    protected $bookingId;
    protected $tanggal;
    protected $jam;

    /**
     * Create a new notification instance.
     */
    public function __construct($bookingId, $tanggal, $jam)
    {
        $this->bookingId = $bookingId;
        $this->tanggal = $tanggal;
        $this->jam = $jam;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->bookingId,
            'title'      => 'Jadwal Dibatalkan',
            'message'    => "Mohon maaf, tiket Anda pada hari {$this->tanggal} jam {$this->jam} terpaksa dibatalkan karena operasional klinik sedang ditutup. Silakan lakukan pemesanan ulang.",
            'tanggal'    => $this->tanggal,
            'jam'        => $this->jam,
            'type'       => 'booking_cancelled'
        ];
    }
}
