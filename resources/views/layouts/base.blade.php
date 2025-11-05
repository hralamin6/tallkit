<!DOCTYPE html>
<html
{{--    data-theme="light"--}}
    data-theme="dark"
      lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>

      @PwaHead
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">
      <meta property="og:url" content="@yield('url', config('app.url'))" />
      <meta property="og:site_name" content="{{ setting('name', 'starter') }}" />

      @php($title=Str::title(str_replace(['.', '_'], ' ',  request()->route()->getName())))
      <title>@yield('title', $title) - {{ setting('name', 'starter') }}</title>
      <meta property="og:title" content="@yield('title', $title) - {{ setting('name', 'starter') }}" />
      <meta name="twitter:title" content="@yield('title', $title) - {{ setting('name', 'starter') }}" />

      <meta name="description" content="@yield('description', setting('details', 'dummy description') ) - {{ setting('name', 'starter') }}">
      <meta property="og:description" content="@yield('description', setting('details', 'dummy description') ) - {{ setting('name', 'starter') }}" />
      <meta name="twitter:description" content="@yield('description', setting('details', 'dummy description') ) - {{ setting('name', 'starter') }}" />

      <meta property="og:image:width" content="1536" />
      <meta property="og:image:height" content="1024" />
      <meta name="twitter:card" content="summary" />
      <link rel="shortcut icon" href="@yield('image', getSettingImage('iconImage'))">
      <meta property="og:image" content="@yield('image', getSettingImage('iconImage'))" />
      <meta property="og:image:secure_url" content="@yield('image', getSettingImage('iconImage'))" />
      <meta name="twitter:image" content="@yield('image', getSettingImage('iconImage'))" />

      {{-- Chart.js for Activity Dashboard --}}
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @livewireScripts

        <!-- CropperJS for Mary File cropper -->
{{--        <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.css">--}}
{{--        <script defer src="https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.js"></script>--}}
{{--      <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.1/Sortable.min.js"></script>--}}
{{--      <script src="https://cdn.tiny.cloud/1/mbka6lqu2y9tf2q7tvoe1clyhs6oxwsct5ma91a7re40y5ms/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>--}}
    </head>

    <body>
        @yield('body')
        <x-toast />
        @RegisterServiceWorkerScript
    </body>
</html>
