<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }} - Bienvenida</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Monaco', sans-serif;
        }
        .hero-section {
            background: url('https://images.pexels.com/photos/7794423/pexels-photo-7794423.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1') no-repeat center center;
            background-size: cover;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: white;
            padding-top: 70px; /* Added to prevent overlap with fixed navbar */
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 20px;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .hero-subtitle {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 2rem;
        }
        .btn-custom {
            padding: 10px 25px;
            font-size: 1.1rem;
            text-transform: uppercase;
            margin: 0 10px;
        }
        .navbar-custom {
            background-color: rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark fixed-top navbar-custom">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ asset('vendor/adminlte/dist/img/logo_empresa.png') }}" alt="{{ config('app.name', 'Laravel') }}" height="60">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto">
                        @if (Route::has('login'))
                            @auth
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ url('/home') }}">Home</a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">Iniciar Sesión</a>
                                </li>
                                @if (Route::has('register'))
                                    <!--<li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}">Registrarse</a>
                                    </li>-->
                                @endif
                            @endauth
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            <section class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">Ingeniería en Precisión Geodésica</h1>
                    <p class="hero-subtitle">Transformando el futuro de las ciudades con tecnología y precisión.</p>
                    <div>
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/home') }}" class="btn btn-primary btn-custom">Ir al Panel</a>
                            @else
                            @endauth
                        @endif

                        <a href="http://localhost/projectcatastro/public/consulta" class="btn btn-outline-light btn-custom">
                            Consultar Trámite
                        </a>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


