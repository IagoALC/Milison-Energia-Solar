<!--feature section start-->
<section class="feature" id="intro">
    <div class="container">
        <h2 class="section-heading color-black">Se surpreenda com todas as funcionalidades</h2>
        <div class="row">
            <?php
            $Read->ExeRead(DB_INTRO, "LIMIT 0, 4");
            if ($Read->getResult()) :
                foreach ($Read->getResult() as $Intro) :
                    extract($Intro);
            ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature__box feature__box--1" style="margin-top: 0px !important;">
                            <!-- <div class="icon icon-1">
                        <i class="fad fa-user-astronaut"></i>
                    </div> -->
                            <div class="feature__box__wrapper" style="background-color: #8EC1CE">
                                <div class="feature__box--content feature__box--content-1">
                                    <h3><?= $intro_title ?></h3>
                                    <p class="paragraph dark"><?= $intro_desc ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
    </div>
</section>
<!--feature section end-->