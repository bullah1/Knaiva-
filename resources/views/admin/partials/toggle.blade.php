{{-- A one-click on/off pill that posts to $action with optional hidden $field. --}}
<form method="post" action="{{ $action }}" class="inline">
    @csrf
    @if (!empty($method)) @method($method) @endif
    @if (!empty($field))<input type="hidden" name="field" value="{{ $field }}">@endif
    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold {{ $on ? 'border-success/40 bg-success/10 text-success' : 'border-border text-muted-foreground' }}">
        <span class="size-2 rounded-full {{ $on ? 'bg-success' : 'bg-border' }}"></span>{{ $label }}
    </button>
</form>
