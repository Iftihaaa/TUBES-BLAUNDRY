<?php

namespace App\Mail;

use App\Models\Penggajian;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SlipGajiMail extends Mailable
{
    use Queueable, SerializesModels;

    public Penggajian $penggajian;
    public ?string $pdfContent;

    /**
     * Create a new message instance.
     */
    public function __construct(Penggajian $penggajian, ?string $pdfContent = null)
    {
        $this->penggajian = $penggajian;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $mail = $this
            ->subject('Slip Gaji ' . $this->penggajian->id_penggajian)
            ->view('emails.slip_gaji');

        if ($this->pdfContent) {
            $mail->attachData($this->pdfContent, 'slip-gaji.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
