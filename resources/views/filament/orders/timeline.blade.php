@php($order = $getRecord())
<ol class="ob-timeline">
  @forelse($order->events as $event)
    <li>
      <time>{{ $event->created_at->translatedFormat('j M Y H:i') }}</time>
      <span>{{ $event->message }}</span>
      @if($event->user_id && $event->user)<span class="ob-muted">door {{ $event->user->name }}</span>@endif
    </li>
  @empty
    <li class="ob-muted">Nog geen gebeurtenissen.</li>
  @endforelse
</ol>
