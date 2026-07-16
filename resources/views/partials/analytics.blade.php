{{-- Search-engine verification tags (render whenever set; harmless, no scripts). --}}
@if($v = config('analytics.google_site_verification'))
    <meta name="google-site-verification" content="{{ $v }}">
@endif
@if($v = config('analytics.bing_site_verification'))
    <meta name="msvalidate.01" content="{{ $v }}">
@endif

@php
    $gaId = config('analytics.ga_id');
    $clarityId = config('analytics.clarity_id');
    $metaPixelId = config('analytics.meta_pixel_id');
    $analyticsActive = ($gaId || $clarityId || $metaPixelId) && (app()->environment('production') || config('analytics.force'));
@endphp

@if($analyticsActive)
    {{-- Google Consent Mode v2: deny analytics/ads storage until the visitor accepts. --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
        });
        (function () {
            try {
                if (localStorage.getItem('vn_consent') === 'granted') {
                    gtag('consent', 'update', { analytics_storage: 'granted' });
                }
            } catch (e) {}
        })();
    </script>

    @if($gaId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}', { anonymize_ip: true });
        </script>
    @endif

    @if($clarityId)
        <script>
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "{{ $clarityId }}");
        </script>
    @endif

    @if($metaPixelId)
        {{-- Meta Pixel — loads with consent REVOKED (CASL); granted only after Accept. --}}
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                document,'script','https://connect.facebook.net/en_US/fbevents.js');
            fbq('consent', 'revoke');
            fbq('init', '{{ $metaPixelId }}');
            (function(){
                var granted = false;
                try { granted = localStorage.getItem('vn_consent') === 'granted'; } catch (e) {}
                if (granted) { fbq('consent', 'grant'); }
                fbq('track', 'PageView');
            })();
        </script>
        <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"/></noscript>
    @endif

    {{-- Minimal, branded cookie-consent banner. Vanilla JS so it works on every
         page (public + app) without touching the Inertia React tree. --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            try { if (localStorage.getItem('vn_consent')) return; } catch (e) { return; }
            var bar = document.createElement('div');
            bar.setAttribute('role', 'dialog');
            bar.setAttribute('aria-label', 'Cookie consent');
            bar.style.cssText = 'position:fixed;z-index:2147483647;left:1rem;right:1rem;bottom:1rem;max-width:560px;margin:0 auto;background:#1e1b17;color:#faf6ef;border-radius:12px;padding:16px 18px;font-family:DM Sans,system-ui,sans-serif;font-size:13px;line-height:1.5;box-shadow:0 18px 40px -12px rgba(0,0,0,.45);display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between';
            bar.innerHTML = '<span style="flex:1;min-width:200px">We use privacy-friendly analytics to improve VowNook. <a href="/privacy" style="color:#e9c176;text-decoration:underline">Privacy</a>.</span>';
            function choose(v){ try{localStorage.setItem('vn_consent', v);}catch(e){} if(v==='granted'){ gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'}); if(window.fbq){ fbq('consent','grant'); fbq('track','PageView'); } } bar.remove(); }
            var wrap = document.createElement('span'); wrap.style.cssText='display:flex;gap:8px;flex-shrink:0';
            var decline = document.createElement('button'); decline.textContent='Decline';
            decline.style.cssText='background:transparent;border:1px solid rgba(250,246,239,.35);color:#faf6ef;border-radius:8px;padding:7px 14px;font-size:12px;cursor:pointer';
            decline.onclick=function(){choose('denied');};
            var accept = document.createElement('button'); accept.textContent='Accept';
            accept.style.cssText='background:#e9c176;border:0;color:#1e1b17;border-radius:8px;padding:7px 16px;font-size:12px;font-weight:600;cursor:pointer';
            accept.onclick=function(){choose('granted');};
            wrap.appendChild(decline); wrap.appendChild(accept); bar.appendChild(wrap);
            document.body.appendChild(bar);
        });
    </script>
@endif
