@props(['rating' => 0, 'size' => 'size-3.5'])
<span class="inline-flex items-center gap-0.5" role="img" aria-label="{{ $rating }} out of 5">
    @for ($i = 1; $i <= 5; $i++)
        {!! icon('star', $size.' '.($i <= round($rating) ? 'fill-primary text-primary' : 'text-border')) !!}
    @endfor
</span>
