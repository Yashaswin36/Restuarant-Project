<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'TableFare') ?> — TableFare</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
// TableFare's palette is applied by *redefining* the Tailwind scales the views already
// use, rather than bolting extra classes onto every template. amber = tandoor orange and
// saffron, stone = a warm brick-brown ink (not grey), red = chili, green = the FSSAI leaf
// green. Everything stays in the amber/red family this project is assigned.
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans:    ['Archivo', 'Segoe UI', 'system-ui', 'sans-serif'],
                display: ['"Bricolage Grotesque"', 'Archivo', 'Segoe UI', 'sans-serif'],
            },
            colors: {
                amber: {
                    50:  '#FDF4EC', 100: '#F6E3D2', 200: '#EECDAE', 300: '#E5B37F',
                    400: '#DC9448', 500: '#CE7423', 600: '#B4501A', 700: '#933B15',
                    800: '#6F2C12', 900: '#4B1E0E',
                },
                stone: {
                    50:  '#FBF6F1', 100: '#F2E9E1', 200: '#E2D4C8', 300: '#C7B3A4',
                    400: '#A08A7B', 500: '#7C6559', 600: '#5E4940', 700: '#45332C',
                    800: '#2F211C', 900: '#1E1310',
                },
                red: {
                    50:  '#FDF0EE', 100: '#FADDD8', 200: '#F3BCB3', 300: '#E68F82',
                    400: '#D45F4F', 500: '#BC3A28', 600: '#A2271A', 700: '#851C12',
                    800: '#63150E', 900: '#43100A',
                },
                green: {
                    50:  '#EEF6F0', 100: '#D6EADC', 200: '#AED6BC', 300: '#7CBB96',
                    400: '#4E9F73', 500: '#2F7D5B', 600: '#25664A', 700: '#1D503B',
                    800: '#163C2D', 900: '#0F2A20',
                },
            },
        },
    },
};
</script>
<style>
    :root { color-scheme: light; }

    /* The ground is warm paper punched with a faint perforation — the dot pattern
       pressed into a steel tiffin lid. It is the one ambient flourish in the app. */
    body {
        background-color: #FDF4EC;
        background-image: radial-gradient(rgba(180, 80, 26, 0.14) 1px, transparent 1px);
        background-size: 26px 26px;
        background-position: -1px -1px;
    }

    h1, h2, h3, .font-display {
        font-family: 'Bricolage Grotesque', Archivo, 'Segoe UI', sans-serif;
        letter-spacing: -0.018em;
    }
    h1 { font-weight: 800; }
    h2, h3 { font-weight: 700; }

    /* Prices, quantities and table numbers line up in columns across the app. */
    .tnum, table td, table th { font-variant-numeric: tabular-nums; }

    /* Small caps label used above page titles and section heads. */
    .eyebrow {
        font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.14em;
        text-transform: uppercase; color: #A08A7B;
    }

    :focus-visible { outline: 2px solid #B4501A; outline-offset: 2px; border-radius: 4px; }

    /* The FSSAI mark every Indian menu carries: a filled circle inside a square outline,
       green for vegetarian, dark red for non-vegetarian. It replaces the coloured-circle
       emoji, which rendered differently on every OS and read as decoration rather than
       the regulated food mark that it is. */
    .fmark {
        display: inline-flex; align-items: center; justify-content: center;
        width: 0.95em; height: 0.95em; flex: none;
        border: 1.5px solid currentColor; border-radius: 2px;
        vertical-align: -0.1em;
    }
    .fmark::before { content: ''; width: 0.45em; height: 0.45em; border-radius: 50%; background: currentColor; }
    .fmark-veg { color: #2F7D5B; }
    .fmark-nonveg { color: #A2271A; }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>
</head>
<body class="min-h-screen text-stone-800 flex flex-col antialiased">
