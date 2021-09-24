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

$FaqId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($FaqId) :
    $Read->ExeRead(DB_FAQ, "WHERE faq_id = :id", "id={$FaqId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);
    else :
        $_SESSION['trigger_controll'] = "<b>OPPSS {$Admin['faq_name']}</b>, você tentou editar um Passo que não existe ou que foi removido recentemente!";
        header('Location: dashboard.php?wc=site/faq/home');
        exit;
    endif;
else :
    $CreateUserDefault = [
        "faq_title" => ""
    ];
    $Create->ExeCreate(DB_FAQ, $CreateUserDefault);
    header("Location: dashboard.php?wc=site/faq/create&id={$Create->getResult()}");
    exit;
endif;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Faqs</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=site/faq/home" class="text-muted">Faqs</a>
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
                        <span rel='dashboard_header_search' callback='Faqs' callback_action='delete' class='btn btn-danger j_swal_action' id='<?= $FaqId; ?>'>Deletar Registro!</span>
                    </div>
                </div>
                <form class="auto_save j_tab_home tab_create" name="faq_manager" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Faqs" />
                    <input type="hidden" name="callback_action" value="manager" />
                    <input type="hidden" name="faq_id" value="<?= $FaqId; ?>" />
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Título:</label>
                                <input class="form-control" value="<?= $faq_title; ?>" type="text" name="faq_title" placeholder="Título" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Descrição:</label>
                                <textarea class="form-control" value="<?= $faq_desc; ?>" type="text" name="faq_desc" rows=10><?= $faq_desc; ?></textarea>
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