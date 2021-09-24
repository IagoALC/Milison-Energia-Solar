<!--questions section start-->
<section class="questions" id="faq" style="margin-top: 3rem;">
    <div class="questions__wrapper">
        <div class="container">
            <h2 class="section-heading color-black">Algumas perguntas</h2>
            <div class="row align-items-lg-center">
                <div class="col-lg-6 order-2 order-lg-1">
                    <div id="accordion">
                        <?php
                        $Read->ExeRead(DB_FAQ, "LIMIT 0, 7");
                        $i = 1;
                        if ($Read->getResult()) :
                            foreach ($Read->getResult() as $Faq) :
                                extract($Faq);
                        ?>
                                <div class="card" id="card-<?=$i?>">
                                    <div class="card-header" id="heading-<?=$i?>">
                                        <h5 class="mb-0  hidden">
                                            <button class="btn btn-link" data-toggle="collapse" data-target="#collapse-<?=$i?>" aria-expanded="true" aria-controls="collapse-<?=$i?>" style="color: #000 !important;">
                                                <?= $faq_title ?>
                                            </button>
                                        </h5>
                                    </div>

                                    <div id="collapse-<?=$i?>" class="collapse" aria-labelledby="heading-<?=$i?>" data-parent="#accordion">
                                        <div class="card-body">
                                            <p class="paragraph"><?= $faq_desc ?></p>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            $i++;
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                <div class="col-lg-6 order-1 order-lg-2">
                    <div class="questions-img">
                        <img src="<?= INCLUDE_PATH ?>/assets/images/phone-01.png" alt="image">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--questions section end-->