<!--video section start-->
<div class="video" id="video">
        <div class="video__wrapper">
            <div class="container">
                <div class="video__play">
                    <button type="button" data-toggle="modal" data-target="#videoModal">
                        <i class="fad fa-play"></i>
                    </button>
                    <div class="modal fade" id="videoModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-close">
                                    <button type="button" data-dismiss="modal" aria-label="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    $Read->ExeRead(DB_VIDEO);
                                    ?>
                                    <iframe src="<?= $Read->getResult()[0]['video_title'] ?>" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="video__background">
                    <img src="<?= INCLUDE_PATH ?>/assets/images/video-bg-1.png" alt="image" class="texture-1">
                    <img src="<?= INCLUDE_PATH ?>/assets/images/video-img.png" alt="image" class="phone">
                    <img src="<?= INCLUDE_PATH ?>/assets/images/video-bg-2.png" alt="image" class="texture-2">
                </div>
            </div>
        </div>
    </div>
    <!--video section end-->