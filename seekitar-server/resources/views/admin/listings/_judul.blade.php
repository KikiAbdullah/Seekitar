@php
  $images = is_array($listing->images) ? $listing->images : json_decode($listing->images, true);
  $cover = !empty($images) ? asset('storage/' . $images[0]) : 'https://placehold.co/100x100?text=Listing';
@endphp
<div class="d-flex align-items-center gap-2">
  <img src="{{ $cover }}" class="rounded" width="40" height="40" style="object-fit: cover;" onerror="this.onerror=null; this.src='https://placehold.co/100x100?text=Listing'">
  <span class="fw-semibold text-dark">{{ $listing->title }}</span>
</div>
