<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = 3;//LEVEL_FINANCE;

if (!APP_FINANCE || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPPSSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Finance';
$PostData = filter_input_array(INPUT_POST, FILTER_DEFAULT);

//VALIDA AÇÃO
if ($PostData && $PostData['callback_action'] && $PostData['callback'] == $CallBack):
    //PREPARA OS DADOS
    $Case = $PostData['callback_action'];
    unset($PostData['callback'], $PostData['callback_action']);

    // AUTO INSTANCE OBJECTs
    if (empty($Read)): $Read = new Read;
    endif;
    if (empty($Create)): $Create = new Create;
    endif;
    if (empty($Update)): $Update = new Update;
    endif;
    if (empty($Delete)): $Delete = new Delete;
    endif;
    $NumberMaxParc = 12;

    if (FINANCE_GTW_STATUS):
        require '../_siswc/finan/files/_src/gateways/Gerencianet/library/gtwGerencianet.php';
    endif;

    //SELECIONA AÇÃO
    switch ($Case):

        case "newlanc":
            $_SESSION['bf_finanLanc'] = [];
            $Content = ''
                    . '<form name="finanForm" class="mdl--form" action="" method="post" enctype="multipart/form-data">'
                    . '<input type="hidden" name="callback" value="Finance"/>'
                    . '<input type="hidden" name="callback_action" value="financial_release"/>'
                    . "<input type='hidden' name='fin_type' value='{$PostData['id']}'/>"
                    . "<label class='label'>"
                    . "<span class='legend'>Titulo do Lançamento:</span>"
                    . "<input type='text' style='font-size:1.05em' name='fin_title' value='' placeholder='Título do Lançamento' required/>"
                    . "</label>"
                    . "<label class='label'>"
                    . "<span class='legend'>Vincule o Cliente </span>";
            if (FINANCE_MODULE_CLIENTS):
                $Content .= ''
                        . "<select name='cli_id'>"
                        . '<option disabled selected>Cliente ou Fornecedor</option>';
                $Read->ExeRead(DB_CLIENTS, "WHERE 1 = 1 ORDER BY cli_surname ASC, cli_name ASC");
                if (!$Read->getResult()):
                    $Content .= '<option value="" disabled selected>Sem nenhum Cadastro!</option>';
                else:
                    foreach ($Read->getResult() as $Client):
                        if ((!empty($Client['cli_type']) && $Client['cli_type'] == 1)):
                            $Read->FullRead("SELECT u.user_name, u.user_lastname FROM " . DB_CLIENTS_MEMBERS . " m INNER JOIN " . DB_USERS . " u ON u.user_id = m.user_id WHERE m.responsibility = 1 AND m.cli_id = :id", "id={$Client['cli_id']}");
                            if (!$Read->getResult()):
                                $Name = "Usuario não encontrado ({$Client['cli_surname']})";
                            else:
                                $Name = "{$Read->getResult()[0]['user_name']} {$Read->getResult()[0]['user_lastname']} ({$Client['cli_surname']})";
                            endif;
                        else:
                            $Client['cli_name'] = ($Client['cli_name'] ? $Client['cli_name'] : '--');
                            $Client['cli_surname'] = ($Client['cli_surname'] ? $Client['cli_surname'] : 'Cliente sem nome');
                            $Name = "{$Client['cli_surname']} ({$Client['cli_name']})";
                        endif;
                        $Content .= "<option value='{$Client['cli_id']}'>{$Name}</option>";
                    endforeach;
                endif;
                $Content .= "</select>";
            else:
                $Content .= ''
                        . "<select name='user_id'>"
                        . "<option value='' disabled='disabled' selected='selected'>Selecione um Usuário</option>";
                // USUARIOS
                $Read->fullRead("SELECT user_id, user_name, user_lastname FROM " . DB_USERS . " WHERE 1=1");
                if (!$Read->getResult()):
                    $Content .= '<option value="" disabled selected>Cadastre usuários antes!</option>';
                else:
                    foreach ($Read->getResult() as $Client):
                        $Content .= "<option value='{$Client['user_id']}'>{$Client['user_name']} {$Client['user_lastname']}</option>";
                    endforeach;
                endif;
                $Content .= "</select>";
            endif;
            $Content .= ''
                    . "</label>"
                    . "<div class='label_50'>"
                    . "<label class='label'>"
                    . "<span class='legend'>Categoria</span>"
                    . "<select class='j-custom-select custom-select' name='category_id'>"
                    . "<option value='' disabled='disabled' selected='selected'>Selecione uma Categoria</option>";
            $Read->fullRead("SELECT category_id, category_title FROM " . DB_FINAN_CAT . " WHERE category_parent IS NULL AND category_type = :type AND category_status = :sta", "type={$PostData['id']}&sta=1");
            if ($Read->getResult()):
                foreach ($Read->getResult() as $CatPai):
                    $Content .= "<optgroup label='{$CatPai['category_title']}'>";
                    $Read->fullRead("SELECT category_id, category_title FROM " . DB_FINAN_CAT . " WHERE category_parent = :cat", "cat={$CatPai['category_id']}");
                    if (!$Read->getResult()):
                        $Content .= "<option disable='disable'>Nenhuma sub-categoria encontrada</option>";
                    else:
                        foreach ($Read->getResult() as $Cat):
                            $Content .= "<option value='{$Cat['category_id']}'>{$Cat['category_title']}</option>";
                        endforeach;
                    endif;
                    $Content .= "</optgroup>";
                endforeach;
            endif;
            $Content .= "</select>"
                    . "</label>"
                    . "<label class='label'>"
                    . "<span class='legend'>Valor (R$)</span>"
                    . "<input type='text' class='money-input' name='fin_value' placeholder='0,00' autocomplete='off'>"
                    . "</label>"
                    . "<label class='label'>"
                    . "<span class='legend'>Vencimento ou data do 1º pagamento</span>"
                    . "<input type='text' name='fin_due_date' class='jwc_datepicker' placeholder='00/00/0000' readonly='readonly'/>"
                    . "</label>"
                    . "<label class='label parc'>"
                    . "<span class='legend'>Forma de Pagamento</span>"
                    . "<select name='fin_payment_form'>"
                    . "<option value='' disabled='disabled' selected='selected'>Selecione...</option>";
            if (getFinanPaymentsForms()):
                foreach (getFinanPaymentsForms() as $Id => $Forms):
                    $Content .= "<option value='{$Id}'>{$Forms}</option>";
                endforeach;
            endif;
            $Content .= "</select>"
                    . "<div class='jget_split split' style='display: inline-block;'><input type='text' name='fin_split' value='1' autocomplete='off'></div> "
                    . "<button name='public' value='1' type='submit' title='Atualizar Informações' class='btn btn_blue icon-spinner9 j_finRefresh icon-notext'></button>"
                    . "</label>"
                    . "</div>"
                    . "<div class='clear'></div>"
                    . "</div>"
                    . "<div class='clear'></div>"
                    . "<img class='form_load none' load='true' style='margin-left:10px;' alt='Enviando Requisição!' title='Enviando Requisição!' src='./_img/load.gif'/>"
                    . '</form>'
                    . "<div class='mdl-splits j_splits'></div>";

            $jSON['modal'] = [
                'icon' => 'coin-dollar',
                'theme' => (($PostData['id'] == "rec") ? 'success' : 'error'),
                'title' => (($PostData['id'] == "rec") ? 'Lançamento de Receita' : 'Lançamento de Despesa'),
                'content' => $Content,
                'footer' => "<a class='fl_left j_ajaxModalClose'>Cancelar</a><div class='fl_right'><a class='btn btn_green btn-rounded j_createLanc'>CADASTRAR REGISTRO</a></div>",
                'callback' => ['plugginMaskMoney', 'plugginDatepicker']
            ];
            break;

        case 'alterStatus':
            if (!empty($PostData['status'])):

                $Read->FullRead("SELECT fin_split_id, fin_split_gat_id, fin_split_gat, fin_split_status, fin_split_method, fin_split_date FROM " . DB_FINAN_SPLITS . " WHERE fin_split_id = :s", "s={$PostData['id']}");
                if (!$Read->getResult()):
                    $jSON['trigger'] = AjaxErro("Oops! O pagamento não foi encontrado!", E_USER_ERROR);
                    $jSON['redirect'] = 'dashboard.php?wc=finan/home';
                else:
                    $ThisSplit = $Read->getResult()[0];

                    $ThisFuture = strtotime($ThisSplit['fin_split_date']) >= strtotime(date('Y-m-d')) ? true : false;
                    if ($ThisSplit['fin_split_status'] == 4 && $PostData['status'] != $ThisSplit['fin_split_status'] && $ThisSplit['fin_split_method'] == 2 && $ThisFuture):
                        $jSON['trigger'] = AjaxErro("<b>Oops!</b> Não é possível alterar o status de um boleto quando seu status é \"Pgt. Realizado\"!<br/><br/><small>O sistema financeiro está conectado ao Gateway gerador de boletos, por este motivo os boletos já pagos não podem mais ser alterados!</small>", E_USER_ERROR);
                        $jSON['showtrigger'] = true;
                        echo json_encode($jSON);
                        return;
                    elseif ($ThisSplit['fin_split_status'] == 5 && $PostData['status'] != $ThisSplit['fin_split_status'] && $ThisSplit['fin_split_method'] == 2 && $ThisFuture):
                        $jSON['trigger'] = AjaxErro("<b>Oops!</b> Não é possível alterar o status de um boleto quando seu status é \"Pgt. Cancelado\"!<br/><br/><small>O sistema financeiro está conectado ao Gateway gerador de boletos, por este motivo os boletos cancelados não podem mais ser alterados!</small>", E_USER_ERROR);
                        $jSON['showtrigger'] = true;
                        echo json_encode($jSON);
                        return;
                    else:
                        $Status['fin_split_status'] = $PostData['status'];
                    endif;

                    if ($PostData['status'] == 4 && $PostData['status'] != $ThisSplit['fin_split_status']): // Pgt. Realizado
                        $Status['fin_split_date_pay'] = date('Y-m-d H:i:s');

                        // MARCA BOLETO COMO PAGO NO GATEWAY
                        if (FINANCE_GTW_STATUS && $ThisSplit['fin_split_method'] == 2 && $ThisFuture):
                            if ($ThisSplit['fin_split_gat'] == 2 && FINANCE_GTW == 'Gerencianet'): // GERENCIANET
                                $Payment = new GtwGerencianet();

                                $Payment->setBilletPay($ThisSplit['fin_split_gat_id']);
                                if ($Payment->getResult()):
                                    $Update->ExeUpdate(DB_FINAN_SPLITS, $Status, "WHERE fin_split_id = :id", "id={$PostData['id']}");
                                endif;
                            endif;
                        else:
                            $Update->ExeUpdate(DB_FINAN_SPLITS, $Status, "WHERE fin_split_id = :id", "id={$PostData['id']}");
                        endif;

                    elseif ($PostData['status'] == 5): // Pgt. Cancelado
                        $Status['fin_split_date_pay'] = null;

                        // CANCELA BOLETO NO GATEWAY
                        if (FINANCE_GTW_STATUS && $ThisSplit['fin_split_method'] == 2 && $ThisFuture):
                            if ($ThisSplit['fin_split_gat'] == 2 && FINANCE_GTW == 'Gerencianet'): // GERENCIANET
                                $Payment = new GtwGerencianet();

                                $Payment->Cancel($ThisSplit['fin_split_gat_id']);
                                if ($Payment->getResult()):
                                    $Update->ExeUpdate(DB_FINAN_SPLITS, $Status, "WHERE fin_split_id = :id", "id={$PostData['id']}");
                                endif;
                            endif;
                        else:
                            $Update->ExeUpdate(DB_FINAN_SPLITS, $Status, "WHERE fin_split_id = :id", "id={$PostData['id']}");
                        endif;

                    else: // Outros status
                        $Update->ExeUpdate(DB_FINAN_SPLITS, $Status, "WHERE fin_split_id = :id", "id={$PostData['id']}");
                    endif;

                    if ($Update->getResult()):
                        $jSON['success'] = true;
                    endif;
                endif;
            endif;
            break;

        case "financial_release":
            if (!empty($PostData['fin_value'])):
                if (strpos($PostData['fin_value'], ",") && strpos($PostData['fin_value'], ".")):
                    $PostData['fin_value'] = str_replace(",", ".", str_replace(".", "", $PostData['fin_value']));
                elseif (strpos($PostData['fin_value'], ",") && !strpos($PostData['fin_value'], ".")):
                    $PostData['fin_value'] = str_replace(",", ".", $PostData['fin_value']);
                endif;
            else:
                $PostData['fin_value'] = null;
            endif;

            $PostData['category_id'] = (!empty($PostData['category_id']) ? $PostData['category_id'] : null);
            $PostData['fin_due_date'] = (!empty($PostData['fin_due_date']) ? Check::Data($PostData['fin_due_date']) : null);
            $PostData['fin_payment_form'] = (!empty($PostData['fin_payment_form']) ? $PostData['fin_payment_form'] : null);
            $PostData['fin_author'] = $_SESSION['userLogin']['user_id'];
            $PostData['fin_date'] = date("Y-m-d H:i:s");

            $PostData['user_id'] = (!empty($PostData['user_id']) ? $PostData['user_id'] : null);
            $PostData['cli_id'] = (!empty($PostData['cli_id']) ? $PostData['cli_id'] : null);

            $ParcData = [];

            if (!is_null($PostData['fin_value']) && !is_null($PostData['fin_payment_form']) && !is_null($PostData['fin_due_date'])):
                if ($PostData['fin_payment_form'] == "1"):
                    //Pagamento á Vista
                    $ParcData[1] = [
                        'date' => date("Y-m-d", strtotime("{$PostData['fin_due_date']}")),
                        'price' => number_format($PostData['fin_value'], 2, ",", "."),
                        'method' => (!empty($_SESSION['bf_finanLanc']['ParcData'][1]['method']) ? $_SESSION['bf_finanLanc']['ParcData'][1]['method'] : null),
                        'id' => 0];
                    $jSON['divcontent']['.jget_split'] = "<input type='text' name='fin_split' value='1' autocomplete='off'>";
                    $jSON['divcontent']['.j_splits'] = "<table class='j_splits'>
                        <tbody>
                            <tr>
                                <td>À Vista</td>
                                <td>
                                    <p>Método de Pgto.</p>
                                    <select class='j_splitsManager' name='parcMethod' id='1'>
                                        <option value='' disabled='disabled' selected='selected'>Selecione um Método</option>";
                    if (getFinanPaymentsMethods()):
                        foreach (getFinanPaymentsMethods() as $Id => $Methods):
                            $jSON['divcontent']['.j_splits'] .= "<option value='{$Id}' " . ($ParcData[1]['method'] == $Id ? 'selected' : '') . ">{$Methods}</option>";
                        endforeach;
                    endif;
                    $jSON['divcontent']['.j_splits'] .= "</select>
                                </td>
                                <td>
                                    <p>Vencimento</p><input disabled='disabled' class='formDate' type='text' name='' value='" . (date('d/m/Y', strtotime($ParcData[1]['date']))) . "'> 
                                </td>
                                <td>
                                    <p>Valor:</p><input disabled='disabled' class='' type='text' name='' value='R$ {$ParcData[1]['price']}'>  
                                </td>
                            </tr>        
                        </tbody>
                    </table>";

                elseif ($PostData['fin_payment_form'] == "2"):
                    //Pagamento Parcelado

                    $PostData['fin_split'] = ($PostData['fin_split'] < 2 ? 2 : ($PostData['fin_split'] > $NumberMaxParc ? $NumberMaxParc : $PostData['fin_split']));
                    $jSON['divcontent']['.jget_split'] = "<input style='pointer-events: inherit;' type='number' min='2' max='{$NumberMaxParc}' name='fin_split' value='{$PostData['fin_split']}' autocomplete='off'>";

                    $jSON['divcontent']['.j_splits'] = "<table class='j_splits'>";
                    $PriceStartParc = $PostData['fin_value'] / $PostData['fin_split'];
                    for ($P = 1; $P <= $PostData['fin_split']; $P++):
                        $PaymentDate = ($P > 1 ? " +" . ($P - 1) . " month" : null);
                        $DatenoChange = ($P == 1 ? "block" : null);

                        if ($P == $PostData['fin_split']):
                            $Price = 0;
                            foreach ($ParcData as $Value):

                                if (!empty($Value['price'])):
                                    if (strpos($Value['price'], ",") && strpos($Value['price'], ".")):
                                        $Value['price'] = str_replace(",", ".", str_replace(".", "", $Value['price']));
                                    elseif (strpos($Value['price'], ",") && !strpos($Value['price'], ".")):
                                        $Value['price'] = str_replace(",", ".", $Value['price']);
                                    endif;
                                else:
                                    $Value['price'] = null;
                                endif;

                                $Price += $Value['price'];
                            endforeach;
                            $PriceStartParc = $PostData['fin_value'] - $Price;
                        endif;

                        $ParcData[$P] = [
                            'date' => date("Y-m-d", strtotime("{$PostData['fin_due_date']}{$PaymentDate}")),
                            'price' => number_format($PriceStartParc, 2, ",", "."),
                            'method' => (!empty($_SESSION['bf_finanLanc']['ParcData'][$P]['method']) ? $_SESSION['bf_finanLanc']['ParcData'][$P]['method'] : null),
                            'id' => 0];

                        $jSON['divcontent']['.j_splits'] .= "<tbody>
                                                                <tr>
                                                                    <td>Parc. {$P}/{$PostData['fin_split']}</td>
                                                                    <td>
                                                                        <p>Método de Pgto.</p>
                                                                        <select class='j_splitsManager' id='{$P}' name='parcMethod'>
                                                                            <option value='' disabled='disabled' selected='selected'>Selecione um Método</option>";
                        if (getFinanPaymentsMethods()):
                            foreach (getFinanPaymentsMethods() as $Id => $Methods):
                                $jSON['divcontent']['.j_splits'] .= "<option value='{$Id}' " . ($ParcData[$P]['method'] == $Id ? 'selected' : '') . ">{$Methods}</option>";
                            endforeach;
                        endif;
                        $jSON['divcontent']['.j_splits'] .= "</select>
                                                                        <span class='splitsMsg parcMethod' id='msg_{$P}'></span>

                                                                    </td>
                                                                    <td>
                                                                        <p>Vencimento</p><input name='parcDate' id='{$P}' type='text' class='formDate j_splitsManager {$DatenoChange}'  value='" . (date('d/m/Y', strtotime($ParcData[$P]['date']))) . "'> 
                                                                        <span class='splitsMsg parcDate' id='msg_{$P}'></span>
                                                                    </td>
                                                                    <td>
                                                                        <p>Valor</p><input name='parcPrice' id='{$P}' type='text' class='money-input j_splitsManager'  value='{$ParcData[$P]['price']}'>
                                                                        <span class='splitsMsg parcPrice' id='msg_{$P}'></span>
                                                                    </td>
                                                                </tr>        
                                                            </tbody>";
                    endfor;
                    $jSON['divcontent']['.j_splits'] .= "</table><p class='al_right'></p>";
                    unset($_SESSION['bf_finanLanc']['CourrData']);

                // elseif ($PostData['fin_payment_form'] >= "3"):
                else:
                    //Pagamento Quinzenal / Mensal / Trimestral / Semestral / Anual
                    $PostData['fin_split'] = 1;

                    $jSON['divcontent']['.jget_split'] = "<input style='pointer-events:none;background:#eee' type='number' min='2' name='fin_split' autocomplete='off'>";
                    $jSON['divcontent']['.j_splits'] = "<table class='j_splits'>";
                    $PriceStartParc = $PostData['fin_value'];

                    $DTE = date('d', strtotime($PostData['fin_due_date']));
                    $ParcData[1] = [
                        'date' => date("Y-m-d", strtotime($PostData['fin_due_date'])),
                        'price' => number_format($PriceStartParc, 2, ",", "."),
                        'method' => (!empty($_SESSION['bf_finanLanc']['ParcData'][1]['method']) ? $_SESSION['bf_finanLanc']['ParcData'][1]['method'] : null),
                        'id' => 0];
                    $jSON['divcontent']['.j_splits'] .= "<tbody>"
                            . "<tr>
                                                            <td>" . getFinanPaymentsForms($PostData['fin_payment_form']) . "</td>
                                                            <td>
                                                                <p>Método de Pgto.</p>
                                                                <select class='j_splitsManager' name='parcMethod' id='1'>
                                                                    <option value='' disabled='disabled' selected='selected'>Selecione um Método</option>";
                    if (getFinanPaymentsMethods()):
                        foreach (getFinanPaymentsMethods() as $Id => $Methods):
                            $jSON['divcontent']['.j_splits'] .= "<option value='{$Id}' " . ($ParcData[1]['method'] == $Id ? 'selected' : '') . ">{$Methods}</option>";
                        endforeach;
                    endif;
                    $jSON['divcontent']['.j_splits'] .= "</select>
                                                            </td>
                                                            <td>
                                                                <p>1º Vencimento</p><input disabled='disabled' class='formDate' type='text' value='" . (date('d/m/Y', strtotime($PostData['fin_due_date']))) . "'> 
                                                            </td>
                                                            <td>
                                                                <p>Valor:</p><input disabled='disabled' class='' type='text' value='R$ {$ParcData[1]['price']}'>  
                                                            </td>"
                            . "</tr>"
                            . "</tbody>";


                    $_SESSION['bf_finanLanc']['CourrData'] = $DTE;
                    $jSON['divcontent']['.j_splits'] .= "</table><p class='al_right'></p>";
                endif;

                if (!empty($PostData['client_id']) && !empty($PostData['user_id'])):
                    $jSON['divcontent']['.j_splits'] = "<table class='j_splits'>
                            <tbody>
                                <tr>
                                    <td class='icon-warning'><b>ATENÇÃO:</b> PREENCHA APENAS O CAMPO CLIENTE OU APENAS O CAMPO USUÁRIO!</td>
                                </tr>        
                            </tbody>
                        </table>";
                    $jSON['divremove'] = ".j_lancOk";
                else:
                    $jSON['fadein'] = ".j_lancOk";
                endif;

                $_SESSION['bf_finanLanc']['LancData'] = $PostData; //Armazena informações sobre o lançamento
                $_SESSION['bf_finanLanc']['ParcData'] = $ParcData; //Armazena parcelas vinculadas ao lançamento
            else:
                $jSON['trigger'] = AjaxErro("Preencha todos os campos para continuar", E_USER_ERROR);
            endif;
            break;

        case "managerSplits":
            $jSON['divremove'] = ".j_lancOk";
            if (!empty($_SESSION['bf_finanLanc']['ParcData'][$PostData['splitsId']])):
                if (empty($PostData['splitsValue'])):
                    $jSON['error'] = "<b>Campo Vazio!</b><br>Esse campo não pode ficar vazio";
                    echo json_encode($jSON);
                    return;
                endif;

                if ($PostData['splitsName'] == "parcDate"):
                    $DateNow = date("d/m/Y");
                    $DateNew = $PostData['splitsValue'];

                    //Verifica se data da segunda parcela é maior que a data da 1ª
                    if (date("Y-m-d", strtotime(Check::Data($_SESSION['bf_finanLanc']['ParcData'][1]['date']))) > date("Y-m-d", strtotime(Check::Data($DateNew)))):

                        $jSON['error'] = "<b>Data Invalida!</b><br>Informe apenas datas futuras á data da primeira parcela";
                        echo json_encode($jSON);
                        return;
                    endif;

                    $_SESSION['bf_finanLanc']['ParcData'][$PostData['splitsId']]['date'] = $DateNew;
                    $jSON['success'] = true;

                // Só entra aqui se opção escolhida for parcelado
                elseif ($PostData['splitsName'] == "parcPrice" && $_SESSION['bf_finanLanc']['LancData']['fin_payment_form'] == '2'):
                    // $SplitsPrice = str_replace(['.', ',00', "R$", ',', ' '], ['', '', '', '.', ''], $PostData['splitsValue']);
                    if (!empty($PostData['splitsValue'])):
                        if (strpos($PostData['splitsValue'], ",") && strpos($PostData['splitsValue'], ".")):
                            $SplitsPrice = str_replace(",", ".", str_replace(".", "", $PostData['splitsValue']));
                        elseif (strpos($PostData['splitsValue'], ",") && !strpos($PostData['splitsValue'], ".")):
                            $SplitsPrice = str_replace(",", ".", $PostData['splitsValue']);
                        endif;
                    else:
                        $SplitsPrice = null;
                    endif;

                    //Valida Valor
                    if ($SplitsPrice > $_SESSION['bf_finanLanc']['LancData']['fin_value']):
                        $jSON['error'] = "<b>Valor Incorreto!</b><br>Uma Parcela não pode ser maior que o valor total";
                        echo json_encode($jSON);
                        return;
                    else:
                        $_SESSION['bf_finanLanc']['ParcData'][$PostData['splitsId']]['price'] = $SplitsPrice;

                        //Calcula o valor total obtido nas parcelas
                        $SplitsCalc = 0;
                        $SplitsCount = count($_SESSION['bf_finanLanc']['ParcData']);
                        foreach ($_SESSION['bf_finanLanc']['ParcData'] as $Parc):
                            // $Parc['price'] = (float) str_replace(',','.', $Parc['price']);
                            if (!empty($Parc['price'])):
                                if (strpos($Parc['price'], ",") && strpos($Parc['price'], ".")):
                                    $Parc['price'] = str_replace(",", ".", str_replace(".", "", $Parc['price']));
                                elseif (strpos($Value['price'], ",") && !strpos($Value['price'], ".")):
                                    $Parc['price'] = str_replace(",", ".", $Parc['price']);
                                endif;
                            else:
                                $Parc['price'] = null;
                            endif;

                            $SplitsCalc += $Parc['price'];
                        endforeach;

                        if (!(abs(((float) $_SESSION['bf_finanLanc']['LancData']['fin_value'] - $SplitsCalc) / $SplitsCalc) < 0.00001)):
                            if ($SplitsCalc < (float) $_SESSION['bf_finanLanc']['LancData']['fin_value']):
                                $jSON['error'] = "<b>Valor Incorreto!</b><br>O valor total das parcelas esta inferior ao valor do lançamento";
                                echo json_encode($jSON);
                                return;
                            elseif ($SplitsCalc > (float) $_SESSION['bf_finanLanc']['LancData']['fin_value']):
                                $jSON['error'] = "<b>Valor Incorreto!</b><br>O valor total das parcelas esta superior ao valor do lançamento";
                                echo json_encode($jSON);
                                return;
                            endif;
                        endif;
                        $_SESSION['bf_finanLanc']['customSplits'] = $SplitsCount;
                    endif;

                elseif ($PostData['splitsName'] == "parcMethod"):
                    $_SESSION['bf_finanLanc']['ParcData'][$PostData['splitsId']]['method'] = $PostData['splitsValue'];
                    $jSON['success'] = true;
                endif;

                $jSON['computed'] = "Tudo Certo {$_SESSION['userLogin']['user_name']} ;)";
            endif;
            break;

        case "createLanc":
            if (!empty($_SESSION['bf_finanLanc'])):
                if (empty($_SESSION['bf_finanLanc']['LancData']['category_id'])):
                    $jSON['trigger'] = AjaxErro("Selecione uma categoria para continuar!", E_USER_ERROR);
                    $jSON['showtrigger'] = true;
                    echo json_encode($jSON);
                    return;
                endif;

                foreach ($_SESSION['bf_finanLanc']['ParcData'] as $Key => $Value):
                    if (empty($Value['method'])):
                        $ErrorParc = $Key;
                        break;
                    endif;
                endforeach;

                if (isset($ErrorDate)):
                    $jSON['showtrigger'] = true;
                    echo json_encode($jSON);
                    return;
                elseif (isset($ErrorParc)):
                    $Error = ($_SESSION['bf_finanLanc']['LancData']['fin_payment_form'] == 2) ? "da parcela {$ErrorParc} " : "";

                    $jSON['trigger'] = AjaxErro("É obrigatório preencher o método de pagamento {$Error}para finalizar o cadastro", E_USER_ERROR);
                    $jSON['showtrigger'] = true;
                    echo json_encode($jSON);
                    return;
                endif;

                // VALIDA DADOS DO CLIENTE PARA GERACAO DO BOLETO
                if (FINANCE_MODULE_CLIENTS):
                    $Read->FullRead("SELECT "
                            . "u.user_name, u.user_lastname, u.user_document, u.user_datebirth, "
                            . "c.cli_email, c.cli_telephone, c.cli_name, c.cli_cnpj, c.cli_type "
                            . "FROM " . DB_CLIENTS_MEMBERS . " m "
                            . "INNER JOIN " . DB_USERS . " u ON u.user_id = m.user_id "
                            . "INNER JOIN " . DB_CLIENTS . " c ON c.cli_id = :id "
                            . "WHERE m.responsibility = 1 AND m.cli_id = :id", "id={$_SESSION['bf_finanLanc']['LancData']['cli_id']}"
                    );
                else:
                    $Read->FullRead("SELECT "
                            . "user_name AS cli_name, user_name, user_lastname, user_document, user_datebirth, "
                            . "user_email AS cli_email, user_telephone AS cli_telephone "
                            . "FROM " . DB_USERS . " WHERE user_id = :id", "id={$_SESSION['bf_finanLanc']['LancData']['user_id']}"
                    );
                endif;
                /*
                  if (!$Read->getResult()):
                  $jSON['showtrigger'] = true;
                  $jSON['trigger'] = AjaxErro("Oops! Você precisa vincular um cliente ao lançamento! Verifique antes de continuar!", E_USER_ERROR);
                  echo json_encode($jSON);
                  return;
                  else:
                  $UserData = $Read->getResult()[0];
                  if (!FINANCE_MODULE_CLIENTS):
                  $UserData['cli_type'] = 1;
                  endif;
                 */
                if ($Read->getResult()):
                    $UserData = $Read->getResult()[0];
                    $UserData['cli_type'] = 1;
                endif;

                foreach ($_SESSION['bf_finanLanc']['ParcData'] as $Parc):
                    if ($Parc['method'] == 2):
                        $ThisBillet = true;
                        break;
                    endif;
                endforeach;
                /*
                  if (isset($ThisBillet)):
                  if (empty($UserData['user_name']) || empty($UserData['user_lastname'])):
                  $jSON['trigger'] = AjaxErro("<b>ERRO AO GERAR COBRANÇA</b>!<br/> O nome e sobrenome do usuário principal de \"{$UserData['cli_name']}\" ({$UserData['user_name']} {$UserData['user_lastname']}) deve estar preenchido!<br/><br/><small>* Os dados exigidos devem estar previamente preenchidos. Estes dados são exigidos pelo Gateway para a correta geração do boleto bancário!</small>", E_USER_ERROR);
                  elseif (empty($UserData['user_datebirth'])):
                  $jSON['trigger'] = AjaxErro("<b>ERRO AO GERAR COBRANÇA</b>!<br/> A data de nascimento do usuário principal de \"{$UserData['cli_name']}\" ({$UserData['user_name']} {$UserData['user_lastname']}) deve estar preenchida!<br/><br/><small>* Os dados exigidos devem estar previamente preenchidos. Estes dados são exigidos pelo Gateway para a correta geração do boleto bancário!</small>", E_USER_ERROR);
                  elseif (empty($UserData['user_document'])):
                  $jSON['trigger'] = AjaxErro("<b>ERRO AO GERAR COBRANÇA</b>!<br/> A numeração do CPF do usuário principal de \"{$UserData['cli_name']}\" ({$UserData['user_name']} {$UserData['user_lastname']}) deve estar preenchida!<br/><br/><small>* Os dados exigidos devem estar previamente preenchidos. Estes dados são exigidos pelo Gateway para a correta geração do boleto bancário!</small>", E_USER_ERROR);
                  elseif (empty($UserData['cli_email']) || empty($UserData['cli_telephone'])):
                  $jSON['trigger'] = AjaxErro("<b>ERRO AO GERAR COBRANÇA</b>!<br/> O email e telefone principal do cliente \"{$UserData['cli_name']}\" devem estar preenchidos!<br/><br/><small>* Os dados exigidos devem estar previamente preenchidos. Estes dados são exigidos pelo Gateway para a correta geração do boleto bancário!</small>", E_USER_ERROR);
                  endif;

                  if ($UserData['cli_type'] == 2): // PJ
                  if (empty($UserData['cli_name'])):
                  $jSON['trigger'] = AjaxErro("<b>ERRO AO GERAR COBRANÇA</b>!<br/> Preencha corretamente o nome do Cliente selecionado para continuar!<br/><br/><small>* Os dados exigidos devem estar previamente preenchidos. Estes dados são exigidos pelo Gateway para a correta geração do boleto bancário!</small>", E_USER_ERROR);
                  elseif (empty($UserData['cli_cnpj']) || Check::CNPJ($UserData['cli_cnpj']) != true):
                  $jSON['trigger'] = AjaxErro("<b>ERRO AO GERAR COBRANÇA</b>!<br/> Preencha corretamente o CNPJ do Cliente \"{$UserData['cli_name']}\" para continuar!<br/><br/><small>* Os dados exigidos devem estar previamente preenchidos. Estes dados são exigidos pelo Gateway para a correta geração do boleto bancário!</small>", E_USER_ERROR);
                  endif;
                  endif;
                  endif;

                  if (!empty($jSON['trigger'])):
                  $jSON['showtrigger'] = true;
                  echo json_encode($jSON);
                  return;
                  endif;
                  endif; */

                $_SESSION['bf_finanLanc']['LancData']['fin_recurrence_day'] = !empty($_SESSION['bf_finanLanc']['CourrData']) ? $_SESSION['bf_finanLanc']['CourrData'] : null;

                $Type = $_SESSION['bf_finanLanc']['LancData']['fin_payment_form'];
                if ($Type >= 3):
                    if ($Type == 3): // Quinzenal
                        $Interval = '+15 days';
                    elseif ($Type == 4): // Mensal
                        $Interval = '+1 month';
                    elseif ($Type == 5): // Trimestral
                        $Interval = '+3 months';
                    elseif ($Type == 6): // Semestral
                        $Interval = '+6 months';
                    elseif ($Type == 7): // Anual
                        $Interval = '+1 year';
                    endif;

                    // Cria array de acordo com intervalo
                    for ($P = 0; $P <= 10000; $P++):
                        $OrignData = $_SESSION['bf_finanLanc']['ParcData'][1]['date'];

                        $DTE = ($P == 0) ?
                                $OrignData :
                                (($P > 1) ?
                                date('Y-m-d', strtotime($Interval, strtotime($ParcData[($P - 1)]['date']))) :
                                date('Y-m-d', strtotime($Interval, strtotime($OrignData))) );

                        if (!empty($_SESSION['bf_finanLanc']['ParcData'][1]['price'])):
                            if (strpos($_SESSION['bf_finanLanc']['ParcData'][1]['price'], ",") && strpos($_SESSION['bf_finanLanc']['ParcData'][1]['price'], ".")):
                                $_SESSION['bf_finanLanc']['ParcData'][1]['price'] = str_replace(",", ".", str_replace(".", "", $_SESSION['bf_finanLanc']['ParcData'][1]['price']));
                            elseif (strpos($PostData['fin_value'], ",") && !strpos($PostData['fin_value'], ".")):
                                $_SESSION['bf_finanLanc']['ParcData'][1]['price'] = str_replace(",", ".", $_SESSION['bf_finanLanc']['ParcData'][1]['price']);
                            endif;
                        else:
                            $_SESSION['bf_finanLanc']['ParcData'][1]['price'] = null;
                        endif;

                        $ParcData[$P] = [
                            'date' => $DTE,
                            'price' => $_SESSION['bf_finanLanc']['ParcData'][1]['price'],
                            'method' => $_SESSION['bf_finanLanc']['ParcData'][1]['method'],
                            'id' => 0
                        ];

                        if (date('Y-m', strtotime($DTE)) >= date('Y-m')):
                            $P = 10000;
                        endif;
                    endfor;

                    $_SESSION['bf_finanLanc']['ParcData'] = $ParcData;
                endif;

                $Create->ExeCreate(DB_FINAN, $_SESSION['bf_finanLanc']['LancData']);
                if ($Create->getResult()):
                    $FinanId = $Create->getResult();
                    $FinanData = $_SESSION['bf_finanLanc']['LancData'];
                    $P = 1;

                    foreach ($_SESSION['bf_finanLanc']['ParcData'] as $Splits):
                        // $DTE = !empty($OrignData) ? $Splits['date'] : date("Y-m-d", strtotime(Check::Data($Splits['date'])));
                        $DTE = $Splits['date'];
                        unset($Payment);
                        //Cadastro das Parcelas

                        if (!empty($Splits['price'])):
                            if (strpos($Splits['price'], ",") && strpos($Splits['price'], ".")):
                                $Splits['price'] = str_replace(",", ".", str_replace(".", "", $Splits['price']));
                            elseif (strpos($Splits['price'], ",") && !strpos($Splits['price'], ".")):
                                $Splits['price'] = str_replace(",", ".", $Splits['price']);
                            endif;
                        else:
                            $Splits['price'] = null;
                        endif;

                        $DataInsert = [
                            'fin_id' => $FinanId,
                            'fin_split_price' => $Splits['price'],
                            'fin_split_date' => $DTE,
                            'fin_split_number' => $P,
                            'fin_split_method' => $Splits['method'],
                            'fin_split_status' => 1
                        ];

                        $Create->ExeCreate(DB_FINAN_SPLITS, $DataInsert);
                        $SplitID = $Create->getResult();
                        /*
                          // CRIA O BOLETO NO GATEWAY
                          $ThisFuture = strtotime($DTE) >= strtotime(date('Y-m-d')) ? true : false;
                          if (FINANCE_GTW_STATUS && $FinanData['fin_type'] == 'rec' && $DataInsert['fin_split_method'] == 2 && $ThisFuture):
                          $Payment = new GtwGerencianet();

                          $ClienteDados = array(
                          'name' => $UserData['user_name'] . ' ' . $UserData['user_lastname'],
                          'email' => $UserData['cli_email'],
                          'cpf' => $UserData['user_document'],
                          'phone' => $UserData['cli_telephone'],
                          'birth' => $UserData['user_datebirth']
                          );

                          $ClientJurDados = null;
                          if ($UserData['cli_type'] == 2): // PJ
                          $ClientJurDados = ['jurname' => $UserData['cli_name'], 'cnpj' => $UserData['cli_cnpj']];
                          endif;

                          if ($FinanData['fin_payment_form'] == 1):
                          $Description = $FinanData['fin_title'];
                          elseif ($FinanData['fin_payment_form'] == 2):
                          $Description = "{$P}/{$FinanData['fin_split']} \"{$FinanData['fin_title']}\"";
                          elseif ($FinanData['fin_payment_form'] >= 3):
                          $Description = "\"{$FinanData['fin_title']}\" - parc. {$P}";
                          endif;

                          $CobrancaDados = array(
                          'descricao' => $Description,
                          'valor' => $Splits['price'],
                          'vencimento' => date('Y-m-d', strtotime($DTE))
                          );

                          $Payment->BoletoBancario($ClienteDados, $ClientJurDados, $CobrancaDados, null);
                          if ($Payment->getResult()):
                          $UpdateBillet = [
                          'fin_split_gat' => 2, // getFinanCreateMethod()
                          'fin_split_gat_id' => $Payment->getResult()['data']['charge_id'],
                          'fin_split_billet' => $Payment->getResult()['data']['link'],
                          'fin_split_status' => setFinanGnetStatus($Payment->getResult()['data']['status'])
                          ];
                          $Update->ExeUpdate(DB_FINAN_SPLITS, $UpdateBillet, "WHERE fin_split_id = :id", "id={$SplitID}");
                          endif;
                          endif;
                         */
                        $P++;
                    endforeach;

                    unset($_SESSION['bf_finanLanc']);
                    $jSON['forceclick'] = ['.j_ajaxModalClose', '.j_realtimeReports'];
                endif;
            else:
                $jSON['trigger'] = AjaxErro("Preencha todos os campos para continuar", E_USER_ERROR);
                $jSON['showtrigger'] = true;
            endif;
            break;

        case "realtimeReports":
            $DateIn = $PostData['DateIn'];
            $DateOut = $PostData['DateOut'];
            $LastPeriodIn = $PostData['LastPeriodIn'];
            $LastPeriodOut = $PostData['LastPeriodOut'];

            if (!empty($DateIn) || !empty($DateOut) || !empty($LastPeriodIn) || !empty($LastPeriodOut)):
                /*
                 * ALIMENTA BALANÇO
                 */
                //Total Recebido Geral
                //Recupera total recebidos ate a date de hoje **Ignora o periodo previamente selecionado e os saldos do Mês atual
                $Read->FullRead("SELECT l.fin_id, l.fin_type, p.*, sum(p.fin_split_price) FROM " . DB_FINAN . " l LEFT JOIN " . DB_FINAN_SPLITS . " p ON l.fin_id = p.fin_id WHERE l.fin_type = :type AND p.fin_split_status = :sta AND p.fin_split_date < DATE_SUB(NOW(), INTERVAL 1 Month)", "type=rec&sta=4");
                $jSON['report_BalanceGeralREC'] = $BalanceGeralREC = (!is_null($Read->getResult()[0]['sum(p.fin_split_price)']) ? $Read->getResult()[0]['sum(p.fin_split_price)'] : 0);

                //Total Pago Geral
                //Recupera todos os pagamentos  ate a date de hoje **Ignora o periodo previamente selecionado e os saldos do Mês atual
                $Read->setPlaces("type=des&sta=4");
                $BalanceGeralDES = (!is_null($Read->getResult()[0]['sum(p.fin_split_price)']) ? $Read->getResult()[0]['sum(p.fin_split_price)'] : 0);

                //Obtem o Recebido Last Month
                $Read->FullRead("SELECT l.fin_id, l.fin_type, p.*, sum(p.fin_split_price) FROM " . DB_FINAN . " l LEFT JOIN " . DB_FINAN_SPLITS . " p ON l.fin_id = p.fin_id WHERE l.fin_type = :type AND p.fin_split_status = :sta AND p.fin_split_date BETWEEN :dateIn AND :dateOut", "type=rec&sta=4&dateIn={$LastPeriodIn}&dateOut={$LastPeriodOut}");
                $BalenceLastMonthREC = (!is_null($Read->getResult()[0]['sum(p.fin_split_price)']) ? $Read->getResult()[0]['sum(p.fin_split_price)'] : 0);

                //Obtem Pagamento Last Month    
                $Read->setPlaces("type=des&sta=4&dateIn={$LastPeriodIn}&dateOut={$LastPeriodOut}");
                $BalenceLastMonthDES = (!is_null($Read->getResult()[0]['sum(p.fin_split_price)']) ? $Read->getResult()[0]['sum(p.fin_split_price)'] : 0);

                /*
                 * ALIMENTA GRAFICOS
                 */
                //Obtem o Total a Receber Geral
                $Read->FullRead("SELECT l.fin_id, l.fin_type, p.*, sum(p.fin_split_price) FROM " . DB_FINAN . " l LEFT JOIN " . DB_FINAN_SPLITS . " p ON l.fin_id = p.fin_id WHERE l.fin_type = :type AND p.fin_split_date BETWEEN :dateIn AND :dateOut ORDER BY fin_split_date ASC", "type=rec&dateIn={$DateIn}&dateOut={$DateOut}");
                $TotalREC = $Read->getResult()[0]['sum(p.fin_split_price)'];

                //Obtem o total de despesas Geral
                $Read->setPlaces("type=des&dateIn={$DateIn}&dateOut={$DateOut}");
                $TotalDES = $Read->getResult()[0]['sum(p.fin_split_price)'];

                //Obtem o Total Recebido
                $Read->FullRead("SELECT l.fin_id, l.fin_type, p.*, sum(p.fin_split_price) FROM " . DB_FINAN . " l LEFT JOIN " . DB_FINAN_SPLITS . " p ON l.fin_id = p.fin_id WHERE l.fin_type = :type AND p.fin_split_status = :sta AND p.fin_split_date BETWEEN :dateIn AND :dateOut ORDER BY fin_split_date ASC", "type=rec&sta=4&dateIn={$DateIn}&dateOut={$DateOut}");
                $PayTotalREC = $Read->getResult()[0]['sum(p.fin_split_price)'];

                //Obtem o total Pago    
                $Read->setPlaces("type=des&sta=4&dateIn={$DateIn}&dateOut={$DateOut}");
                $PayTotalDES = $Read->getResult()[0]['sum(p.fin_split_price)'];

                //Obtem o total de recebiveis de vencem hoje
                $Read->FullRead("SELECT l.fin_id, l.fin_type, p.*, sum(p.fin_split_price) FROM " . DB_FINAN . " l LEFT JOIN " . DB_FINAN_SPLITS . " p ON l.fin_id = p.fin_id WHERE l.fin_type = :type AND p.fin_split_date BETWEEN :dateIn AND :dateOut ORDER BY fin_split_date ASC", "type=rec&dateIn=" . date("Y-m-d") . "&dateOut=" . date("Y-m-d"));
                $TodayTotalREC = $Read->getResult()[0]['sum(p.fin_split_price)'];

                //Obtem total de despesas de que vencem hoje   
                $Read->setPlaces("type=des&dateIn=" . date("Y-m-d") . "&dateOut=" . date("Y-m-d"));
                $TodayTotalDES = $Read->getResult()[0]['sum(p.fin_split_price)'];

                //Balanço do Mês
                $BalanceMonth = $PayTotalREC - $PayTotalDES;

                //OUTPUT
                $jSON['success'] = true;
                //OUTPUT:::BalançoGeral
                $jSON['divcontent']['.j_realtime_balancegeral'] = "R$ " . number_format($BalanceGeralREC - $BalanceGeralDES, 2, ",", "."); //.j_realtime_balancegeral
                $jSON['divcontent']['.j_realtime_balencelastmonth'] = "R$ " . number_format($BalenceLastMonthREC - $BalenceLastMonthDES, 2, ",", "."); //.j_realtime_balencelastmonth
                $jSON['divcontent']['.j_realtime_balencegeralparcial'] = "R$ " . number_format(($BalanceGeralREC - $BalanceGeralDES) + $BalanceMonth, 2, ",", "."); //.j_realtime_balencegeralparcial
                //OUTPUT:::TotalRecebido
                $jSON['divcontent']['.j_realtime_chartRec'] = $PayTotalREC . "/" . $TotalREC; //.j_realtime_chartRec                
                $jSON['divcontent']['.j_realtime_todaytotalrec'] = "R$ " . number_format($TodayTotalREC, 2, ",", "."); //.j_realtime_todaytotalrec                
                $jSON['divcontent']['.j_realtime_paytotalrec'] = "R$ " . number_format($PayTotalREC, 2, ",", "."); //.j_realtime_paytotalrec
                $jSON['divcontent']['.j_realtime_totalrec'] = "R$ " . number_format($TotalREC, 2, ",", "."); //.j_realtime_totalrec
                //OUTPUT:::TotalPago
                $jSON['divcontent']['.j_realtime_chartDes'] = $PayTotalDES . "/" . $TotalDES; //.j_realtime_chartDes
                $jSON['divcontent']['.j_realtime_todaytotaldes'] = "R$ " . number_format($TodayTotalDES, 2, ",", "."); //.j_realtime_todaytotaldes
                $jSON['divcontent']['.j_realtime_paytotaldes'] = "R$ " . number_format($PayTotalDES, 2, ",", "."); //.j_realtime_paytotaldes
                $jSON['divcontent']['.j_realtime_totaldes'] = "R$ " . number_format($TotalDES, 2, ",", "."); //.j_realtime_totaldes
                //OUTPUT:::BalançoMês
                $jSON['divcontent']['.j_realtime_balancemonth'] = "R$ " . number_format($BalanceMonth, 2, ",", "."); //.j_realtime_balancemonth
                $jSON['divcontent']['.j_realtime_balancemonthsta'] = ($BalanceMonth >= 0 ? "<b>Saldo Positivo</b>" : "<b style='color:red;'>Saldo Negativo</b>"); //.j_realtime_balancemonthsta
            else:
                $jSON['success'] = false;
            endif;
            break;

        case "realtimeLanc": // ok 
            $DateIn = $PostData['DateIn'];
            $DateOut = $PostData['DateOut'];
            $jSON['divcontent']['.j_realtime_allLanc'] = null;

            if (!empty($PostData['Type'])):
                $WHTYPE[0] = "AND fin_type = :tp";
                $WHTYPE[1] = "&tp={$PostData['Type']}";
            else:
                $WHTYPE[0] = "";
                $WHTYPE[1] = "";
            endif;

            if (!empty($DateIn) || !empty($DateOut)):
                $jSON['success'] = true;

                $Read->FullRead("SELECT l.fin_id, l.fin_id AS id, l.fin_title, l.user_id, l.cli_id, l.category_id, l.fin_split, l.fin_author, l.fin_type, l.fin_payment_form, p.*, c.category_title, c.category_parent, u.user_name, u.user_lastname FROM " . DB_FINAN . " l LEFT JOIN " . DB_FINAN_SPLITS . " p ON l.fin_id = p.fin_id LEFT JOIN " . DB_FINAN_CAT . " c ON l.category_id = c.category_id LEFT JOIN " . DB_USERS . " u ON l.user_id = u.user_id WHERE p.fin_split_date BETWEEN :dateIn AND :dateOut {$WHTYPE[0]} GROUP BY p.fin_split_id ORDER BY fin_split_date ASC, l.fin_payment_form ASC", "dateIn={$DateIn}&dateOut={$DateOut}{$WHTYPE[1]}");

                if (!$Read->getResult()):
                    $jSON['divcontent']['.j_realtime_allLanc'] .= "<tr><th colspan='7'>Não há registros para este mês</th></tr>";
                else:
                    foreach ($Read->getResult() as $Release):
                        $Status = (date("Y-m-d", strtotime($Release['fin_split_date'])) <= date("Y-m-d") && $Release['fin_split_status'] <= 3 ? "yellow" : ($Release['fin_split_status'] == 4 ? "green" : ($Release['fin_split_status'] == 5 ? "red" : null)));

                        //Category Pai
                        $Read->FullRead("SELECT category_title FROM " . DB_FINAN_CAT . " WHERE category_id = :parent", "parent={$Release['category_parent']}");
                        $CatParent = $Read->getResult()[0];

                        //Client Data
                        $UserData = '<a>--</a>';
                        if (!empty($Release['cli_id'])):
                            $Read->ExeRead(DB_CLIENTS, "WHERE cli_id = :cli", "cli={$Release['cli_id']}");
                            if ($Read->getResult()):
                                $Client = $Read->getResult()[0];
                                if ((!empty($Client['cli_type']) && $Client['cli_type'] == 1)):
                                    $Read->FullRead("SELECT u.user_name, u.user_lastname, u.user_id FROM " . DB_CLIENTS_MEMBERS . " m INNER JOIN " . DB_USERS . " u ON u.user_id = m.user_id WHERE m.responsibility = 1 AND m.cli_id = :id", "id={$Client['cli_id']}");
                                    if (!$Read->getResult()):
                                        $Name = "Usuario não encontrado ({$Client['cli_surname']})";
                                    else:
                                        $Name = "{$Read->getResult()[0]['user_name']} {$Read->getResult()[0]['user_lastname']} ({$Client['cli_surname']})";
                                    endif;
                                else:
                                    $Client['cli_name'] = ($Client['cli_name'] ? $Client['cli_name'] : '--');
                                    $Client['cli_surname'] = ($Client['cli_surname'] ? $Client['cli_surname'] : 'Cliente sem nome');
                                    $Name = "{$Client['cli_surname']} ({$Client['cli_name']})";
                                endif;
                                $UserData = "<a title='Acessar Cliente' target='_blank' href='dashboard.php?wc=clients/update-clients&id={$Client['cli_id']}'>{$Name}</a>";
                            endif;
                        elseif (!empty($Release['user_id'])):
                            $Read->FullRead("SELECT user_id, user_name, user_lastname FROM " . DB_USERS . " WHERE user_id = :id", "id={$Release['user_id']}");
                            if ($Read->getResult()):
                                $UserData = "<a title='Acessar Perfil do Cliente' href='dashboard.php?wc=users/create&id={$Read->getResult()[0]['user_id']}'>{$Read->getResult()[0]['user_name']} {$Read->getResult()[0]['user_lastname']}</a>";
                            endif;
                        endif;

                        //Admin Data
                        $AdminData = null;
                        if (!empty($Release['fin_author'])):
                            $Read->FullRead("SELECT user_name FROM " . DB_USERS . " WHERE user_id = :id", "id={$Release['fin_author']}");
                            $AdminData = $Read->getResult()[0];
                        endif;

                        $jSON['divcontent']['.j_realtime_allLanc'] .= "<tr class='fin_single {$Release['fin_type']}' id='{$Release['fin_id']}'>
                                    <th>Lançado por {$AdminData['user_name']} para<br>" . (!empty($Release['fin_split_date']) ? date("d/m/Y", strtotime($Release['fin_split_date'])) : date("d/m/Y", strtotime($Release['fin_due_date']))) . "</th>
                                    <th>{$UserData}</th>
                                    <th><p>{$Release['fin_title']}</p>" . ($Release['fin_split'] > 1 ? "<span class='splits'>Parcela {$Release['fin_split_number']}/{$Release['fin_split']}</span>" : (($Release['fin_payment_form'] > 2) ? "<span class='splits' style='text-transform:uppercase;'><b>" . getFinanPaymentsForms($Release['fin_payment_form']) . "</b></span>" : null)) . "</th>
                                    <th><b>{$CatParent['category_title']}</b><br>{$Release['category_title']}</th>
                                    <th><select class='form--status {$Status}' " . ($Release['fin_split_status'] >= 4 ? "disabled" : null) . " id='{$Release['fin_split_id']}'>";

                        foreach (getFinanPaymentsStatus() as $Key => $Value):
                            if ($Release['fin_split_status'] <> 2 || $Release['fin_split_status'] <> 3 AND $Key == 1 || $Key >= 4):
                                $jSON['divcontent']['.j_realtime_allLanc'] .= "<option value='$Key' " . ($Release['fin_split_status'] == $Key ? "selected='selected'" : null) . ">{$Value}</option>";
                            elseif ($Release['fin_split_status'] == 2 || $Release['fin_split_status'] == 3):
                                $jSON['divcontent']['.j_realtime_allLanc'] .= "<option value='$Key' " . ($Release['fin_split_status'] == $Key ? "selected='selected'" : null) . ">{$Value}</option>";
                            endif;
                        endforeach;

                        $jSON['divcontent']['.j_realtime_allLanc'] .= "</select></th>
                                <th class='" . ($Release['fin_type'] == "rec" ? "receita" : "despesa") . "'><b>R$ " . (!empty($Release['fin_split_price']) ? number_format($Release['fin_split_price'], 2, ",", ".") : number_format($Release['fin_value'], 2, ",", ".")) . "</b></th>
                                <th>
                                    <span class='btn btn_blue btn_small j_ajaxModal' callback='Finance' callback_action='finanDetails' callback_id='{$Release['fin_split_id']}'><b>VER</b></span>
                                </th>
                                </tr>";
                    endforeach;
                endif;
            else:
                $jSON['success'] = false;
            endif;
            break;

        case 'finanSplitDelete':
            $Split = $PostData['id'];

            $Read->ExeRead(DB_FINAN_SPLITS, "WHERE fin_split_id = :id", "id={$Split}");
            if (!$Read->getResult()):
                $jSON['trigger'] = AjaxErro("OOPS! Desculpe mas conseguimos encontrar o lançamento que deseja deletar!", E_USER_ERROR);
            else:
                $SplitVoucher = "../../uploads/{$Read->getResult()[0]['fin_split_voucher']}";
                if (file_exists($SplitVoucher) && !is_dir($SplitVoucher)):
                    unlink($SplitVoucher);
                endif;

                if (FINANCE_GTW_STATUS && !empty($Read->getResult()[0]['fin_split_billet'])):
                    if (getFinanCreateMethod($Read->getResult()[0]['fin_split_gat']) == 'Gerencianet' && !empty($Read->getResult()[0]['fin_split_gat_id'])):
                        $Payment = new GtwGerencianet();

                        $Payment->Cancel($Read->getResult()[0]['fin_split_gat_id']);
                        if (!$Payment->getResult()):
                            $jSON['error'] = "<b>ERRO AO DELETAR BOLETO:</b><br/> Erro na comunicação com gateway!";
                        endif;
                    endif;
                endif;

                $Delete->ExeDelete(DB_FINAN_SPLITS, "WHERE fin_split_id = :id", "id={$Split}");
                if ($Delete->getResult()):
                    $jSON['forceclick'] = ['.j_realtimeReports', '.j_ajaxModalClose'];
                    $jSON['success'] = "Lançamento deletado com sucesso!";
                endif;
            endif;
            break;

        case 'finanDelete':
            $Finan = $PostData['id'];

            $Read->ExeRead(DB_FINAN_SPLITS, "WHERE fin_id = :id", "id={$Finan}");

            if ($Read->getResult()):
                foreach ($Read->getResult() as $Splits):
                    $SplitVoucher = "../../uploads/{$Splits['fin_split_voucher']}";
                    if (file_exists($SplitVoucher) && !is_dir($SplitVoucher)):
                        unlink($SplitVoucher);
                    endif;

                    if (FINANCE_GTW_STATUS && !empty($Splits['fin_split_billet'])):
                        if (getFinanCreateMethod($Splits['fin_split_gat']) == 'Gerencianet' && !empty($Splits['fin_split_gat_id'])):
                            $Payment = new GtwGerencianet();

                            $Payment->Cancel($Splits['fin_split_gat_id']);
                            if (!$Payment->getResult()):
                                $jSON['error'] = "<b>ERRO AO DELETAR BOLETO:</b><br/> Erro na comunicação com gateway!";
                            endif;
                        endif;
                    endif;
                endforeach;

                $Delete->ExeDelete(DB_FINAN_SPLITS, "WHERE fin_id = :id", "id={$Finan}");
                $Delete->ExeDelete(DB_FINAN, "WHERE fin_id = :id", "id={$Finan}");
                if ($Delete->getResult()):
                    $jSON['forceclick'] = ['.j_realtimeReports', '.j_ajaxModalClose'];
                    $jSON['success'] = "Lançamento deletado com sucesso!";
                endif;
            endif;

            break;

        case 'finanInative':
            $FinId = $PostData['id'];
            $Fin['fin_status'] = 0;

            $Update->ExeUpdate(DB_FINAN, $Fin, "WHERE fin_id = :id", "id={$FinId}");
            if ($Update->getResult()):
                $jSON['success'] = "Recorrência desativada com sucesso!<br/>A partir de agora os novos lançamentos que seriam registrados para essa recorrência não serão mais lançados automaticamente!";
                $jSON['forceclick'] = ['.j_ajaxModalClose'];
            endif;
            break;

        case 'finanDetails':
            $Split = $PostData['id'];
            $Read->ExeRead(DB_FINAN_SPLITS, "WHERE fin_split_id = :spl", "spl={$Split}");
            $ThisSplit = $Read->getResult()[0];

            if (!$ThisSplit):
                $jSON['trigger'] = AjaxErro("<b>ERRO!</b> O registro que você quer editar não existe ou foi removido do sistema!", E_USER_ERROR);
            else:
                $Read->ExeRead(DB_FINAN, "WHERE fin_id = :id", "id={$ThisSplit['fin_id']}");
                $ThisFinan = $Read->getResult()[0];

                // GET USER
                $UserData = '<span class="fin-option--link">Nenhum cliente</span>';

                if (!empty($ThisFinan['cli_id'])):
                    $Read->ExeRead(DB_CLIENTS, "WHERE cli_id = :cli", "cli={$ThisFinan['cli_id']}");
                    if ($Read->getResult()):
                        $Client = $Read->getResult()[0];
                        if ((!empty($Client['cli_type']) && $Client['cli_type'] == 1)):
                            $Read->FullRead("SELECT u.user_name, u.user_lastname, u.user_id FROM " . DB_CLIENTS_MEMBERS . " m INNER JOIN " . DB_USERS . " u ON u.user_id = m.user_id WHERE m.responsibility = 1 AND m.cli_id = :id", "id={$Client['cli_id']}");
                            if (!$Read->getResult()):
                                $Name = "Usuario não encontrado ({$Client['cli_surname']})";
                            else:
                                $Name = "{$Read->getResult()[0]['user_name']} {$Read->getResult()[0]['user_lastname']} ({$Client['cli_surname']})";
                            endif;
                        else:
                            $Client['cli_name'] = ($Client['cli_name'] ? $Client['cli_name'] : '--');
                            $Client['cli_surname'] = ($Client['cli_surname'] ? $Client['cli_surname'] : 'Cliente sem nome');
                            $Name = "{$Client['cli_surname']} ({$Client['cli_name']})";
                        endif;
                        $UserData = "<a href='dashboard.php?wc=clients/update-clients&id={$Client['cli_id']}' target='_blank' class='fin-option--link'>{$Name}</a>";
                    endif;
                elseif (!empty($ThisFinan['user_id'])):
                    $Read->FullRead("SELECT user_id, user_name, user_lastname FROM " . DB_USERS . " WHERE user_id = :id", "id={$ThisFinan['user_id']}");
                    if ($Read->getResult()):
                        $UserData = "<a target='_blank' class='fin-option--link' href='dashboard.php?wc=" . (FINANCE_MODULE_CLIENTS ? 'clients/update-users&pid=' : 'users/create&id=') . "{$Read->getResult()[0]['user_id']}'>{$Read->getResult()[0]['user_name']} {$Read->getResult()[0]['user_lastname']}</a>";
                    endif;
                endif;

                // GET CATEGORY
                $Read->FullRead("SELECT category_title FROM " . DB_FINAN_CAT . " WHERE category_id = :cat", "cat={$ThisFinan['category_id']}");
                $Category = $Read->getResult()[0]['category_title'];

                // PRICE VALUE
                $Value = explode('.', $ThisSplit['fin_split_price']);

                // GET SPLIT
                $Read->FullRead("SELECT "
                        . "(SELECT COUNT(fin_split_id) FROM " . DB_FINAN_SPLITS . " WHERE fin_id = :fin) AS total, "
                        . "(SELECT COUNT(fin_split_id) FROM " . DB_FINAN_SPLITS . " WHERE fin_id = :fin AND fin_split_date < :date) AS atual "
                        . "FROM " . DB_FINAN_SPLITS . " spl "
                        . "WHERE spl.fin_split_id = :split", "fin={$ThisSplit['fin_id']}&split={$ThisSplit['fin_split_id']}&date={$ThisSplit['fin_split_date']}"
                );
                $Total = $Read->getResult()[0]['total'];
                $Atual = $Read->getResult()[0]['atual'] + 1;
                $ModalTitle = "{$Atual}/{$Total}";

                // SET STATUS
                $Status = null;
                if ($ThisSplit['fin_split_status'] == 4):
                    $Status = 'paid';
                elseif ($ThisSplit['fin_split_status'] == 5):
                    $Status = 'canceled';
                elseif (strtotime(date('Y-m-d', strtotime("-1 day"))) >= strtotime($ThisSplit['fin_split_date'])):
                    $Status = 'late';
                endif;

                $Content = ''
                        . "<div class='fin-details'>"
                        . "<div class='fin-details--head'>"
                        . "<div class='fin-details--options'>"
                        . "<label>"
                        . "<span>Cliente ou Fornecedor</span> {$UserData}"
                        . "</label>"
                        . "<label>"
                        . "<span>Categoria</span>"
                        . "<p class='fin-option--link' title='{$Category}'>{$Category}</p>"
                        . "</label>"
                        . "<label>"
                        . "<a href='#0' class='btn btn_blue btn_small j_register_action'>GERENCIAR REGISTRO</a> "
                        . "</label>"
                        . "</div>"
                        . "<div class='fin-details--price'>"
                        . "<p class='{$ThisFinan['fin_type']}'>"
                        . "<span class='type'>" . ($ThisFinan['fin_type'] == 'rec' ? 'receita' : 'despesa') . "</span>R$"
                        . "<span class='price'>" . (number_format($Value[0], 0, ',', '.')) . ",<small>{$Value[1]}</small>" . (!empty($ThisFinan['fin_payment_form']) ? "<small class='recurrence'>/ " . getFinanPaymentsForms($ThisFinan['fin_payment_form']) . "</small>" : '') . "</span>"
                        . "</p>"
                        . "<div class='status'>" . ($ThisFinan['fin_payment_form'] >= 3 ? ($ThisFinan['fin_status'] == 1 ? '<span class="icon-checkmark j_swal_action" callback="Finance" callback_action="finanInative" data-confirm-text="Desativar Lançamento #' . $ThisFinan['fin_id'] . ' ?" data-confirm-message="Ao desativar este registro o sistema não irá mais lançar automaticamente as parcelas sequentes a partir de agora! Tem certeza dessa ação?" id="' . $ThisFinan['fin_id'] . '">ATIVO</span>' : '<span class="icon-bin font_red j_swal_action" callback="Finance" callback_action="finanDelete" data-confirm-text="Deletar Registro ?" data-confirm-message="Ao deletar esse registro você irá deletar todas as parcelas anteriores e as sequentes e seus respectivos comprovantes!  Tem certeza dessa ação?" id="' . $ThisFinan['fin_id'] . '">INATIVO</span>') : '<span class="icon-bin icon-notext font_red j_swal_action" callback="Finance" callback_action="finanDelete" data-confirm-text="Deletar Registro ?" data-confirm-message="Ao deletar esse registro você irá deletar todas as parcelas anteriores, as sequentes e seus respectivos comprovantes (e boletos se houverem)! Tem certeza dessa ação?" id="' . $ThisFinan['fin_id'] . '"></span>') . "</div>"
                        . (!empty($ThisSplit['fin_split_billet']) ? "<a href='{$ThisSplit['fin_split_billet']}' target='_blank' class='btn btn_green'>ACESSAR BOLETO</a>" : '')
                        . "</div>"
                        . "</div>"

                        // EDIÇÃO DE DADOS GERAIS DO LANCAMENTO
                        . '<div class="fin-details--splits no-title">'
                        . "<div class='split-edit main-edit'>";
                if ($ThisFinan['fin_payment_form'] <= 3):
                    $Content .= ''
                            . '<div class="trigger trigger_ajax trigger_error" style="display: block;">
                                    Você pode editar os dados de um lançamento ' . (getFinanPaymentsForms($ThisFinan['fin_payment_form'])) . ' clicando no botão <i class="icon-pencil"></i> (lápis) da parcela atual, ou das parcelas anteriores
                                </div>';
                else:
                    $Content .= ''
                            . "<form class='ajax_off' autocomplete='off' name='finanGlobal' action='' method='post' enctype='multipart/form-data'>"
                            . "<input type='hidden' name='callback' value='Finance'/>"
                            . "<input type='hidden' name='callback_action' value='finanManagerMain'/>"
                            . "<input type='hidden' name='fin_id' value='{$ThisFinan['fin_id']}'/> "
                            . "<input type='hidden' name='split' value='{$ThisSplit['fin_split_id']}|{$ThisSplit['fin_split_date']}'/> "
                            . "<div class='split'>"
                            . "<p><label>"
                            . "<span>Categoria</span>"
                            . "<select class='j-custom-select custom-select' name='category_id'>"
                            . "<option value='' disabled='disabled' selected='selected'>Selecione uma Categoria</option>";
                    $Read->fullRead("SELECT category_id, category_title FROM " . DB_FINAN_CAT . " WHERE category_parent IS NULL AND category_type = :type AND category_status = :sta", "type={$ThisFinan['fin_type']}&sta=1");
                    if ($Read->getResult()):
                        foreach ($Read->getResult() as $CatPai):
                            $Content .= "<optgroup label='{$CatPai['category_title']}'>";
                            $Read->fullRead("SELECT category_id, category_title FROM " . DB_FINAN_CAT . " WHERE category_parent = :cat", "cat={$CatPai['category_id']}");
                            if (!$Read->getResult()):
                                $Content .= "<option disable='disable'>Nenhuma sub-categoria encontrada</option>";
                            else:
                                foreach ($Read->getResult() as $Cat):
                                    $Content .= "<option value='{$Cat['category_id']}' " . ($ThisFinan['category_id'] == $Cat['category_id'] ? 'selected' : '') . ">{$Cat['category_title']}</option>";
                                endforeach;
                            endif;
                            $Content .= "</optgroup>";
                        endforeach;
                    endif;
                    $Content .= "</select>"
                            . "</label></p>"
                            . "<p><label>"
                            . "<span>Cliente</span>";

                    if (FINANCE_MODULE_CLIENTS):
                        $Content .= ''
                                . "<select name='cli_id'>"
                                . '<option disabled selected>Selecione o cliente</option>';
                        $Read->ExeRead(DB_CLIENTS, "WHERE 1 = 1 ORDER BY cli_surname ASC, cli_name ASC");
                        if (!$Read->getResult()):
                            $Content .= '<option value="" disabled selected>Cadastre clientes antes!</option>';
                        else:
                            foreach ($Read->getResult() as $Client):
                                if ((!empty($Client['cli_type']) && $Client['cli_type'] == 1)):
                                    $Read->FullRead("SELECT u.user_name, u.user_lastname FROM " . DB_CLIENTS_MEMBERS . " m INNER JOIN " . DB_USERS . " u ON u.user_id = m.user_id WHERE m.responsibility = 1 AND m.cli_id = :id", "id={$Client['cli_id']}");
                                    if (!$Read->getResult()):
                                        $Name = "Usuario não encontrado ({$Client['cli_surname']})";
                                    else:
                                        $Name = "{$Read->getResult()[0]['user_name']} {$Read->getResult()[0]['user_lastname']} ({$Client['cli_surname']})";
                                    endif;
                                else:
                                    $Client['cli_name'] = ($Client['cli_name'] ? $Client['cli_name'] : '--');
                                    $Client['cli_surname'] = ($Client['cli_surname'] ? $Client['cli_surname'] : 'Cliente sem nome');
                                    $Name = "{$Client['cli_surname']} ({$Client['cli_name']})";
                                endif;
                                $Content .= "<option value='{$Client['cli_id']}' " . ($Client['cli_id'] == $ThisFinan['cli_id'] ? 'selected' : '') . ">{$Name}</option>";
                            endforeach;
                        endif;
                        $Content .= "</select>";
                    else:
                        $Content .= ''
                                . "<select name='user_id'>"
                                . "<option value='' disabled='disabled' selected='selected'>Selecione um Usuário</option>";
                        // USUARIOS
                        $Read->fullRead("SELECT user_id, user_name, user_lastname FROM " . DB_USERS . " WHERE 1=1");
                        if (!$Read->getResult()):
                            $Content .= '<option value="" disabled selected>Cadastre usuários antes!</option>';
                        else:
                            foreach ($Read->getResult() as $Client):
                                $Content .= "<option value='{$Client['user_id']}' " . ($Client['user_id'] == $ThisFinan['user_id'] ? 'selected' : '') . ">{$Client['user_name']} {$Client['user_lastname']}</option>";
                            endforeach;
                        endif;
                        $Content .= "</select>";
                    endif;
                    $Content .= ''
                            . "</label></p>"
                            . "<p><label>"
                            . "<span>Primeiro Pagamento</span>"
                            . "<input type='text' class='formDate' disabled value='" . (date('d/m/Y', strtotime($ThisFinan['fin_due_date']))) . "'/>"
                            . "</label></p>"
                            . "<p><label>"
                            . "<span>Valor</span>"
                            . "<input type='text' class='money-input' disabled value='" . (number_format($ThisFinan['fin_value'], 2, ",", ".")) . "'/>"
                            . "</label></p>"
                            . "<p><label>"
                            . "<span>Método Selecionado</span>"
                            . "<select class='j_splitsManager' disabled>
                                                <option disabled selected>" . (getFinanPaymentsMethods($ThisSplit['fin_split_method'])) . "</option>"
                            . "</select>"
                            . "</label></p>"
                            . "<p class='actions'><label>"
                            . "<span>&nbsp;</span>"
                            . "<button type='submit' class=' btn btn_green'>ATUALIZAR</button>"
                            . ($ThisFinan['fin_payment_form'] >= 3 ? ($ThisFinan['fin_status'] == 1 ? '<button type="button" class="btn btn_yellow j_swal_action" callback="Finance" callback_action="finanInative" data-confirm-text="Desativar Lançamento #' . $ThisFinan['fin_id'] . ' ?" data-confirm-message="Ao desativar este registro o sistema não irá mais lançar automaticamente as parcelas sequentes a partir de agora! Tem certeza dessa ação?" id="' . $ThisFinan['fin_id'] . '">DESATIVAR</button>' : '<button type="button" class="icon-bin btn btn_red j_swal_action" callback="Finance" callback_action="finanDelete" data-confirm-text="Deletar Registro ?" data-confirm-message="Ao deletar esse registro você irá deletar todas as parcelas anteriores e as sequentes e seus respectivos comprovantes!  Tem certeza dessa ação?" id="' . $ThisFinan['fin_id'] . '">DELETAR</button>') : '<button type="button" class="btn btn_red icon-bin j_swal_action" callback="Finance" callback_action="finanDelete" data-confirm-text="Deletar Registro ?" data-confirm-message="Ao deletar esse registro você irá deletar todas as parcelas anteriores, as sequentes e seus respectivos comprovantes (e boletos se houverem)! Tem certeza dessa ação?" id="' . $ThisFinan['fin_id'] . '">DELETAR</button>')
                            . "<img class='form_load none' style='margin-left: 10px;' alt='Enviando Requisição!' title='Enviando Requisição!' src='_img/load_w.gif'/>"
                            . "</label></p>"
                            . "</div>"
                            . '
                                    <div class="trigger trigger_ajax trigger_error" style="display: block;">
                                        Dados como data de vencimento, valor e método de pagamento não são editáveis. Caso as condições mudem, desative este lançamento e crie outro com as novas condições
                                    </div>'
                            . "</form>";
                endif;
                $Content .= ''
                        . "</div>"
                        . "</div>"

                        // DETALHES DO LANÇAMENTO SELECIONADO
                        . "<div class='fin-details--splits'>"
                        . "<div class='split {$Status}'>"
                        . "<p>{$Atual}/{$Total}</p>"
                        . "<p>" . (date('d/m/Y', strtotime($ThisSplit['fin_split_date']))) . "</p>"
                        . "<p>R$ " . (number_format($Value[0], 0, ',', '.')) . ",{$Value[1]}</p>"
                        . "<p>"
                        . "<select class='j-custom-select form--status custom-select' name='client_id' id='{$ThisSplit['fin_split_id']}'>";
                foreach (getFinanPaymentsStatus() as $Key => $Value):
                    $Content .= "<option value='{$Key}' " . ($Key == $ThisSplit['fin_split_status'] ? 'selected' : '') . ">{$Value}</option>";
                endforeach;
                $Content .= "</select>"
                        . "</p>"
                        . "<p class='actions'>"
                        . "<a href='javascript:void(0)' class='icon-notext j_voucher-view icon-search' href='' target='_blank' voucher='{$ThisSplit['fin_split_id']}' title='Ver comprovante'></a>"
                        . "<a href='javascript:void(0)' class='icon-notext j_fin_action icon-file-zip' data-class='send-voucher' title='Enviar comprovante' data-title='Enviar comprovante'></a>"
                        . "<a href='javascript:void(0)' class='icon-notext j_fin_action icon-pencil' data-class='in-edition' title='Editar dados' data-title='Editar dados'></a>"
                        . "<a href='javascript:void(0)' class='icon-notext icon-cancel-circle j_swal_action' callback='Finance' callback_action='finanSplitDelete' data-confirm-text='Deletar Parcela ?' data-confirm-message='Ao deletar essa parcela o comprovante " . ($ThisSplit['fin_split_method'] == 2 ? 'será deletado e o boleto será cancelado' : 'será deletado') . "! Tem certeza?' id='{$ThisSplit['fin_split_id']}' title='Deletar Registro'></a>"
                        . "</p>"

                        // COMPROVANTE DE PAGAMENTO
                        . "<div class='split-payment-voucher'>"
                        . "<form class='' name='finanGlobal' action='' method='post' enctype='multipart/form-data'>"
                        . "<input type='hidden' name='callback' value='Finance'/>"
                        . "<input type='hidden' name='callback_action' value='finanVoucher'/>"
                        . "<input type='hidden' name='fin_split_id' value='{$ThisSplit['fin_split_id']}'/> "
                        . "<div class='input-file mini'>"
                        . "<label for='flthumb' class='icon-image'>Enviar Recibo (.pdf .png .jpg)</label>"
                        . "<input id='flthumb' type='file' name='fin_split_voucher'/>"
                        . "</div>"
                        . "<button type='submit' class='voucher-send btn btn_green button_opacity'>ENVIAR</button>"
                        . "<span class='form_load'></span>"
                        . "</form>"
                        . "</div>"

                        // EDIÇÃO DE DADOS
                        . "<div class='split-edit'>"
                        . "<form class='ajax_off' autocomplete='off' name='finanGlobal' action='' method='post' enctype='multipart/form-data'>"
                        . "<input type='hidden' name='callback' value='Finance'/>"
                        . "<input type='hidden' name='callback_action' value='finanManager'/>"
                        . "<input type='hidden' name='fin_split_id' value='{$ThisSplit['fin_split_id']}'/> "
                        . "<div class='split'>"
                        . "<p><label>"
                        . "<span>Data de Vencimento</span>"
                        . "<input type='text' class='formDate' name='fin_split_date' value='" . (date('d/m/Y', strtotime($ThisSplit['fin_split_date']))) . "'/>"
                        . "</label></p>"
                        . "<p><label>"
                        . "<span>Data da Quitação</span>"
                        . "<input type='text' class='formDate' name='fin_split_date_pay' value='" . (empty($ThisSplit['fin_split_date_pay']) ? null : date('d/m/Y', strtotime($ThisSplit['fin_split_date_pay']))) . "'/>"
                        . "</label></p>"
                        . "<p><label>"
                        . "<span>Valor</span>"
                        . "<input type='text' class='money-input' name='fin_split_price' value='" . (number_format($ThisSplit['fin_split_price'], 2, ",", ".")) . "'/>"
                        . "</label></p>"
                        . "<p><label>"
                        . "<span>Método de Pgto.</span>"
                        . "<select class='j_splitsManager' name='fin_split_method'>
                                                <option value='' disabled='disabled' selected='selected'>Selecione um Método</option>";
                if (getFinanPaymentsMethods()):
                    foreach (getFinanPaymentsMethods() as $Id => $Methods):
                        $Content .= "<option value='{$Id}' " . ($ThisSplit['fin_split_method'] == $Id ? 'selected' : '') . ">{$Methods}</option>";
                    endforeach;
                endif;
                $Content .= "</select>"
                        . "</label></p>"
                        . "<p class='actions'><label>"
                        . "<span>&nbsp;</span>"
                        . "<button type='submit' class=' btn btn_green'>ATUALIZAR</button>"
                        . "<img class='form_load none' style='margin-left: 10px;' alt='Enviando Requisição!' title='Enviando Requisição!' src='_img/load_w.gif'/>"
                        . "</label></p>"
                        . "</div>"
                        . "</form>"
                        . "</div>"
                        . "</div>"
                        . "</div>";

                // PARCELAS ANTERIORES
                $Read->ExeRead(DB_FINAN_SPLITS, "WHERE fin_id = :fin AND fin_split_date < :date ORDER BY fin_split_date DESC", "fin={$ThisSplit['fin_id']}&date={$ThisSplit['fin_split_date']}");
                if ($Read->getResult()):
                    $Content .= "<div class='fin-details--splits more'>";
                    foreach ($Read->getResult() as $ThisSplit):

                        // PRICE VALUE
                        $Value = explode('.', $ThisSplit['fin_split_price']);

                        // GET SPLIT
                        $Read->FullRead("SELECT "
                                . "(SELECT COUNT(fin_split_id) FROM " . DB_FINAN_SPLITS . " WHERE fin_id = :fin AND fin_split_date < :date) AS atual "
                                . "FROM " . DB_FINAN_SPLITS . " spl "
                                . "WHERE spl.fin_split_id = :split", "fin={$ThisSplit['fin_id']}&split={$ThisSplit['fin_split_id']}&date={$ThisSplit['fin_split_date']}"
                        );
                        $Atual = $Read->getResult()[0]['atual'] + 1;

                        // SET STATUS
                        $ThisSplit['fin_split_status'] = !empty($ThisSplit['fin_split_status']) ? $ThisSplit['fin_split_status'] : 1;

                        $Status = null;
                        if ($ThisSplit['fin_split_status'] == 4):
                            $Status = 'paid';
                        elseif ($ThisSplit['fin_split_status'] == 5):
                            $Status = 'canceled';
                        elseif (strtotime(date('Y-m-d', strtotime("-1 day"))) >= strtotime($ThisSplit['fin_split_date'])):
                            $Status = 'late';
                        endif;

                        $Content .= ''
                                . "<div class='split {$Status}'>"
                                . "<p>{$Atual}/{$Total}</p>"
                                . "<p>" . (date('d/m/Y', strtotime($ThisSplit['fin_split_date']))) . "</p>"
                                . "<p><span class='wc_tooltip'>R$ {$Value[0]},{$Value[1]} " . ($ThisSplit['fin_split_status'] == 4 ? "<span>Quitado " . (date('d/m/Y', strtotime($ThisSplit['fin_split_date_pay']))) . "</span>" : '') . "</span></p>"
                                . "<p>"
                                . "<span class='fake-select' title='" . (getFinanPaymentsStatus($ThisSplit['fin_split_status'])) . "'>" . (getFinanPaymentsStatus($ThisSplit['fin_split_status'])) . "</span>"
                                . "</p>"
                                . "<p class='actions'>"
                                . "<a href='javascript:void(0)' class='icon-notext j_voucher-view icon-search' href='' target='_blank' voucher='{$ThisSplit['fin_split_id']}' title='Ver comprovante'></a>"
                                . "<a href='dashboard.php?wc=finan/home&p=" . (date('Y-m', strtotime($ThisSplit['fin_split_date']))) . "&fin={$ThisSplit['fin_split_id']}' class='btn btn_blue btn_small' title='Clique para visualizar'>VER</a>"
                                . "</p>"
                                . "</div>";
                    endforeach;
                    $Content .= ''
                            . "</div>";
                endif;
                $Content .= ''
                        . "</div>"
                        . "</div>";

                $jSON['modal'] = [
                    // 'icon' => 'coin-dollar',
                    'title' => "($ModalTitle) {$ThisFinan['fin_title']}",
                    'content' => $Content,
                    'footer' => "<a class='al_center j_ajaxModalClose'>Fechar</a>",
                    'callback' => ['plugginMaskDefault', 'plugginMaskMoney']
                ];
            endif;
            break;

        case 'finanVoucher':
            if (empty($_FILES['fin_split_voucher'])):
                $jSON['trigger'] = AjaxErro("ERRO! Por favor, envie um arquivo para servir como comprovante de pagamento!", E_USER_ERROR);
            else:
                $Split = $PostData['fin_split_id'];
                $File = $_FILES['fin_split_voucher'];
                unset($PostData['fin_split_id'], $PostData['fin_split_voucher']);
                $Upload = new Upload('../../uploads/');

                $Read->ExeRead(DB_FINAN_SPLITS, "WHERE fin_split_id = :id", "id={$Split}");
                $SplitVoucher = "../../uploads/{$Read->getResult()[0]['fin_split_voucher']}";
                if (file_exists($SplitVoucher) && !is_dir($SplitVoucher)):
                    unlink($SplitVoucher);
                endif;

                if ($File['type'] == 'application/pdf'):
                    $Upload->File($File, 'vouch-' . time() . '.' . base64_encode(time()), 'vouchers');
                else:
                    $Upload->Image($File, 'vouch-' . time() . '.' . base64_encode(time()), IMAGE_W, 'vouchers');
                endif;

                if ($Upload->getResult()):
                    $PostData['fin_split_voucher'] = $Upload->getResult();

                    $Update->ExeUpdate(DB_FINAN_SPLITS, $PostData, "WHERE fin_split_id = :id", "id={$Split}");
                    if ($Update->getResult()):
                        $jSON['trigger'] = AjaxErro("Comprovante enviado com sucesso!");
                        $jSON['forceclick'] = '.j_fin_action';
                    endif;
                else:
                    $jSON['trigger'] = AjaxErro("<b class='icon-image'>ERRO AO ENVIAR COMPROVANTE:</b> " . $Upload->getError(), E_USER_WARNING);
                    echo json_encode($jSON);
                    return;
                endif;
            endif;
            break;

        case 'finanVoucherView':
            $Read->ExeRead(DB_FINAN_SPLITS, "WHERE fin_split_id = :id", "id={$PostData['split']}");
            $SplitVoucher = "../../uploads/{$Read->getResult()[0]['fin_split_voucher']}";
            if (file_exists($SplitVoucher) && !is_dir($SplitVoucher)):
                $jSON['success'] = BASE . "/uploads/{$Read->getResult()[0]['fin_split_voucher']}";
            else:
                $jSON['trigger'] = AjaxErro("<b class='icon-image'>Oops:</b> Não há comprovante para esse registro!", E_USER_WARNING);
            endif;
            break;

        case 'finanManager':
            $Split = $PostData['fin_split_id'];
            unset($PostData['fin_split_id']);

            $Read->FullRead("SELECT f.fin_payment_form AS type, f.fin_type, f.fin_split, f.cli_id, f.fin_title, spl.* FROM " . DB_FINAN_SPLITS . " spl INNER JOIN " . DB_FINAN . " f ON f.fin_id = spl.fin_id WHERE spl.fin_split_id = :id", "id={$Split}");
            if (!$Read->getResult()):
                $jSON['trigger'] = AjaxErro("<b>Oops!</b> O lançamento que você quer editar não existe ou foi removido recentemente!", E_USER_ERROR);
            else:
                $ThisSplit = $Read->getResult()[0];

                if (strpos($PostData['fin_split_price'], ",") && strpos($PostData['fin_split_price'], ".")):
                    $PostData['fin_split_price'] = str_replace(",", ".", str_replace(".", "", $PostData['fin_split_price']));
                elseif (strpos($PostData['fin_split_price'], ",") && !strpos($PostData['fin_split_price'], ".")):
                    $PostData['fin_split_price'] = str_replace(",", ".", $PostData['fin_split_price']);
                endif;
                $PostData['fin_split_date'] = (!empty($PostData['fin_split_date']) ? Check::Data($PostData['fin_split_date']) : null);
                $PostData['fin_split_date_pay'] = (!empty($PostData['fin_split_date_pay']) ? Check::Data($PostData['fin_split_date_pay']) : null);

                if (empty($PostData['fin_split_price'])):
                    $jSON['trigger'] = AjaxErro("ATENÇÂO! O campo \"VALOR\" é obrigatório!", E_USER_ERROR);

                elseif ($PostData['fin_split_date'] == false || (!empty($PostData['fin_split_date_pay']) && $PostData['fin_split_date_pay'] == false)):
                    $jSON['trigger'] = AjaxErro("ATENÇÂO! Revise os campos de data!", E_USER_ERROR);

                elseif (empty($PostData['fin_split_date'])):
                    $jSON['trigger'] = AjaxErro("ATENÇÂO! O campo \"DATA DE VENCIMENTO\" é obrigatório!", E_USER_ERROR);

                elseif ($ThisSplit['type'] >= 3 && (date('m', strtotime($PostData['fin_split_date'])) != date('m', strtotime($ThisSplit['fin_split_date'])))):
                    $jSON['trigger'] = AjaxErro("<b>ERRO!</b> Não é possível inserir um MÊS diferente do atual no campo \"DATA DE VENCIMENTO\"! Isso causará divergências no sistema!<br/><br/><small>Você pode alterar a data mas mantenha no mesmo mês!</small>", E_USER_ERROR);
                else:

                    $ThisFuture = strtotime($ThisSplit['fin_split_date']) >= strtotime(date('Y-m-d')) ? true : false;
                    if (FINANCE_GTW_STATUS && $ThisSplit['fin_type'] == 'rec' && $ThisSplit['fin_split_method'] == 2 && $ThisFuture):

                        // NÃO DEVE ATUALIZAR BOLETO QUANDO A DATA É ANTERIOR À DATA ATUAL DO BOLETO
                        if (date('Y-m-d', strtotime($ThisSplit['fin_split_date'])) != date('Y-m-d', strtotime($PostData['fin_split_date'])) && strtotime($PostData['fin_split_date']) < strtotime($ThisSplit['fin_split_date'])):
                            $jSON['trigger'] = AjaxErro("Oops, a data do boleto não pode ser menor ou igual à data original!", E_USER_ERROR);
                            echo json_encode($jSON);
                            return;
                        endif;

                        if (FINANCE_GTW == 'Gerencianet'):
                            $Payment = new GtwGerencianet();

                            // ALTERA APENAS O VALOR
                            if (date('Y-m-d', strtotime($ThisSplit['fin_split_date'])) != date('Y-m-d', strtotime($PostData['fin_split_date'])) && $ThisSplit['fin_split_price'] == $PostData['fin_split_price']):
                                $Payment->setBilletDate($ThisSplit['fin_split_gat_id'], date('Y-m-d', strtotime($PostData['fin_split_date'])));

                            // DELETA BOLETO E CRIA OUTRO
                            elseif (date('Y-m-d', strtotime($ThisSplit['fin_split_date'])) != date('Y-m-d', strtotime($PostData['fin_split_date'])) || $ThisSplit['fin_split_price'] != $PostData['fin_split_price']):
                                $Payment->Cancel($ThisSplit['fin_split_gat_id']);

                                // VALIDA DADOS DO CLIENTE PARA GERACAO DO BOLETO
                                if (!empty($ThisSplit['cli_id'])):
                                    $Read->FullRead("SELECT "
                                            . "u.user_name, u.user_lastname, u.user_document, u.user_datebirth, "
                                            . "c.cli_email, c.cli_telephone, c.cli_name, c.cli_cnpj, c.cli_type "
                                            . "FROM " . DB_CLIENTS_MEMBERS . " m "
                                            . "INNER JOIN " . DB_USERS . " u ON u.user_id = m.user_id "
                                            . "INNER JOIN " . DB_CLIENTS . " c ON c.cli_id = :id "
                                            . "WHERE m.responsibility = 1 AND m.cli_id = :id", "id={$ThisSplit['cli_id']}"
                                    );
                                elseif (!empty($ThisSplit['user_id'])):
                                    $Read->FullRead("SELECT "
                                            . "user_name AS cli_name, user_name, user_lastname, user_document, user_datebirth, "
                                            . "user_email AS cli_email, user_telephone AS cli_telephone "
                                            . "FROM " . DB_USERS . " WHERE user_id = :id", "id={$ThisSplit['user_id']}"
                                    );
                                else:
                                    $jSON['trigger'] = AjaxErro("Não foi possível alterar o boleto no Gateway pois os dados do cliente não foram encontrados!", E_USER_ERROR);
                                    echo json_encode($jSON);
                                    return;
                                endif;

                                $UserData = $Read->getResult()[0];
                                if (!FINANCE_MODULE_CLIENTS):
                                    $UserData['cli_type'] = 1;
                                endif;

                                $ClienteDados = array(
                                    'name' => $UserData['user_name'] . ' ' . $UserData['user_lastname'],
                                    'email' => $UserData['cli_email'],
                                    'cpf' => $UserData['user_document'],
                                    'phone' => $UserData['cli_telephone'],
                                    'birth' => $UserData['user_datebirth']
                                );

                                $ClientJurDados = null;
                                if ($UserData['cli_type'] == 2): // PJ
                                    $ClientJurDados = ['jurname' => $UserData['cli_name'], 'cnpj' => $UserData['cli_cnpj']];
                                endif;

                                if ($ThisSplit['type'] == 1):
                                    $Description = $ThisSplit['fin_title'];
                                elseif ($ThisSplit['type'] == 2):
                                    $Description = "{$ThisSplit['fin_split_number']}/{$ThisSplit['fin_split']} \"{$ThisSplit['fin_title']}\"";
                                elseif ($ThisSplit['type'] >= 3):
                                    $Description = "\"{$ThisSplit['fin_title']}\" - parc. {$ThisSplit['fin_split_number']}";
                                endif;

                                $CobrancaDados = array(
                                    'descricao' => $Description,
                                    'valor' => $PostData['fin_split_price'],
                                    'vencimento' => date('Y-m-d', strtotime($PostData['fin_split_date']))
                                );

                                $Payment->BoletoBancario($ClienteDados, $ClientJurDados, $CobrancaDados, null);
                                if ($Payment->getResult()):
                                    $UpdateBillet = [
                                        'fin_split_gat_id' => $Payment->getResult()['data']['charge_id'],
                                        'fin_split_billet' => $Payment->getResult()['data']['link'],
                                        'fin_split_status' => setFinanGnetStatus($Payment->getResult()['data']['status'])
                                    ];
                                    $Update->ExeUpdate(DB_FINAN_SPLITS, $UpdateBillet, "WHERE fin_split_id = :id", "id={$Split}");
                                endif;
                            endif;
                        endif;
                    endif;

                    $Update->ExeUpdate(DB_FINAN_SPLITS, $PostData, "WHERE fin_split_id = :id", "id={$Split}");
                    if ($Update->getResult()):
                        $jSON['trigger'] = AjaxErro("Lançamento atualizado com sucesso!");
                        $jSON['forceclick'] = '.j_ajaxModalClose';
                        $jSON['redirect'] = "dashboard.php?wc=finan/home&p=" . (date('Y-m', strtotime($PostData['fin_split_date']))) . "&fin={$Split}";
                    endif;
                endif;
            endif;
            break;

        case 'finanManagerMain':
            $Fin = $PostData['fin_id'];
            $Split = explode('|', $PostData['split']);
            // var_dump($Split);
            unset($PostData['fin_id'], $PostData['split']);

            $Read->ExeRead(DB_FINAN, "WHERE fin_id = :id", "id={$Fin}");
            if (!$Read->getResult()):
                $jSON['trigger'] = AjaxErro("<b>Oops!</b> O registro que você quer editar não existe ou foi removido recentemente!", E_USER_ERROR);
            else:
                $Update->ExeUpdate(DB_FINAN, $PostData, "WHERE fin_id = :id", "id={$Fin}");
                if ($Update->getResult()):
                    $jSON['trigger'] = AjaxErro("Lançamento atualizado com sucesso!");
                    $jSON['forceclick'] = '.j_ajaxModalClose';
                    $jSON['redirect'] = "dashboard.php?wc=finan/home&p=" . (date('Y-m', strtotime($Split[1]))) . "&fin={$Split[0]}";
                endif;
            endif;
            break;

        case 'get_report':
            $ReportStart = date("Y-m-d", strtotime(($PostData['start_date'] ? Check::Data($PostData['start_date']) : date("Y-m-01 H:i:s"))));
            $ReportEnd = date("Y-m-d", strtotime(($PostData['end_date'] ? Check::Data($PostData['end_date']) : date("Y-m-d H:i:s"))));

            $_SESSION['wc_report_date'] = [$ReportStart, $ReportEnd];
            $jSON['redirect'] = "dashboard.php?wc=finan/reports";
            break;


//Manager Banks
        //Manager Banks::Obtem Gestor
        case "getbanks":
            $jSON['modal'] = ["", "shield", "Gerenciar Bancos", "", "<p class='al_center font_small'>Alterar ou Remover um vinculo não altera os dados lançados e finalizados anteriormente!</p>"];
            if (empty($PostData['action_id'])):
                $jSON['modal'][3] .= "<div class='getbancks'>";
                foreach (getFinanBanks() as $BankID => $BankName):
                    $jSON['modal'][3] .= "<p><span class='fl_right'><b class='btn btn_blue icon-tab icon-notext jbs_finan_action' data-cc='Finan' data-ca='getbanks' rel='transf&{$BankID}'></b> <b class='btn btn_blue icon-link icon-notext jbs_finan_action' data-cc='Finan' data-ca='getbanks' rel='vinc&{$BankID}'></b></span>{$BankName} <br><b class='font_small icon-coin-dollar'>Valor atual em caixa: R$ 0,00</b></p>";
                endforeach;
                $jSON['modal'][3] .= "</div>";
            else:
                $PostData['action_id'] = explode("&", $PostData['action_id']);
                $BankAction = $PostData['action_id'][0];
                $BankID = $PostData['action_id'][1];
                unset($PostData['action_id']);

                if ($BankAction == 'vinc'):
                    //Manager Vinculos
                    $jSON['modal'][3] .= "<div class='al_right m_botton'><h3 class='fl_left m_top icon-library'>" . getFinanBanks($BankID) . "</h3><span class='bs_btn bs_btn_grey icon-shield jbs_finan_action' data-cc='Finan' data-ca='getbanks' style='font-size: .7em; padding:10px;'>Ver Todos Banco</span></div>";

                    $jSON['modal'][3] .= "<div class='getbancks' style='padding: 10px;'>";
                    $jSON['modal'][3] .= "<form class='financial_release mdl--form ajax_off realtime_off' name='financial_release' action='' method='post' enctype='multipart/form-data'>
                                            <input type='hidden' name='callback' value='Finan'/>
                                            <input type='hidden' name='callback_action' value='getbanks'/>
                                            <input type='hidden' name='action_id' value='addvinc&{$BankID}'/> 
                                            <input type='hidden' name='finan_bank_id' value='{$BankID}'/> 
                                                <label class='label al_center'>
                                                    <span class='legend al_left'>Métodos de Pagamento</span>
                                                    <select style='width:85%; vertical-align: middle;' class='j-custom-select custom-select' name='finan_methods_id'>
                                                        <option value='' disabled='disabled' selected='selected'>Selecione um Métodos</option>";
                    if (getFinanPaymentsMethods()):
                        foreach (getFinanPaymentsMethods() as $Id => $Methods):
                            $Read->ExeRead(DB_FINAN_BANKVINC, "WHERE finan_methods_id = :methods", "methods={$Id}");
                            if (!$Read->getResult()):
                                $jSON['modal'][3] .= "<option value='{$Id}'>{$Methods}</option>";
                            endif;
                        endforeach;
                    endif;
                    $jSON['modal'][3] .= "</select><span class='fl_right'><button name='public' value='1' type='submit' style='padding: 11px; vertical-align: middle;' title='Realizar Vinculo' class='btn btn_blue icon-link icon-notext'><img class='form_load none' style='margin-left: 10px;' alt='Enviando Requisição!' title='Enviando Requisição!' src='_img/load_w.gif'/></button></span></label>";

                    //Manager Vinculos::Exibe vinculos desse banco
                    $jSON['modal'][3] .= "<div class='bankvinc j_retunBank'>";
                    $Read->ExeRead(DB_FINAN_BANKVINC, "WHERE finan_bank_id = :bank", "bank={$BankID}");
                    if ($Read->getResult()):
                        foreach ($Read->getResult() as $BankVinc):
                            $jSON['modal'][3] .= "<span style='display: inline-flex;' class='m_right m_botton jmethodsv_del_{$BankVinc['finan_bankvinc_id']}'><span class='bankvinc_single'>" . getFinanPaymentsMethods($BankVinc['finan_methods_id']) . "</span><span class='bankvinc_del jbankvinc_del icon-cross icon-notext' id='{$BankVinc['finan_bankvinc_id']}'></span><span class='bankvinc_del_true jbankvinc_del_true_{$BankVinc['finan_bankvinc_id']} icon-warning'>Tem Certeza?</span></span>";
                        endforeach;
                    endif;
                    $jSON['modal'][3] .= "</div>";
                    $jSON['modal'][3] .= "</div>";
                    $jSON['custonPlugs'] = true;
                elseif ($BankAction == 'addvinc'):
                    //Adiciona Novos Vinculos
                    if (empty($PostData['finan_methods_id'])):
                        $jSON['alert'] = ["yellow", "warning", "Opps!! Impossivel realizar vinculo", "Não conseguimos identificar o metodo que deseja vincular ao banco!"];
                        echo json_encode($jSON);
                        return;
                    endif;
                    $Read->ExeRead(DB_FINAN_BANKVINC, "WHERE finan_methods_id = :methods", "methods={$PostData['finan_methods_id']}");
                    if ($Read->getResult()):
                        $jSON['alert'] = ["yellow", "warning", "Opps!! Impossivel realizar vinculo", "O metodo " . getFinanPaymentsMethods($PostData['fin_payment_method']) . " ja foi vinculado em outro banco!"];
                    else:
                        $Create->ExeCreate(DB_FINAN_BANKVINC, $PostData);
                        if ($Create->getResult()):
                            $Read->ExeRead(DB_FINAN_BANKVINC, "WHERE finan_bank_id = :bank", "bank={$BankID}");
                            if ($Read->getResult()):
                                $jSON['divcontent']['.j_retunBank'] = null;
                                foreach ($Read->getResult() as $BankVinc):
                                    $jSON['divcontent']['.j_retunBank'] .= "<span style='display: inline-flex;' class='m_right m_botton jmethodsv_del_{$BankVinc['finan_bankvinc_id']}'><span class='bankvinc_single'>" . getFinanPaymentsMethods($BankVinc['finan_methods_id']) . "</span><span class='bankvinc_del jbankvinc_del icon-cross icon-notext' id='{$BankVinc['finan_bankvinc_id']}'></span><span class='bankvinc_del_true jbankvinc_del_true_{$BankVinc['finan_bankvinc_id']} icon-warning'>Tem Certeza?</span></span>";
                                endforeach;
                            endif;
                        endif;
                        $jSON['clear'] = true;
                    endif;

                endif;
            endif;
            break;

        //Remove Vinculos de metodos de pagamento nos bancos
        case "delvinc":
            //Remove Vinculos
            $Delete->ExeDelete(DB_FINAN_BANKVINC, "WHERE finan_bankvinc_id = :id", "id={$PostData['action_id']}");
            $jSON['divremove'] = ".jmethodsv_del_{$PostData['action_id']}";
            break;

//Manage Categories
        case 'catManager':
            $PostData = array_map('strip_tags', $PostData);
            $CatId = $PostData['category_id'];
            $PostData['category_status'] = 1;
            unset($PostData['category_id']);

            if (strlen($PostData['category_title']) <= 2):
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>OPPSSS: </b> {$_SESSION['userLogin']['user_name']}, dê um nome com mais de 2 caracteres para esta categoria!", E_USER_WARNING);
                echo json_encode($jSON);
                return;
            else:
                $PostData['category_parent'] = ($PostData['category_parent'] ? $PostData['category_parent'] : null);

                $Read->FullRead("SELECT category_id FROM " . DB_FINAN_CAT . " WHERE category_parent = :ci", "ci={$CatId}");
                if ($Read->getResult() && !empty($PostData['category_parent'])):
                    $jSON['trigger'] = AjaxErro("<b class='icon-warning'>OPPSSS: </b> {$_SESSION['userLogin']['user_name']}, uma categoria PAI (que possui subcategorias) não pode ser atribuida como subcategoria", E_USER_WARNING);
                else:
                    $Update->ExeUpdate(DB_FINAN_CAT, $PostData, "WHERE category_id = :id", "id={$CatId}");
                    $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>TUDO CERTO: </b> A categoria <b>{$PostData['category_title']}</b> foi atualizada com sucesso!");
                endif;
            endif;
            break;

        case 'catDelete':
            $Del = $PostData['id'];
            $Read->FullRead("SELECT category_id FROM " . DB_FINAN_CAT . " WHERE category_id = :id", "id={$Del}");
            if (!$Read->getResult()):
                $jSON['error'] = "Oopsss! A categoria que você quis deletar não existe no sistema " . ADMIN_NAME . "! Atualize a tela e tente novamente :)";
            else:
                $Category = $Read->getResult()[0];
                $CatError = null;

                // Verif se as sub-categorias tem lançamentos vinculados
                $Read->FullRead("SELECT category_title, category_id FROM " . DB_FINAN_CAT . " WHERE category_parent = :cat", "cat={$Del}");
                if ($Read->getResult()): // Tem sub categorias
                    foreach ($Read->getResult() as $SubCt):
                        $Read->FullRead("SELECT c.category_title FROM " . DB_FINAN . " f INNER JOIN " . DB_FINAN_CAT . " c ON c.category_id = f.category_id WHERE f.category_id = :cat", "cat={$SubCt['category_id']}");
                        if ($Read->getResult()):
                            $CatError .= " A Categoria {$Read->getResult()[0]['category_title']} possui {$Read->getRowCount()} Lançamentos vinculados!";
                        endif;
                    endforeach;
                else:
                    $Read->FullRead("SELECT c.category_title FROM " . DB_FINAN . " f INNER JOIN " . DB_FINAN_CAT . " c ON c.category_id = f.category_id WHERE f.category_id = :cat", "cat={$Category['category_id']}");
                    if ($Read->getResult()):
                        $CatError .= " A Categoria {$Read->getResult()[0]['category_title']} possui " . $Read->getRowCount() . (($Read->getRowCount() == 1) ? " Lançamento vinculado!" : " Lançamentos vinculados!");
                    endif;
                endif;
            endif;

            if (empty($CatError)):
                $Delete->ExeDelete(DB_FINAN_CAT, "WHERE category_id = :id OR category_parent = :id", "id={$Del}");
                $jSON['success'] = 'Categoria deletada com sucesso!';
            else:
                $jSON['error'] = "( ! ) Não foi possível deletar ... " . $CatError . "! Para deletar alguma categoria ela obrigatoriamente não deve ter nenhum lançamento vinculado!";
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
