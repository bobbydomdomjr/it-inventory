<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SystemAlert extends Notification
{
    use Queueable;
    public function __construct(public string $title, public string $message, public string $url = '#', public string $level = 'info', public ?string $eventKey = null) {}
    public function via(object $notifiable): array { return ['database', 'mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->greeting('Hello '.$notifiable->name.',')->line($this->message);
        if ($this->url !== '#') {
            $mail->action('Open IT Inventory', $this->url);
        }
        return $mail->line('This is an automated notification from '.config('app.name').'.');
    }
    public function toDatabase(object $notifiable): array
    {
        return ['title'=>$this->title, 'message'=>$this->message, 'url'=>$this->url, 'level'=>$this->level, 'created_at'=>now()->toIso8601String(), 'event_key'=>$this->eventKey];
    }
}
