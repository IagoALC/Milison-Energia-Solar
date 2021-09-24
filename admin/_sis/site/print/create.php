<?php
$AdminLevel = 3;
if (empty($DashboardLogin) || empty($Admin) || ($Admin['user_level'] < 6)) :
    die('<div style="text-align: center; margin: 5% 0; color: #C54550; font-size: 1.6em; font-weight: 400; background: #fff; float: left; width: 100%; padding: 30px 0;"><b>ACESSO NEGADO:</b> Você não esta logado<br>ou não tem permissão para acessar essa página!</div>');
endif;

// AUTO INSTANCE OBJECT READ
if (empty($Read)) :
    $Read = new Read;
endif;

// AUTO INSTANCE OBJECT READ
if (empty($Create)) :
    $Create = new Create;
endif;

$PrintId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($PrintId) :
    $Read->ExeRead(DB_PRINT, "WHERE print_id = :id", "id={$PrintId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);
    else :
        $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar um print que não existe ou que foi removido recentemente!", E_USER_NOTICE);
        header('Location: dashboard.php?wc=site/prints/create&id=1');
        exit;
    endif;
endif;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Prints</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=prints/create&id=<?= $PrintId ?>" class="text-muted">Prints</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="content flex-column-fluid" id="kt_content">

    <div class="d-flex flex-row">
        <div class="flex-row-fluid ml-lg-8">
            <div class="card card-custom">
                <div class="card-header py-3">
                    <div class="card-title align-items-start flex-column">
                        <h3 class="card-label font-weight-bolder text-dark">Dados Iniciais</h3>
                    </div>
                </div>
                <form class="auto_save j_tab_home tab_create" name="user_manager" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Prints" />
                    <input type="hidden" name="callback_action" value="manager" />
                    <input type="hidden" name="print_id" value="<?= $PrintId; ?>" />
                    <div class="card-body">
                        <div class="form-group row">
                            <?php
                            $i = 1;
                            for ($i; $i <= 5; $i++) :
                                $print = 'print_image' . $i;
                            ?>
                                <div class="col-lg-2">
                                    <label>Imagem <?= $i ?></label>
                                    <?php
                                    $Image = (file_exists("../uploads/{$$print}") && !is_dir("../uploads/{$$print}") ? "../uploads/{$$print}" : '_img/no_image.jpg');
                                    ?>
                                    <img class="print" src="<?= $Image ?>" alt="" style="width: 20%">
                                    <input class="form-control wc_loadimage" value="<?= $print; ?>" type="file" name="<?= $print ?>" />
                                </div>
                                <?php
                                if ($i == 10) :
                                ?>
                        </div>
                        <div class="form-group row">
                    <?php
                                endif;
                            endfor;
                    ?>
                        </div>
                    </div>
            </div>


            <div class="card-footer">
                <div class="row">
                    <div class="col-lg-12 text-lg-right">
                        <button type="submit" class="btn btn-primary mr-2">Atualizar</button>
                        <img class="form_load none fl_right" style="margin-left: 10px; margin-top: 2px; display: none" alt="Enviando Requisição!" title="Enviando Requisição!" src="_img/load.gif" />
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>
</div>