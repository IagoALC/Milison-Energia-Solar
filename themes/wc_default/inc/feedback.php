<!--client section start-->
<section class="clients-sec" id="feedback">
    <div class="container">
        <h2 class="section-heading color-black">Hear from what others had to say.</h2>
        <div class="testimonial__wrapper">
            <?php
            $Read->ExeRead(DB_COMMENTS, "LIMIT 0, 4");
            if ($Read->getResult()) :
                foreach ($Read->getResult() as $Com) :
                    extract($Com);
                    $Read->ExeRead(DB_USERS, "WHERE user_id={$user_id}");
                    extract($Read->getResult()[0]);
            ?>
                    <div class="client client-01 active">
                        <div class="image">
                            <img src="<?= BASE ?>/uploads/<?= $user_thumb ?>" style="width: 150px; height: 150px;" alt="image">
                        </div>
                        <div class="testimonial">
                            <div class="testimonial__wrapper">
                                <p><?= $comment ?></p>
                                <h4><?= $user_name ?> <?= $user_lastname ?></h4>
                            </div>
                        </div>
                    </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <div class="clients">
            <div class="clients__info">
                <h3>47,000+</h3>
                <p class="paragraph dark">Customers in over 90 countries are growing their businesses with us.</p>
            </div>
            <div class="swiper-container clients-slider">
                <div class="swiper-wrapper">
                    <?php
                    $Read->ExeRead(DB_USERS, "WHERE user_thumb IS NOT NULL");
                    if ($Read->getResult()) :
                        foreach ($Read->getResult() as $User) :
                            extract($User);
                    ?>
                            <div class="swiper-slide clients-slide">
                                <a href="#"><img src="<?= BASE ?>/uploads/<?= $user_thumb ?>" alt="image"></a>
                            </div>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!--client section end-->