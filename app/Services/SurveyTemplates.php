<?php

namespace App\Services;

/**
 * Pre-built survey templates that HR can apply with a single click on the
 * Create Survey page. Each template returns:
 *   - meta:      defaults for the EmployeeSurvey row
 *   - sections:  optional grouping label for the question builder UI
 *   - questions: ordered list of {section, code, text, type, required, is_enps}
 *
 * To add a new template: just append a new method here + add a row to all().
 *
 * Question types: rating_5 | rating_10 | yes_no | multiple_choice | text
 */
class SurveyTemplates
{
    /**
     * Return list of templates for the create-page dropdown.
     *
     * @return array<string, array{label:string, description:string, type:string}>
     */
    public static function all(): array
    {
        return [
            'engagement_core_001' => [
                'label'       => 'Engagement Survey (Deep) — ENG-CORE-001',
                'description' => '18 questions across Job Satisfaction, Leadership, Culture, Communication, Career Growth, Compensation, eNPS, and Open Feedback. Annual / half-yearly. Anonymous recommended.',
                'type'        => 'employee',
            ],
            'exit_interview_001' => [
                'label'       => 'Exit Interview',
                'description' => 'For departing employees. Captures reason for leaving, manager experience, role fit, and improvement suggestions. Identified (HR-only).',
                'type'        => 'employee',
            ],
            'onboarding_30_60_90' => [
                'label'       => 'Onboarding 30/60/90-day',
                'description' => 'Combined check-in for 30, 60, and 90 day milestones. Covers role clarity, training adequacy, manager support, ramp-up confidence.',
                'type'        => 'employee',
            ],
            'manager_360' => [
                'label'       => 'Manager Effectiveness 360°',
                'description' => '360-degree feedback on a manager — leadership, communication, fairness, growth support, and overall trust.',
                'type'        => 'employee',
            ],
            'diversity_inclusion' => [
                'label'       => 'Diversity & Inclusion',
                'description' => 'Measures belonging, equitable treatment, voice, allyship, and bias signals. Anonymous recommended.',
                'type'        => 'employee',
            ],
            'pulse_default_5q' => [
                'label'       => 'Pulse Survey (5 questions)',
                'description' => 'Standard weekly/monthly pulse: feeling, workload, manager support, motivation, blockers.',
                'type'        => 'pulse',
            ],
            'enps_only' => [
                'label'       => 'eNPS-only Survey',
                'description' => 'Single-question Employee Net Promoter Score (0-10).',
                'type'        => 'enps',
            ],
        ];
    }

    /**
     * Resolve a template by code → full definition.
     *
     * @return array{
     *   meta: array{title:string, description:string, type:string, is_anonymous:bool, frequency:string},
     *   questions: array<int, array{section:?string, code:string, text:string, type:string, required:bool, is_enps:bool, options:?array}>
     * }|null
     */
    public static function get(string $code): ?array
    {
        return match ($code) {
            'engagement_core_001' => self::engagementCore001(),
            'exit_interview_001'  => self::exitInterview001(),
            'onboarding_30_60_90' => self::onboarding306090(),
            'manager_360'         => self::manager360(),
            'diversity_inclusion' => self::diversityInclusion(),
            'pulse_default_5q'    => self::pulseDefault5(),
            'enps_only'           => self::enpsOnly(),
            default               => null,
        };
    }

