<?php

return [
    'sort_options' => [
        'default' => 'Default Sorting',
        'title_asc' => 'Title A-Z',
        'title_desc' => 'Title Z-A',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'newest' => 'Newest First',
    ],

    /*
     * Fallback groups when DB categories are unavailable.
     * Prefer DB categories passed from the controller.
     */
    'category_groups' => [
        'Programs' => [
            ['label' => 'Coding', 'slug' => 'coding'],
            ['label' => 'Lifeskills', 'slug' => 'lifeskills'],
            ['label' => 'STEM', 'slug' => 'stem'],
        ],
        'Camps' => [
            ['label' => 'Back to School', 'slug' => 'back-to-school'],
            ['label' => 'AI Camp', 'slug' => 'ai-camp'],
            ['label' => 'Arts & Animation Camp', 'slug' => 'arts-animation-camp'],
            ['label' => 'Minecraft Camp', 'slug' => 'minecraft-camp'],
            ['label' => 'Roblox Camp', 'slug' => 'roblox-camp'],
            ['label' => 'Robotics Camp', 'slug' => 'robotics-camp'],
        ],
    ],

    'age_tags' => [
        'Explorers (6-8)',
        'Voyagers (9-11)',
        'Pioneers (12-14)',
    ],

    'price_types' => [
        'free' => 'Free',
        'paid' => 'Paid',
    ],

    'class_types' => [
        'live' => 'Live Class',
        'self-paced' => 'Pre-Recorded',
    ],

    'age_group_tags' => [
        'explorers' => ['Explorers (6-8)', '6 - 8 Years', '6 - 9 Years', '6 - 10 Years'],
        'voyagers' => ['Voyagers (9-11)', '9 - 12', '9 - 13 Years', '8 - 13 Years'],
        'pioneers' => ['Pioneers (12-14)', '10 - 15 Years', '10 14 Years'],
    ],

    /*
     * Individual school grades for nav / hero filters (not age bands).
     * Values map to courses.grade_min / grade_max (K = 0).
     */
    'grades' => [
        'k' => ['label' => 'Kindergarten', 'value' => 0],
        '1' => ['label' => 'Grade 1', 'value' => 1],
        '2' => ['label' => 'Grade 2', 'value' => 2],
        '3' => ['label' => 'Grade 3', 'value' => 3],
        '4' => ['label' => 'Grade 4', 'value' => 4],
        '5' => ['label' => 'Grade 5', 'value' => 5],
        '6' => ['label' => 'Grade 6', 'value' => 6],
        '7' => ['label' => 'Grade 7', 'value' => 7],
        '8' => ['label' => 'Grade 8', 'value' => 8],
        '9' => ['label' => 'Grade 9', 'value' => 9],
        '10' => ['label' => 'Grade 10', 'value' => 10],
        '11' => ['label' => 'Grade 11', 'value' => 11],
        '12' => ['label' => 'Grade 12', 'value' => 12],
    ],

    /*
     * Optional band aliases (legacy redirects / filters).
     */
    'grade_bands' => [
        'k-2' => [
            'label' => 'Grades K–2',
            'subtitle' => 'Early elementary',
            'grade_min' => 0,
            'grade_max' => 2,
        ],
        '3-5' => [
            'label' => 'Grades 3–5',
            'subtitle' => 'Upper elementary',
            'grade_min' => 3,
            'grade_max' => 5,
        ],
        '6-8' => [
            'label' => 'Grades 6–8',
            'subtitle' => 'Middle school',
            'grade_min' => 6,
            'grade_max' => 8,
        ],
        '9-12' => [
            'label' => 'Grades 9–12',
            'subtitle' => 'High school',
            'grade_min' => 9,
            'grade_max' => 12,
        ],
    ],

    'camp_slug_keywords' => [
        'ai-camp' => ['ai camp', 'artificial intelligence', 'ai coding'],
        'arts-animation-camp' => ['arts', 'animation', 'drawing'],
        'minecraft-camp' => ['minecraft'],
        'roblox-camp' => ['roblox', 'obby'],
        'robotics-camp' => ['robotics', 'robot', 'arduino', 'micro:bit'],
        'lifeskills' => ['gita', 'moral', 'sanatan', 'mindful'],
        'coding' => ['scratch', 'python', 'coding'],
        'back-to-school' => ['back to school'],
    ],
];
