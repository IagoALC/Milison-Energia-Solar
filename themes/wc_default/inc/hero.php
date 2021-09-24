<!--hero section start-->
<?php
$Read->ExeRead(DB_HERO);
if ($Read->getResult()) :
?>
    <section class="hero" id="hero">
        <div class="hero__wrapper">
            <div class="container">
                <div class="row align-items-lg-center">
                    <div class="col-lg-6 order-2 order-lg-1">
                        <h1 class="main-heading color-black"><?= $Read->getResult()[0]['hero_title'] ?></h1>
                        <?= str_replace("<p>", "<p class='paragraph'>", $Read->getResult()[0]['hero_desc']) ?>
                        <div class="download-buttons">
                            <a href="#" class="google-play">
                                <i class="fab fa-google-play"></i>
                                <div class="button-content">
                                    <h6>Baixe no <span>Google Play</span></h6>
                                </div>
                            </a>
                            <a href="#" class="apple-store">
                                <i class="fab fa-apple"></i>
                                <div class="button-content">
                                    <h6>Baixe na <span>Apple Store</span></h6>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 order-1 order-lg-2">
                        <div class="questions-img hero-img">
                            <img src="<?= BASE ?>/uploads/<?= $Read->getResult()[0]['hero_image'] ?>" alt="image">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php
endif;
?>