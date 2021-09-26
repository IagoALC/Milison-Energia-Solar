<!-- Footer -->
<footer>

    <!-- Footer [content] -->
    <section id="footer" class="odd footer offers">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-3 footer-left">

                    <!-- Navbar Brand-->
                    <a class="navbar-brand" href="<?= INCLUDE_PATH ?>/">
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
                    <p>Energia do sol na sua vida</p>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a href="https://api.whatsapp.com/send?phone=21971191779" target="_blank" class="nav-link">
                                <i class="fas fa-phone-alt mr-2"></i>
                                (21) 97119-1779
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="mailto:" class="nav-link">
                                <i class="fas fa-envelope mr-2"></i>
                                contato.milisol@gmail.com
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="https://api.whatsapp.com/send?phone=21971191779" target="_blank" class="mt-4 btn outline-button">Entre em Contato</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-lg-9 p-0 footer-right" style="display: flex; flex-direction: column; justify-content:end;">
                    <div class="row items">
                        <div class="col-12 col-lg-4 item">
                            <div class="card">
                                <h4>Site</h4>
                                <a href="<?= BASE ?>/"><i class="fas fa-arrow-right"></i>Home</a>
                                <a href="<?= BASE ?>/#about"><i class="fas fa-arrow-right"></i>Sobre</a>
                                <a href="<?= BASE ?>/#process"><i class="fas fa-arrow-right"></i>Processo</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Copyright -->
    <section id="copyright" class="p-3 odd copyright">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 p-3 text-center text-lg-left">
                </div>
                <div class="col-12 col-md-6 p-3 text-center text-lg-right">
                    <p>© <?= date("Y") ?> Todos os Direitos Reservados a <a href="" target="_blank">Milisol</a>.</p>
                </div>
            </div>
        </div>
    </section>

</footer>

<!-- Modal [search] -->
<div id="search" class="p-0 modal fade" role="dialog" aria-labelledby="search" aria-hidden="true">
    <div class="modal-dialog modal-dialog-slideout" role="document">
        <div class="modal-content full">
            <div class="modal-header" data-dismiss="modal">
                <i class="icon-close fas fa-arrow-right"></i>
            </div>
            <div class="modal-body">
                <form class="row">
                    <div class="col-12 p-0 align-self-center">
                        <div class="row">
                            <div class="col-12 p-0">
                                <h2>What are you looking for?</h2>
                                <div class="badges">
                                    <span class="badge"><a href="<?= INCLUDE_PATH ?>/">Consulting</a></span>
                                    <span class="badge"><a href="<?= INCLUDE_PATH ?>/">Audit</a></span>
                                    <span class="badge"><a href="<?= INCLUDE_PATH ?>/">Assurance</a></span>
                                    <span class="badge"><a href="<?= INCLUDE_PATH ?>/">Advisory</a></span>
                                    <span class="badge"><a href="<?= INCLUDE_PATH ?>/">Financial</a></span>
                                    <span class="badge"><a href="<?= INCLUDE_PATH ?>/">Capital Markets</a></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 p-0 input-group">
                                <input type="text" class="form-control" placeholder="Enter Keywords">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 p-0 input-group align-self-center">
                                <button class="btn primary-button">SEARCH</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal [sign] -->
<div id="sign" class="p-0 modal fade" role="dialog" aria-labelledby="sign" aria-hidden="true">
    <div class="modal-dialog modal-dialog-slideout" role="document">
        <div class="modal-content full">
            <div class="modal-header" data-dismiss="modal">
                <i class="icon-close fas fa-arrow-right"></i>
            </div>
            <div class="modal-body">
                <form action="/" class="row">
                    <div class="col-12 p-0 align-self-center">
                        <div class="row">
                            <div class="col-12 p-0 pb-3">
                                <h2>Sign In</h2>
                                <p>Don't have an account? <a href="<?= INCLUDE_PATH ?>/" class="primary-color" data-toggle="modal" data-target="#register">Register now</a>.</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 p-0 input-group">
                                <input type="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="col-12 p-0 input-group">
                                <input type="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 p-0 input-group align-self-center">
                                <button class="btn primary-button">SIGN IN</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal [register] -->
<div id="register" class="p-0 modal fade" role="dialog" aria-labelledby="register" aria-hidden="true">
    <div class="modal-dialog modal-dialog-slideout" role="document">
        <div class="modal-content full">
            <div class="modal-header" data-dismiss="modal">
                <i class="icon-close fas fa-arrow-right"></i>
            </div>
            <div class="modal-body">
                <form action="/" class="row">
                    <div class="col-12 p-0 align-self-center">
                        <div class="row">
                            <div class="col-12 p-0 pb-3">
                                <h2>Register</h2>
                                <p>Have an account? <a href="<?= INCLUDE_PATH ?>/" class="primary-color" data-toggle="modal" data-target="#sign">Sign In</a>.</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 p-0 input-group">
                                <input type="text" class="form-control" placeholder="Name" required>
                            </div>
                            <div class="col-12 p-0 input-group">
                                <input type="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="col-12 p-0 input-group">
                                <input type="password" class="form-control" placeholder="Password" required>
                            </div>
                            <div class="col-12 p-0 input-group">
                                <input type="password" class="form-control" placeholder="Confirm Password" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 p-0 input-group align-self-center">
                                <button class="btn primary-button">REGISTER</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal [map] -->
<div id="map" class="p-0 modal fade" role="dialog" aria-labelledby="map" aria-hidden="true">
    <div class="modal-dialog modal-dialog-slideout" role="document">
        <div class="modal-content full">
            <div class="modal-header absolute" data-dismiss="modal">
                <i class="icon-close fas fa-arrow-right"></i>
            </div>
            <div class="modal-body p-0">
                <iframe src="<?= INCLUDE_PATH ?>/https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2970.123073808986!2d12.490042215441486!3d41.89021017922119!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x132f61b6532013ad%3A0x28f1c82e908503c4!2sColiseu!5e0!3m2!1spt-BR!2sbr!4v1594148229878!5m2!1spt-BR!2sbr" width="600" height="450" aria-hidden="false" tabindex="0"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Modal [responsive menu] -->
<div id="menu" class="p-0 modal fade" role="dialog" aria-labelledby="menu" aria-hidden="true">
    <div class="modal-dialog modal-dialog-slideout" role="document">
        <div class="modal-content full">
            <div class="modal-header" data-dismiss="modal">
                <i class="icon-close fas fa-arrow-right"></i>
            </div>
            <div class="menu modal-body">
                <div class="row w-100">
                    <div class="items p-0 col-12 text-center">
                        <!-- Append [navbar] -->
                    </div>
                    <div class="contacts p-0 col-12 text-center">
                        <!-- Append [navbar] -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scroll [to top] -->
<div id="scroll-to-top" class="scroll-to-top">
    <a href="#header" class="smooth-anchor">
        <i class="fas fa-arrow-up"></i>
    </a>
</div>

<!-- ==============================================
Google reCAPTCHA // Put your site key here
=============================================== -->
<script src="https://www.google.com/recaptcha/api.js?render=6Lf-NwEVAAAAAPo_wwOYxFW18D9_EKvwxJxeyUx7"></script>

<!-- ==============================================
Vendor Scripts
=============================================== -->
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/jquery.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/jquery.easing.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/jquery.inview.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/popper.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/bootstrap.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/ponyfill.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/slider.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/animation.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/progress-radial.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/bricklayer.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/gallery.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/shuffle.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/cookie-notice.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/vendor/particles.min.js"></script>
<script src="<?= INCLUDE_PATH ?>/assets/js/main.js"></script>