    /* ──────────────────────────────────────────────────────────────
     * ENG-CORE-001 — Engagement Survey (Deep, 18 questions)
     * ──────────────────────────────────────────────────────────── */
    protected static function engagementCore001(): array
    {
        $rating = fn(string $code, string $text, ?string $section) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'rating_5', 'required' => true, 'is_enps' => false, 'options' => null,
        ];
        $text = fn(string $code, string $text, ?string $section) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'text', 'required' => false, 'is_enps' => false, 'options' => null,
        ];

        return [
            'meta' => [
                'title'        => 'Annual Engagement Survey (ENG-CORE-001)',
                'description'  => 'A deep, comprehensive look at engagement across job satisfaction, leadership, culture, growth, and recognition. Your honest input shapes how we improve as an organization.',
                'type'         => 'employee',
                'is_anonymous' => true,
                'frequency'    => 'once',
            ],
            'questions' => [
                // 1. Job Satisfaction
                $rating('ENG1', 'I am satisfied with my current role.',                    'Job Satisfaction'),
                $rating('ENG2', 'My work is meaningful.',                                  'Job Satisfaction'),
                $rating('ENG3', 'My workload is manageable.',                              'Job Satisfaction'),

                // 2. Leadership & Management
                $rating('ENG4', 'My manager supports my growth.',                          'Leadership & Management'),
                $rating('ENG5', 'I receive constructive feedback.',                        'Leadership & Management'),
                $rating('ENG6', 'Leadership is trustworthy.',                              'Leadership & Management'),

                // 3. Culture & Work Environment
                $rating('ENG7', 'I feel respected at work.',                               'Culture & Work Environment'),
                $rating('ENG8', 'The organization promotes inclusion.',                    'Culture & Work Environment'),
                $rating('ENG9', 'I feel safe sharing opinions.',                           'Culture & Work Environment'),

                // 4. Communication
                $rating('ENG10', 'Company goals are clearly communicated.',                'Communication'),
                $rating('ENG11', 'I am informed about important updates.',                 'Communication'),

                // 5. Career Growth
                $rating('ENG12', 'I have opportunities to learn.',                         'Career Growth'),
                $rating('ENG13', 'I see long-term growth here.',                           'Career Growth'),

                // 6. Compensation & Benefits
                $rating('ENG14', 'Compensation is fair.',                                  'Compensation & Benefits'),
                $rating('ENG15', 'Benefits meet my needs.',                                'Compensation & Benefits'),

                // 7. eNPS
                [
                    'section' => 'eNPS', 'code' => 'ENG16',
                    'text'    => 'How likely are you to recommend this company as a place to work?',
                    'type'    => 'rating_10', 'required' => true, 'is_enps' => true, 'options' => null,
                ],

                // 8. Open Feedback
                $text('ENG17', 'What do you like most?',          'Open Feedback'),
                $text('ENG18', 'What should we improve?',         'Open Feedback'),
            ],
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * Pulse default (parity with auto-seed in SurveyController::store)
     * ──────────────────────────────────────────────────────────── */
    protected static function pulseDefault5(): array
    {
        return [
            'meta' => [
                'title'        => 'Weekly Pulse Check',
                'description'  => 'A short weekly pulse to track team well-being.',
                'type'         => 'pulse',
                'is_anonymous' => true,
                'frequency'    => 'weekly',
            ],
            'questions' => [
                ['section' => null, 'code' => 'P1', 'text' => 'How are you feeling this week?',                'type' => 'rating_5', 'required' => true,  'is_enps' => false, 'options' => null],
                ['section' => null, 'code' => 'P2', 'text' => 'Is your workload manageable?',                  'type' => 'rating_5', 'required' => true,  'is_enps' => false, 'options' => null],
                ['section' => null, 'code' => 'P3', 'text' => 'Are you getting support from your manager?',    'type' => 'rating_5', 'required' => true,  'is_enps' => false, 'options' => null],
                ['section' => null, 'code' => 'P4', 'text' => 'Do you feel motivated at work?',                'type' => 'rating_5', 'required' => true,  'is_enps' => false, 'options' => null],
                ['section' => null, 'code' => 'P5', 'text' => 'Any blocker or concern?',                       'type' => 'text',     'required' => false, 'is_enps' => false, 'options' => null],
            ],
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * eNPS-only
     * ──────────────────────────────────────────────────────────── */
    protected static function enpsOnly(): array
    {
        return [
            'meta' => [
                'title'        => 'Employee Net Promoter Score',
                'description'  => 'A single question to measure how likely employees are to recommend the company.',
                'type'         => 'enps',
                'is_anonymous' => true,
                'frequency'    => 'once',
            ],
            'questions' => [
                [
                    'section' => null, 'code' => 'ENPS1',
                    'text'    => 'How likely are you to recommend this company as a place to work?',
                    'type'    => 'rating_10', 'required' => true, 'is_enps' => true, 'options' => null,
                ],
            ],
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * Exit Interview
     * Identified survey (NOT anonymous) — HR needs to follow up,
     * understand context, and link to the employee record. The
     * "would recommend" question is NOT eNPS-flagged here because
     * exiting employees skew the company-wide eNPS — that should
     * stay clean for current employees only.
     * ──────────────────────────────────────────────────────────── */
    protected static function exitInterview001(): array
    {
        $r = fn(string $code, string $text, ?string $section, bool $req = true) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'rating_5', 'required' => $req, 'is_enps' => false, 'options' => null,
        ];
        $t = fn(string $code, string $text, ?string $section, bool $req = false) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'text', 'required' => $req, 'is_enps' => false, 'options' => null,
        ];
        $mc = fn(string $code, string $text, array $opts, ?string $section, bool $req = true) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'multiple_choice', 'required' => $req, 'is_enps' => false, 'options' => $opts,
        ];

        return [
            'meta' => [
                'title'        => 'Exit Interview',
                'description'  => 'Your honest feedback as you leave helps us improve. All responses go to HR and will be handled confidentially.',
                'type'         => 'employee',
                'is_anonymous' => false, // identified — HR follows up
                'frequency'    => 'once',
            ],
            'questions' => [
                // 1. Reason for leaving
                $mc('EXIT1', 'Primary reason for leaving the company.', [
                    'Better compensation elsewhere',
                    'Career growth / promotion',
                    'Work-life balance / burnout',
                    'Manager / leadership',
                    'Role / responsibilities mismatch',
                    'Relocation / personal reasons',
                    'Health',
                    'Higher studies',
                    'Other',
                ], 'Reason for Leaving'),
                $t('EXIT2', 'Please share more about why you are leaving.', 'Reason for Leaving', false),

                // 2. Role experience
                $r('EXIT3', 'I had a clear understanding of my role and responsibilities.',  'Role Experience'),
                $r('EXIT4', 'I had the tools and resources I needed to do my job well.',    'Role Experience'),
                $r('EXIT5', 'My workload was manageable.',                                  'Role Experience'),

                // 3. Manager & team
                $r('EXIT6', 'My manager supported my growth and development.',              'Manager & Team'),
                $r('EXIT7', 'I received useful feedback from my manager.',                  'Manager & Team'),
                $r('EXIT8', 'My team was a good place to work.',                            'Manager & Team'),

                // 4. Compensation & growth
                $r('EXIT9',  'Compensation and benefits were fair for my role.',            'Compensation & Growth'),
                $r('EXIT10', 'I saw long-term growth opportunities for myself here.',       'Compensation & Growth'),

                // 5. Recommend
                $mc('EXIT11', 'Would you recommend this company to a friend?', ['Yes', 'Maybe', 'No'], 'Recommendation'),

                // 6. Open feedback
                $t('EXIT12', 'What did you enjoy most about working here?', 'Open Feedback', false),
                $t('EXIT13', 'What should we change to retain people like you?', 'Open Feedback', false),
            ],
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * Onboarding 30/60/90-day
     * Combined check-in across the first 3 milestones. New hires
     * complete this once per milestone (HR re-sends at day 30, 60, 90).
     * Identified — HR / manager follow-up is the whole point.
     * ──────────────────────────────────────────────────────────── */
    protected static function onboarding306090(): array
    {
        $r = fn(string $code, string $text, ?string $section, bool $req = true) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'rating_5', 'required' => $req, 'is_enps' => false, 'options' => null,
        ];
        $t = fn(string $code, string $text, ?string $section, bool $req = false) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'text', 'required' => $req, 'is_enps' => false, 'options' => null,
        ];
        $mc = fn(string $code, string $text, array $opts, ?string $section, bool $req = true) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'multiple_choice', 'required' => $req, 'is_enps' => false, 'options' => $opts,
        ];

        return [
            'meta' => [
                'title'        => 'Onboarding Check-in (30 / 60 / 90 day)',
                'description'  => 'Your experience so far. Tell us how onboarding is going so we can support you better.',
                'type'         => 'employee',
                'is_anonymous' => false,
                'frequency'    => 'custom',
                // Only for new hires — auto-hide from anyone who joined > 100 days ago.
                'audience_rules' => [
                    'tenure_max_days' => 100,
                ],
            ],
            'questions' => [
                // Milestone marker
                $mc('OB1', 'Which check-in is this?', ['30 days', '60 days', '90 days'], 'Milestone'),

                // Role clarity
                $r('OB2',  'I have a clear understanding of my role and goals.',          'Role Clarity'),
                $r('OB3',  'I know who to contact when I need help.',                     'Role Clarity'),

                // Training & ramp-up
                $r('OB4',  'My initial training prepared me for the work.',               'Training & Ramp-up'),
                $r('OB5',  'I have access to the tools and systems I need.',              'Training & Ramp-up'),
                $r('OB6',  'I feel productive and confident in my work.',                 'Training & Ramp-up'),

                // Manager & team
                $r('OB7',  'My manager regularly checks in with me.',                     'Manager & Team'),
                $r('OB8',  'My team has welcomed me and made me feel included.',          'Manager & Team'),
                $r('OB9',  'I am getting helpful feedback on my early work.',             'Manager & Team'),

                // Engagement
                $r('OB10', 'I am excited about my future at this company.',               'Engagement'),
                $r('OB11', 'My role matches what I expected when I joined.',              'Engagement'),

                // Open feedback
                $t('OB12', 'What is going well in your onboarding so far?',               'Open Feedback', false),
                $t('OB13', 'What could we improve in your onboarding experience?',        'Open Feedback', false),
            ],
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * Manager Effectiveness 360°
     * Anonymous so reports/peers can give honest feedback. The
     * subject manager's name/ID is captured in OB so HR knows
     * which leader the feedback is about (free-text by design —
     * could be upgraded to an employee-picker later).
     * ──────────────────────────────────────────────────────────── */
    protected static function manager360(): array
    {
        $r = fn(string $code, string $text, ?string $section) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'rating_5', 'required' => true, 'is_enps' => false, 'options' => null,
        ];
        $t = fn(string $code, string $text, ?string $section, bool $req = false) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'text', 'required' => $req, 'is_enps' => false, 'options' => null,
        ];
        $mc = fn(string $code, string $text, array $opts, ?string $section, bool $req = true) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'multiple_choice', 'required' => $req, 'is_enps' => false, 'options' => $opts,
        ];

        return [
            'meta' => [
                'title'        => 'Manager Effectiveness 360°',
                'description'  => 'Anonymous 360° feedback for a manager. Be honest and constructive — your input helps them grow as a leader.',
                'type'         => 'employee',
                'is_anonymous' => true,
                'frequency'    => 'once',
            ],
            'questions' => [
                // Context
                $t('M0', 'Manager being reviewed (full name).', 'Context', true),
                $mc('M1', 'Your relationship to this manager.', [
                    'Direct report',
                    'Skip-level report',
                    'Peer / cross-functional',
                    'Their manager',
                ], 'Context'),

                // Leadership
                $r('M2', 'Sets clear goals and expectations.',                              'Leadership'),
                $r('M3', 'Makes good decisions, even under pressure.',                      'Leadership'),
                $r('M4', 'Owns mistakes and learns from them.',                             'Leadership'),

                // Communication
                $r('M5', 'Communicates clearly and timely.',                                'Communication'),
                $r('M6', 'Listens and is open to different views.',                         'Communication'),
                $r('M7', 'Gives useful, actionable feedback.',                              'Communication'),

                // People & Growth
                $r('M8',  'Coaches and develops their team members.',                       'People & Growth'),
                $r('M9',  'Recognizes good work fairly.',                                   'People & Growth'),
                $r('M10', 'Treats everyone with respect and fairness.',                     'People & Growth'),

                // Trust & Safety
                $r('M11', 'I can trust this manager.',                                      'Trust & Safety'),
                $r('M12', 'It is safe to disagree with this manager.',                      'Trust & Safety'),

                // Overall
                $r('M13', 'Overall, this manager is effective in their role.',              'Overall'),

                // Open feedback
                $t('M14', 'What does this manager do well? Be specific with examples.',     'Open Feedback'),
                $t('M15', 'What should this manager work on or do differently?',            'Open Feedback'),
            ],
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * Diversity & Inclusion
     * Anonymous by default — measures belonging, equitable treatment,
     * voice, allyship, and bias signals. Demographic capture
     * (gender / ethnicity / age band) intentionally omitted from
     * this template — that is sensitive PII and should be a
     * deliberate, optional add-on per company policy.
     * ──────────────────────────────────────────────────────────── */
    protected static function diversityInclusion(): array
    {
        $r = fn(string $code, string $text, ?string $section) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'rating_5', 'required' => true, 'is_enps' => false, 'options' => null,
        ];
        $yn = fn(string $code, string $text, ?string $section, bool $req = true) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'yes_no', 'required' => $req, 'is_enps' => false, 'options' => null,
        ];
        $t = fn(string $code, string $text, ?string $section, bool $req = false) => [
            'section' => $section, 'code' => $code, 'text' => $text,
            'type' => 'text', 'required' => $req, 'is_enps' => false, 'options' => null,
        ];

        return [
            'meta' => [
                'title'        => 'Diversity & Inclusion Survey',
                'description'  => 'Help us build a workplace where everyone belongs. Your responses are anonymous.',
                'type'         => 'employee',
                'is_anonymous' => true,
                'frequency'    => 'once',
            ],
            'questions' => [
                // Belonging
                $r('DI1', 'I feel I belong at this company.',                               'Belonging'),
                $r('DI2', 'I can be myself at work without fear of judgement.',             'Belonging'),
                $r('DI3', 'My team values different perspectives and backgrounds.',         'Belonging'),

                // Equity & fairness
                $r('DI4', 'People of all backgrounds get equal opportunities here.',        'Equity & Fairness'),
                $r('DI5', 'Promotion and pay decisions are fair.',                          'Equity & Fairness'),
                $r('DI6', 'Hiring at this company is unbiased.',                            'Equity & Fairness'),

                // Voice & psychological safety
                $r('DI7', 'I can speak up without fear of negative consequences.',          'Voice & Safety'),
                $r('DI8', 'My ideas and contributions are heard and respected.',            'Voice & Safety'),
                $r('DI9', 'Leaders take inclusion seriously, not just as a slogan.',        'Voice & Safety'),

                // Bias signals (yes/no for clean signals)
                $yn('DI10', 'Have you witnessed disrespectful or biased behavior at work?',           'Bias Signals'),
                $yn('DI11', 'Have you personally experienced disrespectful or biased behavior?',     'Bias Signals'),
                $yn('DI12', 'If yes, did you feel comfortable reporting it through formal channels?','Bias Signals', false),

                // Allyship
                $r('DI13', 'Colleagues here actively support and advocate for each other.','Allyship'),

                // Open
                $t('DI14', 'What is one thing we could do to make this a more inclusive workplace?', 'Open Feedback', false),
            ],
        ];
    }
}
