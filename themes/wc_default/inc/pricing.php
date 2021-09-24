<!--pricing section start-->
<section class="pricing" id="pricing">
    <div class="pricing__wrapper">
        <h2 class="section-heading color-black" style="width: 90% !important">Planos de preços fáceis para suas necessidades.</h2>
        <div class="container">
            <div class="row">
                <?php
                $Read->ExeRead(DB_PLAN, "LIMIT 0, 3");
                if ($Read->getResult()) :
                    foreach ($Read->getResult() as $Plan) :
                        extract($Plan);
                        $priceArray = explode(".", $plan_price);
                        $price = implode(",", $priceArray);
                ?>
                        <div class="col-lg-4">
                            <div class="pricing__single pricing__single-2">
                                <div class="icon">
                                    <i class="fad fa-user-graduate"></i>
                                </div>
                                <h4><?= $plan_title ?></h4>
                                <h3>R$ <?= $price ?></h3>
                                <div class="list">
                                    <ul>
                                        <?php
                                        $Read->ExeRead(DB_PLAN_BENEFIT, "WHERE benefit_plan_id = {$plan_id}");
                                        if ($Read->getResult()) :
                                            foreach ($Read->getResult() as $Benefit) :
                                                extract($Benefit);
                                        ?>
                                                <li><?= $benefit_title ?></li>
                                        <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </ul>
                                </div>
                                <a href="#" class="button">
                                    <span>GET STARTED <i class="fad fa-long-arrow-right"></i></span>
                                </a>
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
<!--pricing section end-->