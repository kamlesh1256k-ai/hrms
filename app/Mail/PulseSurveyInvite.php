<?php

namespace App\Mail;

use App\Models\EmployeeSurvey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email sent to an employee inviting them to take a pulse survey, or
 * reminding them that a survey is still open.
 */
class PulseSurveyInvite extends Mailable
{
    use Queueable, SerializesModels;

    public EmployeeSurvey $survey;
    public string $employeeName;
    public bool   $isReminder;

    public function __construct(EmployeeSurvey $survey, string $employeeName, bool $isReminder = false)
    {
        $this->survey       = $survey;
        $this->employeeName = $employeeName;
        $this->isReminder   = $isReminder;
    }

    public function build()
    {
        $subject = $this->isReminder
            ? __('Reminder: ') . $this->survey->title
            : __('New pulse survey: ') . $this->survey->title;

        return $this->subject($subject)
            ->view('emails.pulse_survey_invite');
    }
}
