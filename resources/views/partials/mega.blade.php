{{-- $groups: lista de ['nombre' => ..., 'url' => ..., 'items' => Collection<Tour>] --}}
<li class="mega-item" data-mega>
    <button type="button" class="nav-btn" data-mega-btn aria-expanded="false" aria-controls="{{ $id }}">
        {{ $label }} @if(! empty($em))<em>{{ $em }}</em>@endif <span class="chev" aria-hidden="true"></span>
    </button>

    <div class="mega" id="{{ $id }}">
        <div class="mega-left" @style(['--bg: url(' . ($bg ?? '') . ')' => ! empty($bg)])>
            <p class="kicker">{{ $kicker }}</p>
            <h3>{{ $title }}</h3>
            <a class="mega-go" href="{{ route('tours') }}"><span aria-hidden="true">→</span> {{ __('header.discover') }}</a>
        </div>

        <div class="mega-sel">
            <p class="kicker-dim">{{ __('header.selection') }}</p>
            <ul>
                @foreach($groups as $g)
                    <li>
                        <a href="{{ $g['url'] }}" data-sel="{{ $loop->index }}" @class(['on' => $loop->first])>
                            {{ $g['nombre'] }} <span aria-hidden="true">›</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mega-right">
            @foreach($groups as $g)
                @php $items = $g['items']; @endphp
                <div data-pane="{{ $loop->index }}" @if(! $loop->first) hidden @endif>
                    <div class="mr-head">
                        <div>
                            <p class="kicker-gold">{{ __('header.curated') }}</p>
                            <h4>{{ $g['nombre'] }}</h4>
                        </div>
                        <span class="mr-count">{{ str_pad($items->count(), 2, '0', STR_PAD_LEFT) }}<small>{{ __('header.options') }}</small></span>
                    </div>
                    @if($items->isEmpty())
                        <p class="mr-empty">{{ __('header.soon') }}</p>
                    @else
                        <div class="mr-grid">
                            @foreach($items->take($limit ?? 6) as $t)
                                <a class="mr-card" href="{{ route('tour.detalle', $t->slug) }}">
                                    @if($t->imagen_url)<img src="{{ $t->imagen_url }}" alt="" loading="lazy" onerror="this.remove()">@endif
                                    <span>{{ \Illuminate\Support\Str::limit($t->nombre, 34) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="mr-foot">
                <span>{{ __('header.foot_note') }}</span>
                <a href="{{ route('tours') }}">{{ __('header.catalog') }} →</a>
            </div>
        </div>
    </div>
</li>
