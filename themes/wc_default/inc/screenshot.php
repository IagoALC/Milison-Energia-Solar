<!--screenshot section start-->
<section class="screenshot" id="preview">
    <div class="screenshot__wrapper">
        <div class="container">
            <div class="screenshot__info">
                <h2 class="section-heading color-black" style="width: 90% !important">Conheça um pouco mais</h2>
                <div class="screenshot-nav">
                    <div class="screenshot-nav-prev"><i class="fad fa-long-arrow-left"></i></div>
                    <div class="screenshot-nav-next"><i class="fad fa-long-arrow-right"></i></div>
                </div>
            </div>
        </div>
        <div class="swiper-container screenshot-slider">
            <div class="swiper-wrapper">
                <?php
                $i = 1;
                $Read->ExeRead(DB_PRINT);
                if ($Read->getResult()) :
                    for ($i; $i <= 5; $i++) :
                        $print = 'print_image' . $i;
                ?>
                        <div class="swiper-slide screenshot-slide">
                            <img src="<?= BASE ?>/uploads/<?= $Read->getResult()[0][$print] ?>" alt="image">
                        </div>
                <?php
                    endfor;
                endif;
                ?>
            </div>
        </div>
    </div>
</section>
<!--screenshot section end-->