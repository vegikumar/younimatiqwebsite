@php
    $at = static fn (string $key): string => '@'.$key;
    $homeUrl = url('/');
    $faqSchema = [
        $at('context') => 'https://schema.org',
        $at('type') => 'FAQPage',
        $at('id') => $homeUrl.'/#faq',
        'mainEntity' => [
            [
                $at('type') => 'Question',
                'name' => 'How often are classes, and how long do they run?',
                'acceptedAnswer' => [
                    $at('type') => 'Answer',
                    'text' => 'Most students attend 2–3 sessions per week, 45–55 minutes each. Schedules flex around school, sports, and family commitments.',
                ],
            ],
            [
                $at('type') => 'Question',
                'name' => 'What devices does my child need?',
                'acceptedAnswer' => [
                    $at('type') => 'Answer',
                    'text' => 'A laptop or tablet with a webcam and stable internet connection. Coding and AI Mastery tracks work best on a laptop.',
                ],
            ],
            [
                $at('type') => 'Question',
                'name' => "Is the curriculum aligned with my child's school?",
                'acceptedAnswer' => [
                    $at('type') => 'Answer',
                    'text' => "Yes — coaches customize sessions to complement your child's grade-level curriculum, aligned to Common Core standards.",
                ],
            ],
            [
                $at('type') => 'Question',
                'name' => 'Can my child try a class before enrolling?',
                'acceptedAnswer' => [
                    $at('type') => 'Answer',
                    'text' => 'Absolutely — every family starts with one free trial class, no commitment required.',
                ],
            ],
            [
                $at('type') => 'Question',
                'name' => 'Can my child combine two subjects, like Coding and AI Mastery?',
                'acceptedAnswer' => [
                    $at('type') => 'Answer',
                    'text' => "Yes, many families mix and match tracks. We'll help you build a schedule that fits.",
                ],
            ],
            [
                $at('type') => 'Question',
                'name' => 'Are tutors background-checked?',
                'acceptedAnswer' => [
                    $at('type') => 'Answer',
                    'text' => 'Yes. Younimatiq tutors are background-checked and teach on a COPPA-conscious platform designed for K–12 families.',
                ],
            ],
        ],
    ];
    $howToSchema = [
        $at('context') => 'https://schema.org',
        $at('type') => 'HowTo',
        'name' => 'How to start live online classes for kids on Younimatiq',
        'description' => 'Three steps to enroll your child in live 1:1 or small-group coaching at Younimatiq.',
        'totalTime' => 'PT10M',
        'step' => [
            [
                $at('type') => 'HowToStep',
                'position' => 1,
                'name' => 'Get started',
                'text' => 'Tell us a bit about your child — interests, grade, and goals. It takes about two minutes and unlocks a free trial class.',
                'url' => $homeUrl.'/#how',
            ],
            [
                $at('type') => 'HowToStep',
                'position' => 2,
                'name' => 'Get matched to the right tutor and track',
                'text' => 'We pair your child with a background-checked tutor suited to their pace, subject track, and schedule.',
                'url' => $homeUrl.'/#how',
            ],
            [
                $at('type') => 'HowToStep',
                'position' => 3,
                'name' => 'Track real progress every week',
                'text' => "Follow milestones, projects, and coach notes from your child's dashboard every week.",
                'url' => $homeUrl.'/#how',
            ],
        ],
    ];
    $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;
@endphp
<script type="application/ld+json">{!! json_encode($faqSchema, $jsonFlags) !!}</script>
<script type="application/ld+json">{!! json_encode($howToSchema, $jsonFlags) !!}</script>
