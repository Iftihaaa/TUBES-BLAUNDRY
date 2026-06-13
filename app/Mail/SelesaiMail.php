<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SelesaiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pesanan Laundry Kamu Sudah Selesai! - BLaundry',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.selesai_pemesanan',
            with: ['data' => $this->data],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}