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

$FormularioId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$i = 1;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Perguntas</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=formularios/home" class="text-muted">Prontuários</a>
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
                <div class="card-body">
                    <div class="row">
                        <?php
                        $Read->ExeRead(DB_FORM_PERG, "WHERE pergunta_formulario_id = :id", "id={$FormularioId}");
                        foreach ($Read->getResult() as $Reg) :
                            extract($Reg);
                        ?>
                            <div class="col-lg-4">
                                <form class="auto_save j_tab_home tab_create" name="user_manager" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="callback" value="Respostas" />
                                    <input type="hidden" name="callback_action" value="manager" />
                                    <input type="hidden" name="formulario_id" value="<?= $FormularioId ?>" />
                                    <input type="hidden" name="pergunta_id" value="<?= $pergunta_id ?>" />
                                    <h3><?= $pergunta_title ?></h3>
                                    <?php
                                    if ($pergunta_type == 1) :
                                    ?>
                                        <select name="resposta" id="" class="form-control">
                                            <?php
                                            $Read->ExeRead(DB_FORM_PERG_OPT, "WHERE option_pergunta_id = :id", "id={$pergunta_id}");
                                            foreach ($Read->getResult() as $Reg) :
                                                extract($Reg);
                                            ?>
                                                <option value="<?= $option_id ?>"><?= $option_title ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <input type="text" class="form-control" name="resposta">
                                    <?php endif; ?>
                                </form>
                            </div>

                            <?php
                            if ($i % 3 == 0) :
                            ?>

                    </div>
                    <div class="row" style="padding-top: 1em">
                <?php
                            endif;
                            $i++;
                        endforeach;
                ?>

                    </div>
                    <div class="card-footer">
                        <div class="row">

                        </div>
                    </div>
                </div>
            </div>
        </div>