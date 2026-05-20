<?php

namespace App\Mail;

use App\Models\Leave;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveSubstituteRequest extends Mailable
{
    use Queueable, SerializesModels;

    public Leave $leave;
    public Employee $requester;
    public Employee $substitute;

    public function __construct(Leave $leave, Employee $requester, Employee $substitute)
    {
        $this->leave = $leave;
        $this->requester = $requester;
        $this->substitute = $substitute;
    }

    public function build()
    {
        return $this->view('email.leave_substitute_request')
            ->with([
                'leave' => $this->leave,
                'requester' => $this->requester,
                'substitute' => $this->substitute,
            ])
            ->subject('Leave substitute request');
    }
}
