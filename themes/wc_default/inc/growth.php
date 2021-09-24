<!--growth section start-->
<section class="growth" id="feature">
    <div class="growth__wrapper">
        <div class="container">
            <h2 class="section-heading color-black" style="width: 90% !important">App que auxilia o crescimento profissional</h2>
            <div class="row">
                <?php
                $Read->ExeRead(DB_GROWTH, "LIMIT 0, 6");
                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $Growth) :
                        extract($Growth);
                ?>
                        <div class="col-lg-6">
                            <div class="growth__box">
                                <div class="icon">
                                    <i class="fad fa-user-astronaut"></i>
                                </div>
                                <div class="content">
                                    <h3><?= $growth_title ?></h3>
                                    <p class="paragraph dark"><?= $growth_desc ?></p>
                                </div>
                            </div>
                        </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
            <div class="row">
                <div class="button__wrapper">
                    <a href="#" class="button">
                        <span>COMECE AGORA <i class="fad fa-long-arrow-right"></i></span>
                    </a>
                    <a href="#" class="button">
                        <span>SAIBA MAIS <i class="fad fa-long-arrow-right"></i></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<!--growth section end-->