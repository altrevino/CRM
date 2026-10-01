<!DOCTYPE html>
<html lang="es-MX" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full">
<div class="flex min-h-full">
    <div class="relative hidden flex-1 overflow-hidden bg-gradient-to-br from-brand-700 via-brand-800 to-slate-900 lg:block">
        <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 30% 40%, rgba(251,191,36,.55), transparent 35%), radial-gradient(circle at 70% 70%, rgba(244,63,94,.35), transparent 30%);"></div>
        <div class="relative flex h-full flex-col justify-end p-12 text-white">
            <p class="text-sm font-medium tracking-widest text-brand-200 uppercase">Espectro Soluciones</p>
            <h1 class="mt-3 max-w-md text-3xl font-semibold leading-tight">Censos térmicos de fauna, organizados de principio a fin.</h1>
            <p class="mt-4 max-w-md text-brand-100/80">Prospectos, ranchos, cotizaciones, pagos y censos programados en un solo lugar.</p>
        </div>
    </div>
    <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:flex-none lg:w-[480px]">
        <div class="mx-auto w-full max-w-sm">
            <div class="flex items-center gap-2.5">
                <img src="{{ asset('favicon.svg') }}" alt="" class="size-10">
                <div class="leading-tight">
                    <p class="font-semibold text-slate-900">Espectro CRM</p>
                    <p class="text-sm text-slate-500">Inicia sesión para continuar</p>
                </div>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="mt-10 space-y-5">
                @csrf
                <div>
                    <label for="email" class="label">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="input @error('email') input-error @enderror">
                    @error('email')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="label">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
                    @error('password')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Mantener sesión iniciada
                </label>
                <button type="submit" class="btn-primary w-full py-2.5">Entrar</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
