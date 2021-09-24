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

$CursoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($CursoId) :
    $Read->ExeRead(DB_CURSOS, "WHERE curso_id = :id", "id={$CursoId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);

        $Read->ExeRead(DB_USERS, "WHERE user_id = :user", "user={$curso_user_id}");
        if ($Read->getResult()) :
            extract($Read->getResult()[0]);
        else :
            $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar uma mídia que não existe ou que foi removido recentemente!", E_USER_NOTICE);
            header('Location: dashboard.php?wc=modelo/cur_home');
            exit;
        endif;
    else :
        $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar uma mídia que não existe ou que foi removido recentemente!", E_USER_NOTICE);
        header('Location: dashboard.php?wc=modelo/cur_home');
        exit;
    endif;
else:
    $NewMidia = ['curso_title' => '',
                 'curso_user_id' => 1];
    $Create->ExeCreate(DB_CURSOS, $NewMidia);
    header('Location: dashboard.php?wc=modelo/cur_create&id=' . $Create->getResult());
    exit;
endif;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Cursos</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=modelo/cur_home" class="text-muted">Cursos</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="#" class=""><?= $user_name; ?></a>
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
                        <span class="text-muted font-weight-bold font-size-sm mt-1"><?= $user_name; ?></span>
                    </div>
                    <div class="card-toolbar">
                        <span rel='dashboard_header_search' callback='Cursos' callback_action='delete' class='btn btn-danger j_swal_action' id='<?= $CursoId; ?>'>Deletar Registro!</span>
                    </div>
                </div>
                <form class="auto_save j_tab_home tab_create" name="user_manager" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Cursos" />
                    <input type="hidden" name="callback_action" value="manager" />
                    <input type="hidden" name="curso_id" value="<?= $CursoId; ?>" />
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Título:</label>
                                <input class="form-control" value="<?= $curso_title; ?>" type="text" name="curso_title" placeholder="Título" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Descrição:</label>
                                <textarea class="form-control work_mce_basic" type="text" name="curso_desc" rows="10"><?= $curso_desc; ?></textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-6">
                                <label>Status:</label>
                                <select class="form-control" name="curso_status" id="">
                                    <option value="0">Aberto</option>
                                    <option value="1">Fechado</option>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label>Profissional:</label>
                                <select name="curso_user_id" class="form-control" id="">
                                <?php
                                $Read->ExeRead(DB_USERS);
                                foreach ($Read->getResult() as $Reg) :
                                ?>
                                    <option  value="<?= $Reg['user_id'] ?>" <?= $user_id == $Reg['user_id'] ? "selected" : "" ?>><?= $Reg['user_name'] ?> <?= $Reg['user_lastname'] ?></option>
                                <?php
                                endforeach;
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Link:</label>
                                <input class="form-control work_mce_basic" type="text" name="curso_link" rows="10"><?= $curso_link; ?></input>
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
</div>