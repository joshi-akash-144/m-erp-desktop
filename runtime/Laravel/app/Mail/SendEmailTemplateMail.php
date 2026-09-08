<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmailTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *$html
     * @return void
     */
    protected $emailSubject;
    protected $messageContent;
    protected $users;
    public $direct_file;
    public $fileUrl;
    public $file_as;
    public $fileMimeType;

    public function __construct($emailSubject, $messageContent, $users, $direct_file, $fileUrl, $file_as, $fileMimeType)
    {
        $this->emailSubject = $emailSubject; // subject 
        $this->messageContent = $messageContent; //body
        $this->users = $users;
        $this->direct_file = $direct_file;
        $this->fileUrl = $fileUrl;
        $this->file_as = $file_as;
        $this->fileMimeType = $fileMimeType;
    }

    public function build()
    {
        $mail = $this->subject($this->emailSubject)->html($this->messageContent);

        if (!empty($this->direct_file)) {
            $mail->attach(
                $this->direct_file->getRealPath(),
                [
                    'as'   => $this->direct_file->getClientOriginalName(),
                    'mime' => $this->direct_file->getClientMimeType(),
                ]
            );
        }

        if (!empty($this->fileUrl)) {
            $mail->attach($this->fileUrl, [
                'as'   => $this->file_as,
                'mime' => $this->fileMimeType,
            ]);
        }

        return $mail;
    }
}
