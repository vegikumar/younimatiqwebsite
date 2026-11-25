@extends('layouts.app')

@section('title', 'Leave a Review – ' . $course->title)

@push('head')
<meta name="description" content="Share your experience with {{ $course->title }} at Younimatiq. Your review helps other families make informed decisions.">
<style>
  .review-hero {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
    padding: 64px 24px 80px;
    text-align: center;
    color: #fff;
    position: relative;
    overflow: hidden;
  }
  .review-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(96,165,250,0.25) 0%, transparent 60%);
    pointer-events: none;
  }
  .review-hero-content { position: relative; z-index: 1; max-width: 600px; margin: auto; }
  .review-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 999px;
    padding: 6px 18px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #dbeafe;
  }
  .review-hero h1 {
    font-family: var(--font-headline, 'Quicksand', sans-serif);
    font-size: clamp(24px, 5vw, 38px);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 12px;
  }
  .review-hero h1 em { font-style: normal; color: #86efac; }
  .review-hero p {
    font-size: 15px;
    color: rgba(255,255,255,0.8);
    line-height: 1.6;
    max-width: 460px;
    margin: auto;
  }

  /* ─── Main layout ─── */
  .review-page-wrap {
    min-height: 60vh;
    background: var(--color-surface-container-low, #f8fafc);
    display: flex;
    justify-content: center;
    padding: 0 16px 80px;
  }
  .review-card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 4px 32px rgba(37,99,235,0.10);
    padding: 40px 40px;
    width: 100%;
    max-width: 580px;
    border: 1px solid var(--color-outline-variant, #e2e8f0);
    margin-top: -44px;
    position: relative;
    z-index: 10;
    margin-bottom: 60px;
  }

  /* ─── Alerts ─── */
  .review-success {
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 28px;
  }
  .review-success .icon { font-size: 28px; flex-shrink: 0; }
  .review-success strong { display: block; font-weight: 700; color: #15803d; margin-bottom: 4px; font-size: 15px; }
  .review-success span { font-size: 13px; color: #166534; }

  .review-error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 28px;
  }
  .review-error .icon { font-size: 24px; flex-shrink: 0; }
  .review-error strong { display: block; font-weight: 700; color: #b91c1c; margin-bottom: 4px; }
  .review-error span { font-size: 13px; color: #7f1d1d; }

  /* ─── Form elements ─── */
  .rv-form-group { margin-bottom: 24px; }
  .rv-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--color-on-surface, #0f172a);
    margin-bottom: 8px;
    letter-spacing: 0.01em;
    text-transform: uppercase;
  }
  .rv-label .req { color: #ef4444; margin-left: 2px; }

  .rv-input,
  .rv-textarea {
    width: 100%;
    border: 1.5px solid var(--color-outline-variant, #e2e8f0);
    border-radius: 12px;
    padding: 14px 16px;
    font-size: 15px;
    font-family: inherit;
    color: var(--color-on-surface, #0f172a);
    background: var(--color-surface-container-low, #fafafa);
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
  }
  .rv-input:focus,
  .rv-textarea:focus {
    border-color: var(--blue, #2563eb);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.10);
    background: #fff;
  }
  .rv-input.is-invalid,
  .rv-textarea.is-invalid {
    border-color: #ef4444;
  }
  .rv-textarea { resize: vertical; min-height: 130px; }
  .rv-hint { font-size: 12px; color: var(--color-on-surface-variant, #64748b); margin-top: 5px; }
  .rv-error { color: #ef4444; font-size: 12px; margin-top: 5px; }

  /* ─── Star Rating ─── */
  .star-group-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--color-on-surface, #0f172a);
    margin-bottom: 10px;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.01em;
  }
  .star-group-label .req { color: #ef4444; }

  .star-row {
    display: flex;
    gap: 4px;
    flex-direction: row-reverse;
    justify-content: flex-end;
  }
  .star-row input[type="radio"] { display: none; }
  .star-row label {
    font-size: 40px;
    cursor: pointer;
    color: #d1d5db;
    transition: color 0.15s, transform 0.15s;
    line-height: 1;
    user-select: none;
  }
  .star-row label:hover,
  .star-row label:hover ~ label,
  .star-row input[type="radio"]:checked ~ label {
    color: #f59e0b;
  }
  .star-row label:hover { transform: scale(1.18); }
  .star-caption {
    font-size: 13px;
    color: var(--color-on-surface-variant, #64748b);
    margin-top: 8px;
    font-weight: 500;
    min-height: 20px;
    transition: opacity 0.2s;
  }

  /* ─── Submit button ─── */
  .rv-submit {
    width: 100%;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    font-family: inherit;
    font-size: 16px;
    font-weight: 700;
    border: none;
    border-radius: 14px;
    padding: 17px 24px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 18px rgba(37,99,235,0.30);
    margin-top: 8px;
  }
  .rv-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(37,99,235,0.40);
  }
  .rv-submit:active { transform: translateY(0); }

  /* ─── Footer note ─── */
  .rv-footer-note {
    text-align: center;
    font-size: 12px;
    color: var(--color-on-surface-variant, #64748b);
    margin-top: 24px;
    line-height: 1.8;
  }
  .rv-footer-note a { color: #2563eb; text-decoration: none; }
  .rv-footer-note a:hover { text-decoration: underline; }

  /* ─── Divider ─── */
  .rv-divider {
    border: none;
    border-top: 1px solid var(--color-outline-variant, #e2e8f0);
    margin: 24px 0;
  }

  /* ─── Course chip ─── */
  .rv-course-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--color-primary-container, #eff6ff);
    color: var(--color-on-primary-container, #1d4ed8);
    border-radius: 999px;
    padding: 6px 16px 6px 10px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 24px;
  }
  .rv-course-chip .material-symbols-outlined { font-size: 16px; }

  @media (max-width: 480px) {
    .review-card { padding: 24px 18px; }
    .star-row label { font-size: 32px; }
  }
</style>
@endpush

@section('content')

<div class="review-hero">
  <div class="review-hero-content">
    <div class="review-hero-badge">⭐ Share Your Experience</div>
    <h1>How was <em>{{ $course->title }}</em>?</h1>
    <p>Your honest feedback helps other parents find the best learning experience for their child.</p>
  </div>
</div>

<div class="review-page-wrap">
  <div class="review-card">

    @if(session('success'))
      <div class="review-success">
        <div class="icon">🎉</div>
        <div>
          <strong>Thank you! Your review is live.</strong>
          <span>{{ session('success') }}</span>
        </div>
      </div>
    @endif

    @if($errors->any())
      <div class="review-error">
        <div class="icon">⚠️</div>
        <div>
          <strong>Please fix the following:</strong>
          <span>{{ $errors->first() }}</span>
        </div>
      </div>
    @endif

    <div class="rv-course-chip">
      <span class="material-symbols-outlined">school</span>
      Reviewing: {{ $course->title }}
    </div>

    <form method="POST" action="{{ route('courses.review.store', $course) }}">
      @csrf

      <div class="rv-form-group">
        <label class="rv-label" for="reviewer_name">Your Name <span class="req">*</span></label>
        <input
          type="text"
          id="reviewer_name"
          name="reviewer_name"
          value="{{ old('reviewer_name') }}"
          placeholder="E.g. Sarah (Parent of Arjun)"
          class="rv-input {{ $errors->has('reviewer_name') ? 'is-invalid' : '' }}"
          required
        >
        @error('reviewer_name')
          <p class="rv-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="rv-form-group">
        <span class="star-group-label">Your Rating <span class="req">*</span></span>
        <div class="star-row" id="starRow">
          @for($i = 5; $i >= 1; $i--)
            <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}" {{ old('rating', 5) == $i ? 'checked' : '' }} required>
            <label for="star{{ $i }}" title="{{ $i }} star{{ $i > 1 ? 's' : '' }}">★</label>
          @endfor
        </div>
        <p class="star-caption" id="starCaption">⭐ Excellent – 5 stars</p>
        @error('rating')
          <p class="rv-error">{{ $message }}</p>
        @enderror
      </div>

      <hr class="rv-divider">

      <div class="rv-form-group">
        <label class="rv-label" for="comment">Your Review <span class="req">*</span></label>
        <textarea
          id="comment"
          name="comment"
          placeholder="What did your child enjoy most? How has the course impacted them? Any tips for other parents?..."
          class="rv-textarea {{ $errors->has('comment') ? 'is-invalid' : '' }}"
          required
        >{{ old('comment') }}</textarea>
        <p class="rv-hint">Minimum 10 characters. Be specific — your words help other families make the right choice!</p>
        @error('comment')
          <p class="rv-error">{{ $message }}</p>
        @enderror
      </div>

      <button type="submit" class="rv-submit">
        <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
        Submit My Review
      </button>
    </form>

    <p class="rv-footer-note">
      By submitting, you agree to our <a href="{{ url('/page/terms-of-service') }}">Terms of Service</a>.<br>
      Your review will be publicly visible on the <a href="{{ route('courses.show', $course) }}">course page</a>.
    </p>
  </div>
</div>

@endsection

@push('scripts')
<script>
  const starCaptions = {
    1: '😞 Poor – 1 star',
    2: '😐 Fair – 2 stars',
    3: '🙂 Good – 3 stars',
    4: '😊 Great – 4 stars',
    5: '⭐ Excellent – 5 stars',
  };

  document.querySelectorAll('#starRow input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.getElementById('starCaption').textContent = starCaptions[radio.value] || '';
    });
  });

  const checkedStar = document.querySelector('#starRow input[type="radio"]:checked');
  if (checkedStar) {
    document.getElementById('starCaption').textContent = starCaptions[checkedStar.value] || '';
  }
</script>
@endpush
