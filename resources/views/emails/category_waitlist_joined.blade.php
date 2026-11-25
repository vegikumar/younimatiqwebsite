<x-mail::message>
# Launching Soon: {{ $category->name }}

Thank you for your interest! We will notify you as we launch.

<x-mail::button :url="config('app.url')">
Explore other courses
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
