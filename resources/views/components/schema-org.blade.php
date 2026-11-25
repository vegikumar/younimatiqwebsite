@props([
    'type' => 'WebPage',
    'name' => null,
    'description' => null,
    'url' => null,
    'faqs' => [],
    'course' => null,
    'breadcrumbs' => [],
])

@php
    $siteName = config('app.name', 'Younimatiq');
    $siteUrl = rtrim(config('app.url') ?: url('/'), '/');
    $pageUrl = $url ?? request()->url();
    $pageName = $name ?? $siteName;
    $defaults = \App\Support\SeoHelper::defaultGlobals();
    $pageDescription = $description ?? $defaults['description'];
    $logoUrl = file_exists(public_path('logo.webp')) ? $siteUrl . '/logo.webp' : $siteUrl . '/og-default.png';

    $schemaGraph = [];

    $schemaGraph[] = [
        '@type' => ['Organization', 'EducationalOrganization'],
        '@id' => $siteUrl . '/#organization',
        'name' => $siteName,
        'url' => $siteUrl,
        'description' => $defaults['description'],
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $logoUrl,
            'width' => 1000,
            'height' => 300,
        ],
        'image' => $siteUrl . '/og-default.png',
        'email' => 'support@younimatiq.com',
        'telephone' => '+91-89819-18383',
        'address' => [
            '@type' => 'PostalAddress',
            'addressRegion' => 'Texas',
            'addressCountry' => 'US',
        ],
        'areaServed' => [
            '@type' => 'Place',
            'name' => 'Worldwide',
        ],
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => '+91-89819-18383',
                'email' => 'support@younimatiq.com',
                'availableLanguage' => ['English'],
            ],
        ],
        'sameAs' => array_values(array_filter([
            'https://www.facebook.com/younimatiq',
            'https://x.com/younimatiq/',
            'https://www.youtube.com/@younimatiq',
            'https://www.instagram.com/younimatiq/',
            'https://www.linkedin.com/company/younimatiq',
            'https://www.pinterest.com/younimatiq/',
        ])),
    ];

    $schemaGraph[] = [
        '@type' => 'WebSite',
        '@id' => $siteUrl . '/#website',
        'url' => $siteUrl,
        'name' => $siteName,
        'description' => $defaults['description'],
        'publisher' => ['@id' => $siteUrl . '/#organization'],
        'inLanguage' => str_replace('_', '-', app()->getLocale()),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $siteUrl . '/courses?search={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    if ($type === 'Course' && is_array($course) && ! empty($course['name'])) {
        $courseNode = [
            '@type' => 'Course',
            '@id' => ($course['url'] ?? $pageUrl) . '/#course',
            'name' => $course['name'],
            'description' => $course['description'] ?? $pageDescription,
            'url' => $course['url'] ?? $pageUrl,
            'provider' => [
                '@type' => 'EducationalOrganization',
                '@id' => $siteUrl . '/#organization',
                'name' => $siteName,
                'sameAs' => $siteUrl,
            ],
            'isAccessibleForFree' => false,
            'inLanguage' => 'en',
        ];

        if (! empty($course['image'])) {
            $courseNode['image'] = \App\Support\SeoHelper::absoluteImageUrl($course['image']);
        }

        if (! empty($course['age_group'])) {
            $courseNode['educationalLevel'] = $course['age_group'];
        }

        if (isset($course['price']) && $course['price'] !== null && $course['price'] !== '') {
            $courseNode['offers'] = [
                '@type' => 'Offer',
                'category' => 'Paid',
                'priceCurrency' => $course['currency'] ?? 'USD',
                'price' => (string) $course['price'],
                'url' => $course['url'] ?? $pageUrl,
                'availability' => 'https://schema.org/InStock',
            ];
        }

        $schemaGraph[] = $courseNode;

        $schemaGraph[] = [
            '@type' => 'WebPage',
            '@id' => $pageUrl . '/#webpage',
            'url' => $pageUrl,
            'name' => $pageName,
            'description' => $pageDescription,
            'about' => ['@id' => $courseNode['@id']],
            'isPartOf' => ['@id' => $siteUrl . '/#website'],
            'publisher' => ['@id' => $siteUrl . '/#organization'],
        ];
    } else {
        $schemaGraph[] = [
            '@type' => $type === 'Course' ? 'WebPage' : $type,
            '@id' => $pageUrl . '/#webpage',
            'url' => $pageUrl,
            'name' => $pageName,
            'description' => $pageDescription,
            'isPartOf' => ['@id' => $siteUrl . '/#website'],
            'about' => ['@id' => $siteUrl . '/#organization'],
            'publisher' => ['@id' => $siteUrl . '/#organization'],
        ];
    }

    if (! empty($faqs)) {
        $faqItems = [];
        foreach ($faqs as $faq) {
            $faqItems[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ];
        }

        $schemaGraph[] = [
            '@type' => 'FAQPage',
            '@id' => $pageUrl . '/#faq',
            'mainEntity' => $faqItems,
        ];
    }

    $breadcrumbItems = [];
    $position = 1;
    foreach ($breadcrumbs as $crumb) {
        if (empty($crumb['name'])) {
            continue;
        }
        $item = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $crumb['name'],
        ];
        if (! empty($crumb['url'])) {
            $item['item'] = $crumb['url'];
        }
        $breadcrumbItems[] = $item;
        $position++;
    }
    if (! empty($breadcrumbItems)) {
        $schemaGraph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $pageUrl . '/#breadcrumb',
            'itemListElement' => $breadcrumbItems,
        ];
    }

    $schemaGraph[] = [
        '@type' => 'LocalBusiness',
        '@id' => $siteUrl . '/#localbusiness',
        'name' => $siteName,
        'url' => $siteUrl,
        'image' => $siteUrl . '/og-default.png',
        'logo' => $logoUrl,
        'email' => 'support@younimatiq.com',
        'telephone' => '+91-89819-18383',
        'priceRange' => '$$',
        'address' => [
            '@type' => 'PostalAddress',
            'addressRegion' => 'Texas',
            'addressCountry' => 'US',
        ],
        'parentOrganization' => ['@id' => $siteUrl . '/#organization'],
    ];

    $finalSchema = [
        '@context' => 'https://schema.org',
        '@graph' => $schemaGraph,
    ];
@endphp
<script type="application/ld+json">{!! json_encode($finalSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
