<section class="step">
    <div class="step__wrapper">
        <div class="container">
            <h2 class="section-heading color-black" style="width: 90% !important">Dê início ao seu crescimento com apenas alguns cliques.</h2>
            <div class="row">
                <?php
                $Read->ExeRead(DB_STEP, "LIMIT 0, 4");
                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $Step) :
                        extract($Step);
                ?>
                        <div class="col-lg-4">
                            <div class="step__box">
                                <div class="image">
                                    <img src="<?= BASE ?>/uploads/<?= $step_image ?>" alt="image">
                                </div>
                                <div class="content">
                                    <h3><?= $step_title ?></h3>
                                    <p class="paragraph dark"><?= $step_desc ?></p>
                                </div>
                            </div>
                        </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
        </div>
    </div>
</section>