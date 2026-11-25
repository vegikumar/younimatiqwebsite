@extends('layouts.app')
@section('seo_title', 'Free Trial Class | Younimatiq')
@section('content')
<x-brand-hero class="mt-8 mb-8" eyebrow="Try For Free">
    <h1 class="brand-title">Book a <span class="text-brand-accent">Trial Class</span></h1>
</x-brand-hero>
<div class="pb-20 px-4 max-w-4xl mx-auto flex justify-center mt-8">
    <button class="btn btn-primary btn-lg px-8 py-4 text-lg font-bold" onclick="openLeadModal(event)">Book Now</button>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if(typeof openLeadModal === 'function') {
            openLeadModal();
        }
    });
</script>
@endsection
