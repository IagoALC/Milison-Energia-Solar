<?php
$AdminLevel = LEVEL_WC_USERS;
if (empty($DashboardLogin) || empty($Admin) || ($Admin['user_level'] == 4 || $Admin['user_level'] == 6)) :
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

$StepId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($StepId) :
    $Read->ExeRead(DB_STEP, "WHERE step_id = :id", "id={$StepId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);
    else :
        $_SESSION['trigger_controll'] = "<b>OPPSS {$Admin['step_name']}</b>, você tentou editar um Passo que não existe ou que foi removido recentemente!";
        header('Location: dashboard.php?wc=site/step/home');
        exit;
    endif;
else :
    $CreateUserDefault = [
        "step_title" => ""
    ];
    $Create->ExeCreate(DB_STEP, $CreateUserDefault);
    header("Location: dashboard.php?wc=site/step/create&id={$Create->getResult()}");
    exit;
endif;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Passos</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=site/step/home" class="text-muted">Passos</a>
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
                        <span rel='dashboard_header_search' callback='Steps' callback_action='delete' class='btn btn-danger j_swal_action' id='<?= $StepId; ?>'>Deletar Registro!</span>
                    </div>
                </div>
                <form class="auto_save j_tab_home tab_create" name="step_manager" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Steps" />
                    <input type="hidden" name="callback_action" value="manager" />
                    <input type="hidden" name="step_id" value="<?= $StepId; ?>" />
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Título:</label>
                                <input class="form-control" value="<?= $step_title; ?>" type="text" name="step_title" placeholder="Título" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Descrição:</label>
                                <textarea class="form-control" value="<?= $step_desc; ?>" type="text" name="step_desc" rows=10><?= $step_desc; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <?php
                                $PostCover = (!empty($step_image) && file_exists("../uploads/{$step_image}") && !is_dir("../uploads/{$step_image}") ? "uploads/{$step_image}" : 'admin/_img/no_image.jpg');
                                ?>
                                <img class="post_thumb cover" alt="Capa" title="Capa" src="../tim.php?src=<?= $PostCover; ?>&w=400&h=700" default="../tim.php?src=<?= $PostCover; ?>&w=400&h=700" />
                                <input type="file" name="step_image" class="wc_loadimage form-control" />
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
</div>