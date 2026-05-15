<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// tambahan agar bisa mengirim attachment
use Illuminate\Mail\Mailables\Attachment;

class laporanBeban extends Mailable
{
    use Queueable, SerializesModels;

    // tambahan untuk data dan konten pdf
    public $data;
    public $pdfContent;

    /**
     * Create a new message instance.
     */
    // public function __construct()
    // {
    //     //
    // }

    public function __construct($data, $pdfContent)
    {
        $this->data = $data;
        $this->pdfContent = $pdfContent;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan Beban',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.laporan_beban',
            with: [
                'data' => $this->data,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // ganti isi return dengan kode program untuk membuat invoice 
        return [
            // tambahkan pemrosesan attachment
            Attachment::fromData(fn () => $this->pdfContent, 'laporan_beban.pdf')
            ->withMime('application/pdf'),
        ];
    }
}