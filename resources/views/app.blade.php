<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dummy ION App</title>
    @vite(['resources/js/main.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
