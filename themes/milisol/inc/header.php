<!-- ==============================================
        Favicons
        =============================================== -->
<link rel="shortcut icon" href="<?= INCLUDE_PATH ?>/assets/images/favicon.ico">
<link rel="apple-touch-icon" href="<?= INCLUDE_PATH ?>/assets/images/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?= INCLUDE_PATH ?>/assets/images/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?= INCLUDE_PATH ?>/assets/images/apple-touch-icon-114x114.png">
<link href="<?= INCLUDE_PATH ?>/assets/css/all.css" rel="stylesheet">

<!-- ==============================================
        Vendor Stylesheet
        =============================================== -->
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/vendor/slider.min.css">
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/main.css">
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/vendor/icons.min.css">
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/vendor/icons-fa.min.css">
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/vendor/animation.min.css">
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/vendor/gallery.min.css">
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/vendor/cookie-notice.min.css">

<!-- ==============================================
        Custom Stylesheet
        =============================================== -->
<link rel="stylesheet" href="<?= INCLUDE_PATH ?>/assets/css/default.css">

<!-- ==============================================
        Theme Color
        =============================================== -->
<meta name="theme-color" content="#21333e">

<!-- ==============================================
        Theme Settings
        =============================================== -->
<style>
    :root {
        --hero-bg-color: #000007;

        --section-1-bg-color: #eef4ed;
        --section-2-bg-color: #ffffff;
        --section-3-bg-color: #111117;
        --section-4-bg-color: #eef4ed;
        --section-5-bg-color: #ffffff;
        --section-6-bg-color: #111117;
        --section-6-bg-image: url('<?= INCLUDE_PATH ?>/assets/images/bg-1.jpg');
        --section-7-bg-color: #ffffff;
    }
</style>

</head>

<!-- Header -->
<header id="header">

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand top">
        <div class="container header" style="text-color: white;">

            <!-- Navbar Items [left] -->
            <!-- <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link pl-0"><i class="fas fa-clock mr-2"></i>Respondemos o mais rápido possível</a>
                </li>
            </ul> -->

            <!-- Nav holder -->
            <div class="ml-auto"></div>

            <!-- Navbar Items [right] -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="https://api.whatsapp.com/send?phone=21971191779" target="_blank" class="nav-link"><i class="fas fa-phone-alt mr-2"></i>(21) 97119-1779</a>
                </li>
                <li class="nav-item">
                    <a href="mailto:contato@milisol.com.br" class="nav-link"><i class="fas fa-envelope mr-2"></i>contato@milisol.com.br</a>
                </li>
            </ul>

            <!-- Navbar Icons -->
            <!-- <ul class="navbar-nav icons">
                <li class="nav-item social">
                    <a href="/" class="nav-link"><i class="fab fa-facebook-f"></i></a>
                </li>
                <li class="nav-item social">
                    <a href="/" class="nav-link"><i class="fab fa-twitter"></i></a>
                </li>
                <li class="nav-item social">
                    <a href="/" class="nav-link pr-0"><i class="fab fa-linkedin-in"></i></a>
                </li>
            </ul> -->

        </div>
    </nav>

    <!-- Navbar -->
    <nav class="navbar navbar-expand navbar-fixed sub">
        <div class="container header">

            <!-- Navbar Brand-->
            <a class="navbar-brand" href="<?= BASE ?>/">
                <span class="brand">
                    <span class="featured">
                        <span class="first">MILI</span>
                    </span>
                    <span class="last">SOL</span>
                </span>
        
            </a>

            <!-- Nav holder -->
            <div class="ml-auto"></div>

            <!-- Navbar Items -->
            <ul class="navbar-nav items">
                <li class="nav-item">
                    <a href="#hero" class="nav-link">HOME</a>
                </li>
                <li class="nav-item">
                    <a href="#about" class="nav-link">SOBRE</a>
                </li>
                <li class="nav-item">
                    <a href="#process" class="nav-link">PROCESSO</a>
                </li>
            </ul>

            <!-- Navbar Toggle -->
            <ul class="navbar-nav toggle">
                <li class="nav-item">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link" data-toggle="modal" data-target="#menu">
                    <i class="fas fa-bars m-0"></i>
                    </a>
                </li>
            </ul>

            <!-- Navbar Action -->
            <ul class="navbar-nav action">
                <li class="nav-item ml-3">
                    <a href="https://api.whatsapp.com/send?phone=21971191779" target="_blank" class="btn ml-lg-auto primary-button">Entre em Contato</a>
                </li>
            </ul>
        </div>
    </nav>

</header>