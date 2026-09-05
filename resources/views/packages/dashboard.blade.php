{{--
    Minimaler Blade-Wrapper für die Composer-/npm-Paketübersicht.
    In ein bestehendes Layout einbinden (z.B. @extends('layouts.app')),
    hier standalone gehalten, damit der Vite-Push sichtbar bleibt.
--}}
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Composer- &amp; npm-Pakete</title>
    @vite(['resources/css/dashboard.css', 'resources/js/dashboard-app.js'])
</head>
<body>
    <div id="package-dashboard"></div>
</body>
</html>
