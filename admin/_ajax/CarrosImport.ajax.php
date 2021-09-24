<?php

session_start();
require '../../_app/Config.inc.php';
require '../../_app/Library/SimpleXLSX/SimpleXLSX.php';
$NivelAcess = 3; //LEVEL_WC_PRODUCTS;

if (!APP_PRODUCTS || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'CarrosImport';
$PostData = filter_input_array(INPUT_POST, FILTER_DEFAULT);

//VALIDA AÇÃO
if ($PostData && $PostData['callback_action'] && $PostData['callback'] == $CallBack):
//PREPARA OS DADOS
    $Case = $PostData['callback_action'];
    unset($PostData['callback'], $PostData['callback_action']);

// AUTO INSTANCE OBJECT READ
    if (empty($Read)):
        $Read = new Read;
    endif;

// AUTO INSTANCE OBJECT CREATE
    if (empty($Create)):
        $Create = new Create;
    endif;

// AUTO INSTANCE OBJECT UPDATE
    if (empty($Update)):
        $Update = new Update;
    endif;

// AUTO INSTANCE OBJECT DELETE
    if (empty($Delete)):
        $Delete = new Delete;
    endif;
    $Upload = new Upload('../../uploads/');

//SELECIONA AÇÃO
    switch ($Case):

        case 'list_import':
            $UploadXLXS = (!empty($_FILES['arquivo_excel']) ? $_FILES['arquivo_excel'] : null);
            if ($UploadXLXS):
                $Upload->File($UploadXLXS, time(), "excel", 20);
                if ($Upload->getResult()):
                    if ($xlsx = SimpleXLSX::parse('../../uploads/' . $Upload->getResult())) {
                        $dim = $xlsx->dimension();
                        $num_cols = $dim[0];
                        $num_rows = $dim[1];
                        $l = 0;
                        foreach ($xlsx->rows(0) as $r) {
                            if ($l > 0):
                                if ($r[1]):
//Pesquisa Marca
                                    $Read->ExeRead(DB_VEICULOS_MARCA, "WHERE marca_title = :id", "id={$r[1]}");
                                    if ($Read->getResult()):
                                        $marca_id = $Read->getResult()[0]["marca_id"];
                                    else:
                                        $CreateDefault = [
                                            "marca_title" => $r[1] ? $r[1] : null,
                                        ];
                                        $Create->ExeCreate(DB_VEICULOS_MARCA, $CreateDefault);
                                        if ($Create->getResult()):
                                            $marca_id = $Create->getResult();
                                        endif;
                                    endif;
                                endif;

                                if ($r[2]):
//Pesquisa Modelo
                                    $Read->ExeRead(DB_VEICULOS_MODELO, "WHERE mod_title = :id", "id={$r[2]}");
                                    if ($Read->getResult()):
                                        $mod_id = $Read->getResult()[0]["marca_id"];
                                    else:
                                        $CreateDefault = [
                                            "marca_id" => $marca_id,
                                            "mod_title" => $r[2] ? $r[2] : null,
                                        ];
                                        $Create->ExeCreate(DB_VEICULOS_MODELO, $CreateDefault);
                                        if ($Create->getResult()):
                                            $mod_id = $Create->getResult();
                                        endif;
                                    endif;
                                endif;

                                if ($r[4]):
//Pesquisa Equipamento
                                    $Read->ExeRead(DB_PDT, "WHERE pdt_title = :id", "id={$r[4]}");
                                    if ($Read->getResult()):
                                        $pdt_id = $Read->getResult()[0]["pdt_id"];
                                    else:
                                        $CreateDefault = [
                                            "pdt_title" => $r[4] ? $r[4] : null,
                                        ];
                                        $Create->ExeCreate(DB_PDT, $CreateDefault);
                                        if ($Create->getResult()):
                                            $pdt_id = $Create->getResult();
                                        endif;
                                    endif;
                                endif;

                                if ($r[7] || $r[8]):
//Pesquisa Cliente
                                    $WhereOpt = "";
                                    if (isset($r[7])):
                                        $WhereOpt .= "user_name='{$r[7]}'";
                                    endif;
                                    if (isset($r[7]) && isset($r[8])):
                                        $WhereOpt .= " OR ";
                                    endif;
                                    if (isset($r[8])):
                                        $WhereOpt .= "user_document='{$r[8]}'";
                                    endif;

                                    $Read->ExeRead(DB_USERS, "WHERE {$WhereOpt}");
                                    if ($Read->getResult()):
                                        $user_id = $Read->getResult()[0]["user_id"];
                                    else:
                                        $CreateDefault = [
                                            "user_type" => 1,
                                            "user_name" => $r[7] ? $r[7] : null,
                                            "user_status" => 1,
                                            "user_document" => $r[8] ? $r[8] : null,
                                            "user_level" => 1
                                        ];
                                        $Create->ExeCreate(DB_USERS, $CreateDefault);
                                        if ($Create->getResult()):
                                            $user_id = $Create->getResult();
                                        endif;
                                    endif;
                                endif;

                                $CreateDefault = [
                                    "car_type" => $r[0] ? ($r[0] == "Carro" ? 1 : ($r[0] == "Moto" ? 2 : ($r[0] == "Ônibus" ? 3 : null))) : null,
                                    "car_placa" => $r[3] ? $r[3] : null,
                                    "marca_id" => isset($marca_id) ? $marca_id : null,
                                    "mod_id" => isset($mod_id) ? $mod_id : null,
                                    "pdt_id" => isset($pdt_id) ? $pdt_id : null,
                                    "user_id" => isset($user_id) ? $user_id : null,
                                    "car_acesso" => $r[6] == "Permitido" ? 1 : 0,
                                    "car_status" => 1,
                                    "car_pagamento" => $r[5] == "Em dia" ? 1 : 0,
                                ];
                                $Create->ExeCreate(DB_VEICULOS, $CreateDefault);
                            endif;
                            $l++;
                        }
                        $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>UPLOAD REALIZADO:</b> Olá {$_SESSION['userLogin']['user_name']}, os Veículos foram importados!");
                        $jSON['redirect'] = "dashboard.php?wc=clientes/veiculos";
                    } else {
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b>' . SimpleXLSX::parseError(), E_USER_ERROR);
                    }
                else:
                    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas não foi possível realizar o Upload!', E_USER_ERROR);
                endif;
            else:
                $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas selecione um arquivo para Upload!', E_USER_ERROR);
            endif;
            break;

    endswitch;

//RETORNA O CALLBACK
    if ($jSON):
        echo json_encode($jSON);
    else:
        $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas uma ação do sistema não respondeu corretamente. Ao persistir, contate o desenvolvedor!', E_USER_ERROR);
        echo json_encode($jSON);
    endif;
else:
//ACESSO DIRETO
    die('<br><br><br><center><h1>Acesso Restrito!</h1></center>');
endif;
