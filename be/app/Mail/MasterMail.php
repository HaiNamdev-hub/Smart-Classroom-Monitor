<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MasterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tieu_de;
    public $view;
    public $data;

    public function __construct(
        string $tieu_de,
        string $view,
        array $data = []
    ) {
        $this->tieu_de = $tieu_de;
        $this->view = $view;
        $this->data = $data;
    }

    public function build()
    {
        return $this
            ->subject($this->tieu_de)
            ->view($this->view)
            ->with([
                'data' => $this->data
            ]);
    }
}