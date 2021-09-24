<!-- ==============================================
        Favicons
        =============================================== -->
<link rel="shortcut icon" href="<?= INCLUDE_PATH ?>/assets/images/favicon.ico">
<link rel="apple-touch-icon" href="<?= INCLUDE_PATH ?>/assets/images/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?= INCLUDE_PATH ?>/assets/images/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?= INCLUDE_PATH ?>/assets/images/apple-touch-icon-114x114.png">

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
        <div class="container header">

            <!-- Navbar Items [left] -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link pl-0"><i class="fas fa-clock mr-2"></i>Open Hours: Mon - Sat - 9:00 - 18:00</a>
                </li>
            </ul>

            <!-- Nav holder -->
            <div class="ml-auto"></div>

            <!-- Navbar Items [right] -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link"><i class="fas fa-phone-alt mr-2"></i>+1 (305) 1234-5678</a>
                </li>
                <li class="nav-item">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link"><i class="fas fa-envelope mr-2"></i>contatomilisol@gmail.com.br</a>
                </li>
            </ul>

            <!-- Navbar Icons -->
            <ul class="navbar-nav icons">
                <li class="nav-item social">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link"><i class="fab fa-facebook-f"></i></a>
                </li>
                <li class="nav-item social">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link"><i class="fab fa-twitter"></i></a>
                </li>
                <li class="nav-item social">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link pr-0"><i class="fab fa-linkedin-in"></i></a>
                </li>
            </ul>

        </div>
    </nav>

    <!-- Navbar -->
    <nav class="navbar navbar-expand navbar-fixed sub">
        <div class="container header">

            <!-- Navbar Brand-->
            <a class="navbar-brand" href="<?= INCLUDE_PATH ?>//">
                <span class="brand">
                    <span class="featured">
                        <span class="first">MILI</span>
                    </span>
                    <span class="last">SOL</span>
                </span>

                <!-- 
                Custom Logo
                <img src="<?= INCLUDE_PATH ?>/assets/images/logo.svg" alt="NEXGEN">
            -->
            </a>

            <!-- Nav holder -->
            <div class="ml-auto"></div>

            <!-- Navbar Items -->
            <ul class="navbar-nav items">
                <li class="nav-item">
                    <a href="<?= BASE ?>/#header" class="smooth-anchor nav-link">HOME</a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE ?>/#about" class="smooth-anchor nav-link">ABOUT</a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE ?>/#services" class="smooth-anchor nav-link">SERVICES</a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE ?>/#pricing" class="smooth-anchor nav-link">PRICING</a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE ?>/#blog" class="smooth-anchor nav-link">BLOG</a>
                </li>
            </ul>

            <!-- Navbar Toggle -->
            <ul class="navbar-nav toggle">
                <li class="nav-item">
                    <a href="<?= INCLUDE_PATH ?>/" class="nav-link" data-toggle="modal" data-target="#menu">
                        <i class="icon-menu m-0"></i>
                    </a>
                </li>
            </ul>

            <!-- Navbar Action -->
            <ul class="navbar-nav action">
                <li class="nav-item ml-3">
                    <a href="<?= BASE ?>/#contact" class="smooth-anchor btn ml-lg-auto primary-button">GET IN TOUCH</a>
                </li>
            </ul>
        </div>
    </nav>

</header>