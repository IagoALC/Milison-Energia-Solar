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

$ImageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($ImageId) :
    $Read->ExeRead(DB_ANALYSIS_IMAGE, "WHERE ima_id = :id", "id={$ImageId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);
    else :
        $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar uma análise que não existe ou que foi removido recentemente!", E_USER_NOTICE);
        header('Location: dashboard.php?wc=imagens/home');
        exit;
    endif;
else :
    $NewFormulario = [
        "ima_cover" => '',
        "ima_pro_id" => $Admin['user_id']
    ];
    $Create->ExeCreate(DB_ANALYSIS_IMAGE, $NewFormulario);
    header('Location: dashboard.php?wc=imagens/create&id=' . $Create->getResult());
    exit;
endif;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Imagens</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=imagens/home" class="text-muted">Imagens</a>
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
                    <div class="card-toolbar">
                        <span rel='dashboard_header_search' callback='Imagens' callback_action='delete' class='btn btn-danger j_swal_action' id='<?= $ImageId; ?>'>Deletar Registro!</span>
                    </div>
                </div>
                <form class="auto_save j_tab_home tab_create" name="user_manager" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Imagens" />
                    <input type="hidden" name="callback_action" value="manager" />
                    <input type="hidden" name="ima_id" value="<?= $ImageId; ?>" />
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-lg-4">
                                <label>Imagem</label>
                                <?php
                                $Image = (file_exists("../uploads/{$ima_cover}") && !is_dir("../uploads/{$ima_cover}") ? "../uploads/{$ima_cover}" : '_img/no_image.jpg');
                                ?>
                                <img class="ima_cover" src="<?= $Image ?>" alt="" style="width: 100%">
                                <input class="form-control wc_loadimage" value="<?= $ima_cover; ?>" type="file" name="ima_cover" />
                            </div>
                            <div class="col-lg-8">
                                <label>Análise</label>
                                <select name="ima_ana_id" class="form-control">
                                    <?php
                                    if ($Admin['user_level'] == 7) :
                                        $Read->ExeRead(DB_ANALYSIS, "WHERE ana_pro_id = {$Admin['user_id']}");
                                    else :
                                        $Read->ExeRead(DB_ANALYSIS);
                                    endif;
                                    if ($Read->getResult()) :
                                        foreach ($Read->getResult() as $Ana) :
                                    ?>
                                            <option value="<?= $Ana['ana_id'] ?>" <?= $Ana['ana_id'] == $ima_ana_id ? 'selected' : '' ?>><?= $Ana['ana_title'] ?></option>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <?php
                            if ($Admin['user_level'] != 7) :
                            ?>
                                <div class="col-lg-6">
                                    <label>Profissional</label>
                                    <select name="ima_pro_id" class="form-control">
                                        <?php
                                        $Read->ExeRead(DB_USERS, "WHERE user_level = 7");
                                        if ($Read->getResult()) :
                                            foreach ($Read->getResult() as $User) :
                                        ?>
                                                <option value="<?= $User['user_id'] ?>" <?= $User['user_id'] == $ima_pro_id ? 'selected' : '' ?>><?= $User['user_name'] ?> <?= $User['user_lastname'] ?></option>
                                        <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </select>
                                </div>
                            <?php
                            endif;
                            ?>
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