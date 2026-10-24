@php
    use App\Support\Legal;
    $secciones = Legal::sections($ns);
    $sello = $ns === 'esnna' ? Legal::esnnaImage() : null;
    $email = config('legal.email');
@endphp
<section class="page-head legal-head">
    <div class="container">
        <p class="eyebrow">{{ Legal::t($ns, 'eyebrow') }}</p>
        <h1>{{ Legal::t($ns, 'title') }}</h1>
        <p class="lead-dark">{{ Legal::t($ns, 'intro') }}</p>
        <div class="legal-actions">
            <span class="legal-meta">{{ Legal::t($ns, 'updated') }}: {{ Legal::date() }}</span>
            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">⎙ {{ Legal::t($ns, 'print') }}</button>
        </div>
    </div>
</section>

<section class="legal-section">
    <div class="container legal-grid">
        <nav class="legal-toc" aria-label="{{ Legal::t($ns, 'toc') }}">
            <p>{{ Legal::t($ns, 'toc') }}</p>
            <ol>
                @foreach($secciones as $i => $s)
                    <li><a href="#s{{ $i + 1 }}" data-toc>{{ $s['t'] }}</a></li>
                @endforeach
            </ol>
        </nav>

        <div class="legal-body legal-card">
            @if($sello)
                <img class="legal-seal" src="{{ $sello }}" alt="{{ Legal::t($ns, 'title') }}" width="140" loading="lazy">
            @endif

            @foreach($secciones as $i => $s)
                <section id="s{{ $i + 1 }}">
                    <h2><span class="legal-num">{{ $i + 1 }}</span> {{ $s['t'] }}</h2>
                    @foreach($s['p'] as $par)<p>{!! nl2br(e($par)) !!}</p>@endforeach
                    @if($s['l'])<ul>@foreach($s['l'] as $li)<li>{{ $li }}</li>@endforeach</ul>@endif
                    @foreach($s['p2'] as $par)<p>{!! nl2br(e($par)) !!}</p>@endforeach
                </section>
            @endforeach

            <aside class="legal-contact">
                <h2>{{ Legal::t($ns, 'contact_t') }}</h2>
                <p>{{ Legal::t($ns, 'contact_p') }}</p>
                <div class="legal-contact-actions">
                    <a class="btn btn-grad btn-sm" href="https://wa.me/{{ config('mayo.whatsapp') }}" target="_blank" rel="noopener">{{ Legal::t($ns, 'contact_btn') }}</a>
                    @if($email)<a class="btn btn-glass btn-sm" href="mailto:{{ $email }}">{{ $email }}</a>@endif
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
(function () {
    var links = [].slice.call(document.querySelectorAll('[data-toc]'));
    if (!links.length || !('IntersectionObserver' in window)) { return; }
    var map = {};
    links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) {
                links.forEach(function (a) { a.classList.remove('on'); });
                if (map[e.target.id]) { map[e.target.id].classList.add('on'); }
            }
        });
    }, { rootMargin: '-20% 0px -70% 0px' });
    Object.keys(map).forEach(function (id) { var el = document.getElementById(id); if (el) { io.observe(el); } });
})();
</script>
