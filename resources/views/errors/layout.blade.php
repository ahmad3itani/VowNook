<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — VowNook</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 24px;
            text-align: center;
            font-family: ui-serif, Georgia, 'Times New Roman', serif;
            background: linear-gradient(180deg, #fff1f2 0%, #ffffff 50%, #fff1f2 100%);
            color: #292524;
        }
        .heart { font-size: 28px; color: #fb7185; }
        .code { font-size: 72px; font-weight: 600; line-height: 1; margin: 12px 0 0; color: #e11d48; }
        h1 { font-size: 28px; margin: 8px 0 0; }
        p { font-family: ui-sans-serif, system-ui, sans-serif; color: #78716c; max-width: 28rem; margin: 12px 0 0; }
        a.button {
            font-family: ui-sans-serif, system-ui, sans-serif;
            display: inline-block;
            margin-top: 24px;
            padding: 12px 28px;
            border-radius: 9999px;
            background: #f43f5e;
            color: #fff;
            text-decoration: none;
            font-weight: 500;
        }
        a.button:hover { background: #e11d48; }
        footer { font-family: ui-sans-serif, system-ui, sans-serif; margin-top: 40px; color: #a8a29e; font-size: 12px; }
        @media (prefers-color-scheme: dark) {
            body { background: linear-gradient(180deg, #0c0a09 0%, #1c1917 50%, #0c0a09 100%); color: #f5f5f4; }
            p { color: #a8a29e; }
        }
    </style>
</head>
<body>
    <div class="heart">&#9829;</div>
    <div class="code">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a class="button" href="{{ url('/') }}">Back to home</a>
    <footer>VowNook</footer>
</body>
</html>
