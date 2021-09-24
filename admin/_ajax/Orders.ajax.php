<?php

session_start();
require '../../_app/Config.inc.php';
$NivelAcess = 3; //LEVEL_WC_PRODUCTS_ORDERS;

if (!APP_ORDERS || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'Orders';
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

    $Email = new Email;
    $Upload = new Upload('../../uploads/');

//SELECIONA AÇÃO
    switch ($Case):

        case 'AppOrderConvertPed':
            $OrderId = $PostData['order_id'];
            $Read->ExeRead(DB_ORDERS, "WHERE order_id = :id", "id={$OrderId}");
            $OrderCreate = $Read->getResult()[0];

//Atualiza para Pedido
            $Update->ExeUpdate(DB_ORDERS, ["order_type" => 2, "order_status" => 1], "WHERE order_id = :id", "id={$OrderId}");

//Estoque
            $Read->ExeRead(DB_ORDERS_ITEMS, "WHERE order_id = :order", "order={$PostData['order_id']}");
            foreach ($Read->getResult() as $Item):
//Stock
                $Read->FullRead("SELECT stock_inventory,stock_sold,stock_reserva FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Item['stock_id']}");
                $UpdatePdtStock = [
                    'stock_inventory' => $Read->getResult()[0]['stock_inventory'] - $Item['item_amount'],
                    'stock_sold' => $Read->getResult()[0]['stock_sold'] + $Item['item_amount']
                ];
                if ($OrderCreate["orcamento_reserva"]):
                    $UpdatePdtStock["stock_reserva"] = $Read->getResult()[0]['stock_reserva'] - $Item['item_amount'];
                endif;
                $Update->ExeUpdate(DB_PDT_STOCK, $UpdatePdtStock, "WHERE stock_id = :id", "id={$Item['stock_id']}");

//Product
                $Read->FullRead("SELECT pdt_inventory FROM " . DB_PDT . " WHERE pdt_id = :id", "id={$Item['pdt_id']}");
                $UpdatePdt = [
                    'pdt_inventory' => $Read->getResult()[0]['pdt_inventory'] - $Item['item_amount']
                ];
                $Update->ExeUpdate(DB_PDT, $UpdatePdt, "WHERE pdt_id = :id", "id={$Item['pdt_id']}");
            endforeach;

            $finanLanc = [
                'category_id' => 3,
                'fin_type' => "rec",
                'order_id' => $OrderId,
                'fin_title' => "Venda Pedido #{$OrderId}",
                'fin_value' => $OrderCreate['order_price'],
                'fin_date' => $OrderCreate['order_date'],
                'fin_due_date' => $OrderCreate['order_date'],
                'fin_payment_form' => 1,
                'fin_split' => 1,
                'fin_author' => $_SESSION['userLogin']['user_id'],
                'fin_status' => 1
            ];

            $Read->ExeRead(DB_ORDERS_PAY, "WHERE pay_id={$OrderCreate['order_payment_mode']}", "");
            if ($Read->getResult()):
                extract($Read->getResult()[0]);
                if ($split_number == 1 && $split_time == 0):
                    $finanLanc["fin_payment_form"] = 1;
                else:
                    $finanLanc["fin_payment_form"] = 2;
                endif;
                $finanLanc["fin_split"] = $split_number;

                $Create->ExeCreate(DB_FINAN, $finanLanc);
                if ($Create->getResult()):
                    $FinanId = $Create->getResult();
                    $n = 1;
                    while ($n <= $finanLanc["fin_split"]):
                        $fin_split_price = $OrderCreate['order_price'] / $finanLanc["fin_split"];
                        if ($split_number == 1 && $split_time == 0):
                            $fin_split_date = $OrderCreate['order_date'];
                        else:
                            $x = ($n - 1) * $split_time;
                            $fin_split_date = date('Y-m-d', strtotime('+' . $x . ' month', strtotime($OrderCreate['order_date'])));
                            if ($split_time_first):
                                $x = $split_time_first;
                                $fin_split_date = date('Y-m-d', strtotime('+' . $x . ' month', strtotime($split_time_first)));
                            endif;
                        endif;

                        $finanParc = [
                            'fin_id' => $FinanId,
                            'fin_split_price' => $fin_split_price,
                            'fin_split_date' => $fin_split_date,
                            'fin_split_number' => $n,
                            'fin_split_method' => $split_method,
                            'fin_split_status' => $n == 1 ? $split_status_first : 1
                        ];
                        $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                        $n++;
                    endwhile;
                endif;
            endif;

            $jSON['trigger'] = AjaxErro('<b class="icon-warning">PEDIDO CRIADO:</b>');
            $jSON['redirect'] = "dashboard.php?wc=orders/pdv&id={$OrderId}";
            $jSON['printer'] = BASE . "/admin/cupom.php?id={$OrderId}";
            break;

        case 'manager':
            $OrderId = $PostData['order_id'];
            $OrderMail = (!empty($PostData['post_mail']) ? true : false);
            unset($PostData['order_id'], $PostData['post_mail'], $PostData['order_nfe']);

            $Read->FullRead("SELECT order_nfepdf, order_nfexml FROM " . DB_ORDERS . " WHERE order_id = :ord", "ord={$OrderId}");
            if (!empty($_FILES['order_nfe'])):
                $NFE = $_FILES['order_nfe'];
                $nfeFile = array();
                $nfeCount = count($NFE['type']);
                $nfeKeys = array_keys($NFE);
                $nfeLoop = 0;
                $UpdateNfe = array();

                for ($nfe = 0; $nfe < $nfeCount; $nfe++):
                    foreach ($nfeKeys as $Keys):
                        $nfeFiles[$nfe][$Keys] = $NFE[$Keys][$nfe];
                    endforeach;
                endfor;

                foreach ($nfeFiles as $nfeUpload):
                    if (strstr($nfeUpload['type'], '/pdf')):
                        if ($Read->getResult() && $Read->getResult()[0]['order_nfepdf'] && file_exists("../../uploads/{$Read->getResult()[0]['order_nfepdf']}") && !is_dir("../../uploads/{$Read->getResult()[0]['order_nfepdf']}")):
                            unlink("../../uploads/{$Read->getResult()[0]['order_nfepdf']}");
                        endif;
                        $Upload->File($nfeUpload, md5(base64_encode($OrderId)), "nfewc", 20);
                        $PostData['order_nfepdf'] = $Upload->getResult();
                        $jSON['nfepdf'] = BASE . "/uploads/{$Upload->getResult()}";
                        $Email->addFile('../../uploads/' . $Upload->getResult());
                    endif;

                    if (strstr($nfeUpload['type'], '/xml')):
                        if ($Read->getResult() && $Read->getResult()[0]['order_nfexml'] && file_exists("../../uploads/{$Read->getResult()[0]['order_nfexml']}") && !is_dir("../../uploads/{$Read->getResult()[0]['order_nfexml']}")):
                            unlink("../../uploads/{$Read->getResult()[0]['order_nfexml']}");
                        endif;
                        $Upload->File($nfeUpload, md5(base64_encode($OrderId)), "nfewc", 20);
                        $PostData['order_nfexml'] = $Upload->getResult();
                        $jSON['nfexml'] = BASE . "/uploads/{$Upload->getResult()}";
                        $Email->addFile('../../uploads/' . $Upload->getResult());
                    endif;
                endforeach;
            elseif ($Read->getResult()):
                $Email->addFile('../../uploads/' . $Read->getResult()[0]['order_nfepdf']);
                $Email->addFile('../../uploads/' . $Read->getResult()[0]['order_nfexml']);
            endif;

            $Read->ExeRead(DB_ORDERS, "WHERE order_id = :order", "order={$OrderId}");
            if (!$Read->getResult()):
                $jSON['trigger'] = AjaxErro("<span class='icon-warning'>Opss {$_SESSION['userLogin']['user_name']}. Você está tentando gerenciar um pedido que não existe ou foi removido!</span>", E_USER_WARNING);
            else:
                extract($Read->getResult()[0]);
                $Read->ExeRead(DB_USERS, "WHERE user_id = :user", "user={$user_id}");
                $Client = $Read->getResult()[0];
                $Traking = ($order_shipcode < 40000 ? ECOMMERCE_SHIPMENT_COMPANY_LINK : 'http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=');

                if ($OrderMail):
                    if ($PostData['order_status'] == 6 && !$order_mail_processing):
//ENVIA E-MAIL DE PEDIDO EM PROCESSAMENTO
                        $BodyMail = "<p style='font-size: 1.2em;'>Caro(a) {$Client['user_name']},</p>";
                        $BodyMail .= "<p>Este e-mail é para informar que seu pedido #" . str_pad($OrderId, 7, 0, STR_PAD_LEFT) . ", foi processado aqui na " . SITE_NAME . " e que já estamos preparando ele!</p>";
                        $BodyMail .= "<p>Isso significa que já identificamos o pagamento do seu pedido, e o mesmo está sendo preparado para ser enviado para você!</p>";
                        $BodyMail .= "<p style='font-size: 1.4em;'>Detalhes do Pedido:</p>";
                        $BodyMail .= "<p>Pedido: <a href='" . BASE . "/conta/pedido/{$order_id}' title='Ver pedido' target='_blank'>#" . str_pad($OrderId, 7, 0, STR_PAD_LEFT) . "</a><br>Data: " . date('d/m/Y H\hi', strtotime($order_date)) . "<br>Valor: R$ " . number_format($order_price, '2', ',', '.') . "<br>Método de Pagamento: " . getOrderPayment($order_payment) . "</p>";
                        $BodyMail .= "<hr><table style='width: 100%'><tr><td>STATUS:</td><td style='color: #00AD8E; text-align: center;'>✓ Aguardando Pagamento</td><td style='color: #00AD8E; text-align: center;'>✓ Processando</td><td style='color: #888888; text-align: right;'>» Concluído</td></tr></table><hr>";
                        $Read->ExeRead(DB_ORDERS_ITEMS, "WHERE order_id = :order", "order={$OrderId}");
                        if ($Read->getResult()):
                            $i = 0;
                            $ItemsPrice = 0;
                            $ItemsAmount = 0;
                            $BodyMail .= "<p style='font-size: 1.4em;'>Produtos:</p>";
                            $BodyMail .= "<p>Abaixo você pode conferir os detalhes, quantidades e valores de cada produto adquirido em seu pedido. Confira:</p>";
                            $BodyMail .= "<table style='width: 100%' border='0' cellspacing='0' cellpadding='0'>";
                            foreach ($Read->getResult() as $Item):
                                /* CUSTOM BY ALISSON */
                                $Read->FullRead("SELECT (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Item['stock_id']}");
                                //$Read->FullRead("SELECT stock_code FROM " . DB_PDT_STOCK . " WHERE stock_id = :stid", "stid={$Item['stock_id']}");
                                /* CUSTOM BY ALISSON */
                                $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span>" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']});'></span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']});'></span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : '')))));
                                //$ProductSize = ($Read->getResult() && $Read->getResult()[0]['stock_code'] != 'default' ? " ({$Read->getResult()[0]['stock_code']})" : null);

                                $i++;
                                $ItemsAmount += $Item['item_amount'];
                                $ItemsPrice += $Item['item_amount'] * $Item['item_price'];
                                /* CUSTOM BY ALISSON */
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>" . str_pad($i, 5, 0, STR_PAD_LEFT) . " - " . Check::Words($Item['item_name'], 5) . "{$PdtVariation}</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_price'], '2', ',', '.') . " * <b>{$Item['item_amount']}</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_amount'] * $Item['item_price'], '2', ',', '.') . "</td></tr>";
                                //$BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>" . str_pad($i, 5, 0, STR_PAD_LEFT) . " - " . Check::Words($Item['item_name'], 5) . "{$ProductSize}</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_price'], '2', ',', '.') . " * <b>{$Item['item_amount']}</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_amount'] * $Item['item_price'], '2', ',', '.') . "</td></tr>";
                            endforeach;
                            if (!empty($order_coupon)):
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Cupom:</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>{$order_coupon}% de desconto</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>- <strike>R$ " . number_format($ItemsPrice * ($order_coupon / 100), '2', ',', '.') . "</strike></td></tr>";
                            endif;
                            if (!empty($order_shipcode)):
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Frete via " . getShipmentTag($order_shipcode) . "</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_shipprice, '2', ',', '.') . " <b>* 1</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_shipprice, '2', ',', '.') . "</td></tr>";
                            endif;
                            $BodyMail .= "<tr style='background: #cccccc;'><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px;'>{$i} produto(s) no pedido</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px; text-align: right;'>{$ItemsAmount} Itens</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px; text-align: right;'>R$ " . number_format($order_price, '2', ',', '.') . "</td></tr>";

                            if (!empty($order_installments) && $order_installments > 1):
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Pago em {$order_installments}x de R$ " . number_format($order_installment, '2', ',', '.') . "</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>Total: </td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_installments * $order_installment, '2', ',', '.') . "</td></tr>";
                            endif;
                            $BodyMail .= "</table>";
                        endif;
                        $BodyMail .= "<p>Qualquer dúvida não deixe de entrar em contato {$Client['user_name']}. Obrigado por sua preferência mais uma vez...</p>";
                        $BodyMail .= "<p><i>Atenciosamente " . SITE_NAME . "!</i></p>";

                        require '../_tpl/Client.email.php';
                        $Mensagem = str_replace('#mail_body#', $BodyMail, $MailContent);
                        $Email->EnviarMontando("Identificamos seu pagamento #" . str_pad($order_id, 7, 0, 0), $Mensagem, SITE_NAME, MAIL_USER, "{$Client['user_name']} {$Client['user_lastname']}", $Client['user_email']);

//ESTOQUE: Remove produtos do estoque:
                        $Read->FullRead("SELECT pdt_id, item_amount, stock_id FROM " . DB_ORDERS_ITEMS . " WHERE order_id = :order AND pdt_id IS NOT NULL", "order={$OrderId}");
                        if ($Read->getResult()):
                            foreach ($Read->getResult() as $Inventory):

                                $Read->FullRead("SELECT stock_inventory, stock_sold FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Inventory['stock_id']}");
                                $UpdatePdtStock = ['stock_inventory' => $Read->getResult()[0]['stock_inventory'] - $Inventory['item_amount'], 'stock_sold' => $Read->getResult()[0]['stock_sold'] + $Inventory['item_amount']];
                                $Update->ExeUpdate(DB_PDT_STOCK, $UpdatePdtStock, "WHERE stock_id = :id", "id={$Inventory['stock_id']}");

                                $Read->FullRead("SELECT pdt_inventory, pdt_delivered FROM " . DB_PDT . " WHERE pdt_id = :pdt", "pdt={$Inventory['pdt_id']}");
                                if ($Read->getResult()):
                                    $UpdateInventory = [
                                        'pdt_inventory' => $Read->getResult()[0]['pdt_inventory'] - $Inventory['item_amount'],
                                        'pdt_delivered' => $Read->getResult()[0]['pdt_delivered'] + $Inventory['item_amount']
                                    ];
                                    $Update->ExeUpdate(DB_PDT, $UpdateInventory, "WHERE pdt_id = :pdt", "pdt={$Inventory['pdt_id']}");
                                endif;

                            endforeach;
                        endif;

//Impede envio dubplicado de e-mail de processamento
                        $PostData['order_mail_processing'] = 1;
                    endif;

                    if ($PostData['order_status'] == 1 && !$order_mail_completed && $PostData['order_tracking']):
//ENVIA E-MAIL DE PEDIDO CONCLUÍDO
                        $BodyMail = "<p style='font-size: 1.2em;'>Caro(a) {$Client['user_name']},</p>";
                        $BodyMail .= "<p>Este e-mail rápido é para informar que seu pedido #" . str_pad($OrderId, 7, 0, STR_PAD_LEFT) . " foi concluído, e que seus produtos estão a caminho!</p>";
                        if ($PostData['order_tracking'] && $PostData['order_tracking'] != 1):
                            $BodyMail .= "<p>Você pode acompanhar o envio <a title='Acompanhar Pedido' href='{$Traking}{$PostData['order_tracking']}' target='_blank'>clicando aqui!</a></p>";
                        endif;
                        $BodyMail .= "<p>A " . SITE_NAME . " gostaria de lhe agradecer mais uma vez pela preferência em adquirir seus produtos em nossa loja.</p>";
                        $BodyMail .= "<p>Esperamos ter proporcionado a melhor experiência!</p>";
                        $BodyMail .= "<p style='font-size: 1.4em;'>Detalhes do Pedido:</p>";
                        $BodyMail .= "<p>Pedido: <a href='" . BASE . "/conta/pedido/{$order_id}' title='Ver pedido' target=''>#" . str_pad($OrderId, 7, 0, STR_PAD_LEFT) . "</a><br>Data: " . date('d/m/Y H\hi', strtotime($order_date)) . "<br>Valor: R$ " . number_format($order_price, '2', ',', '.') . "<br>Método de Pagamento: " . getOrderPayment($order_payment) . (!empty($PostData['order_tracking']) && $PostData['order_tracking'] != 1 ? "<br>Código do Rastreio: <a title='Acompanhar Pedido' href='{$Traking}{$PostData['order_tracking']}' target='_blank'>{$PostData['order_tracking']}</a>" : '') . "</p>";
                        $BodyMail .= "<hr><table style='width: 100%'><tr><td>STATUS:</td><td style='color: #00AD8E; text-align: center;'>✓ Aguardando Pagamento</td><td style='color: #00AD8E; text-align: center;'>✓ Processando</td><td style='color: #00AD8E; text-align: right;'>✓ Concluído</td></tr></table><hr>";
                        $Read->ExeRead(DB_ORDERS_ITEMS, "WHERE order_id = :order", "order={$OrderId}");
                        if ($Read->getResult()):
                            $i = 0;
                            $ItemsPrice = 0;
                            $ItemsAmount = 0;
                            $BodyMail .= "<p style='font-size: 1.4em;'>Produtos:</p>";
                            $BodyMail .= "<p>Abaixo você pode conferir os detalhes, quantidades e valores de cada produto adquirido em seu pedido. Confira:</p>";
                            $BodyMail .= "<table style='width: 100%' border='0' cellspacing='0' cellpadding='0'>";
                            foreach ($Read->getResult() as $Item):
                                /* CUSTOM BY ALISSON */
                                $Read->FullRead("SELECT (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Item['stock_id']}");
                                //$Read->FullRead("SELECT stock_code FROM " . DB_PDT_STOCK . " WHERE stock_id = :stid", "stid={$Item['stock_id']}");
                                /* CUSTOM BY ALISSON */
                                $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span>" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']});'></span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']});'></span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : '')))));
                                //$ProductSize = ($Read->getResult() && $Read->getResult()[0]['stock_code'] != 'default' ? " ({$Read->getResult()[0]['stock_code']})" : null);

                                $i++;
                                $ItemsAmount += $Item['item_amount'];
                                $ItemsPrice += $Item['item_amount'] * $Item['item_price'];

                                $pdtUnity = '';
                                $Read->LinkResult(DB_PDT, 'pdt_id', $Item['pdt_id'], 'pdt_unity');
                                if (!empty($Read->getResult()[0]['pdt_unity'])):
                                    $pdtUnity = ($Item['item_amount'] >= 2 ? "{$Read->getResult()[0]['pdt_unity']}s" : $Read->getResult()[0]['pdt_unity']);
                                endif;

                                /* CUSTOM BY ALISSON */
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>" . str_pad($i, 5, 0, STR_PAD_LEFT) . " - " . Check::Words($Item['item_name'], 5) . "{$PdtVariation}</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_price'], '2', ',', '.') . " * <b>" . str_replace('.', ',', (float) $Item['item_amount']) . " {$pdtUnity}</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_amount'] * $Item['item_price'], '2', ',', '.') . "</td></tr>";
                                //$BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>" . str_pad($i, 5, 0, STR_PAD_LEFT) . " - " . Check::Words($Item['item_name'], 5) . "{$ProductSize}</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_price'], '2', ',', '.') . " * <b>{$Item['item_amount']}</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_amount'] * $Item['item_price'], '2', ',', '.') . "</td></tr>";
                            endforeach;
                            if (!empty($order_coupon)):
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Cupom:</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>{$order_coupon}% de desconto</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>- <strike>R$ " . number_format($ItemsPrice * ($order_coupon / 100), '2', ',', '.') . "</strike></td></tr>";
                            endif;
                            if (!empty($order_shipcode)):
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Frete via " . getShipmentTag($order_shipcode) . "</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_shipprice, '2', ',', '.') . " <b>* 1</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_shipprice, '2', ',', '.') . "</td></tr>";
                            endif;
                            $BodyMail .= "<tr style='background: #cccccc;'><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px;'>{$i} produto(s) no pedido</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px; text-align: right;'>{$ItemsAmount} Itens</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px; text-align: right;'>R$ " . number_format($order_price, '2', ',', '.') . "</td></tr>";

                            if (!empty($order_installments) && $order_installments > 1):
                                $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Pago em {$order_installments}x de R$ " . number_format($order_installment, '2', ',', '.') . "</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>Total: </td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_installments * $order_installment, '2', ',', '.') . "</td></tr>";
                            endif;
                            $BodyMail .= "</table>";
                        endif;
                        $BodyMail .= "<p>Qualquer dúvida não deixe de entrar em contato {$Client['user_name']}. Obrigado por sua preferência mais uma vez...</p>";
                        $BodyMail .= "<p><i>Atenciosamente " . SITE_NAME . "!</i></p>";

                        require '../_tpl/Client.email.php';
                        $Mensagem = str_replace('#mail_body#', $BodyMail, $MailContent);
                        $Email->EnviarMontando("Seu pedido esta a caminho #" . str_pad($order_id, 7, 0, 0), $Mensagem, SITE_NAME, MAIL_USER, "{$Client['user_name']} {$Client['user_lastname']}", $Client['user_email']);

//Impede envio dubplicado de e-mail de concluído
                        $PostData['order_mail_completed'] = 1;
                    elseif ($PostData['order_status'] == 1 && !$order_mail_completed && empty($PostData['order_tracking'])):
                        $jSON['trigger'] = AjaxErro("<span class='icon-checkmark'>Pedido Atualizado com Sucesso!</span><p class='icon-warning'>Opss {$Client['user_name']}. <b>Informe o código do RASTREIO</b> para informar o cliente sobre seu pedido!</p>", E_USER_WARNING);
                    endif;
                endif;

                if (!empty($PostData['order_tracking']) && $PostData['order_tracking'] != 1):
                    $jSON['content'] = "<a title='Rastrear Pedido' target='_blanck' href='{$Traking}{$PostData['order_tracking']}'>RASTREIO:</a>";
                else:
                    $jSON['content'] = 'RASTREIO:';
                endif;

                $PostData['order_shipment'] = (empty($PostData['order_shipment']) ? null : $PostData['order_shipment']);
                $Update->ExeUpdate(DB_ORDERS, $PostData, "WHERE order_id = :order", "order={$OrderId}");

                if (empty($jSON['trigger'])):
                    $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>Pedido Atualizado com Sucesso!</b>");
                endif;
            endif;
            break;

        case 'cancel':
            $order_id = $PostData['order_id'];

            $Read->ExeRead(DB_ORDERS, "WHERE order_id = :ord", "ord={$order_id}");
            extract($Read->getResult()[0]);

            $Read->ExeRead(DB_USERS, "WHERE user_id = :user", "user={$user_id}");
            $Client = $Read->getResult()[0];

            $BodyMail = "<p style='font-size: 1.2em;'>Caro(a) {$Client['user_name']},</p>";
            $BodyMail .= "<p>Este e-mail é para informar que o seu pedido #" . str_pad($order_id, 7, 0, 0) . " foi cancelado.</p>";
            $BodyMail .= "<p>Isso ocorre quando o pagamento não é identificado no prazo, ou quando a operadora (em compras com cartão) nega o pagamento!</p>";

            $BodyMail .= "<p><b>Não desanime {$Client['user_name']}...</b></p>";
            $BodyMail .= "<p>...você ainda pode acessar nosso site e fazer um novo pedido. E assim que confirmado vamos processar e enviar o mais breve possível!</p>";
            $BodyMail .= "<p><a href='" . BASE . "' title='Conferir Produtos' target='_blank'>Confira aqui nossas novidades!</a></p>";

            $BodyMail .= "<p>Caso tenha qualquer dúvida por favor, entre em contato respondendo este e-mail ou pelo telefone " . SITE_ADDR_PHONE_A . ".</p>";
            $BodyMail .= "<p style='font-size: 1.4em;'>Detalhes do Pedido:</p>";
            $BodyMail .= "<p>Pedido: <a href='" . BASE . "/conta/pedido/{$order_id}' title='Ver pedido' target='_blank'>#" . str_pad($order_id, 7, 0, STR_PAD_LEFT) . "</a><br>Data: " . date('d/m/Y H\hi', strtotime($order_date)) . "<br>Valor: R$ " . number_format($order_price, '2', ',', '.') . "<br>Método de Pagamento: " . getOrderPayment($order_payment) . "</p>";
            $BodyMail .= "<hr><table style='width: 100%'><tr><td>STATUS:</td><td style='color: #00AD8E; text-align: center;'>✓ Aguardando Pagamento</td><td style='color: #888888; text-align: center;'>✓ Processando</td><td style='color: #CC4E4F; text-align: right;'>✓ Cancelado</td></tr></table><hr>";
            $Read->ExeRead(DB_ORDERS_ITEMS, "WHERE order_id = :order", "order={$order_id}");
            if ($Read->getResult()):
                $i = 0;
                $ItemsPrice = 0;
                $ItemsAmount = 0;
                $BodyMail .= "<p style='font-size: 1.4em;'>Produtos:</p>";
                $BodyMail .= "<p>Abaixo você pode conferir os detalhes, quantidades e valores de cada produto adquirido em seu pedido. Confira:</p>";
                $BodyMail .= "<table style='width: 100%' border='0' cellspacing='0' cellpadding='0'>";
                foreach ($Read->getResult() as $Item):
                    /* CUSTOM BY ALISSON */
                    $Read->FullRead("SELECT (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Item['stock_id']}");
                    //$Read->FullRead("SELECT stock_code FROM " . DB_PDT_STOCK . " WHERE stock_id = :stid", "stid={$Item['stock_id']}");
                    /* CUSTOM BY ALISSON */
                    $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span>" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']});'></span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']});'></span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : '')))));
                    //$ProductSize = ($Read->getResult() && $Read->getResult()[0]['stock_code'] != 'default' ? " ({$Read->getResult()[0]['stock_code']})" : null);

                    $i++;
                    $ItemsAmount += $Item['item_amount'];
                    $ItemsPrice += $Item['item_amount'] * $Item['item_price'];

                    $pdtUnity = '';
                    $Read->LinkResult(DB_PDT, 'pdt_id', $Item['pdt_id'], 'pdt_unity');
                    if (!empty($Read->getResult()[0]['pdt_unity'])):
                        $pdtUnity = ($Item['item_amount'] >= 2 ? "{$Read->getResult()[0]['pdt_unity']}s" : $Read->getResult()[0]['pdt_unity']);
                    endif;

                    /* CUSTOM BY ALISSON */
                    $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>" . str_pad($i, 5, 0, STR_PAD_LEFT) . " - " . Check::Words($Item['item_name'], 5) . "{$PdtVariation}</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_price'], '2', ',', '.') . " * <b>" . str_replace('.', ',', (float) $Item['item_amount']) . " {$pdtUnity}</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_amount'] * $Item['item_price'], '2', ',', '.') . "</td></tr>";
                    //$BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>" . str_pad($i, 5, 0, STR_PAD_LEFT) . " - " . Check::Words($Item['item_name'], 5) . "{$ProductSize}</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_price'], '2', ',', '.') . " * <b>{$Item['item_amount']}</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($Item['item_amount'] * $Item['item_price'], '2', ',', '.') . "</td></tr>";
                endforeach;
                if (!empty($order_coupon)):
                    $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Cupom:</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>{$order_coupon}% de desconto</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>- <strike>R$ " . number_format($ItemsPrice * ($order_coupon / 100), '2', ',', '.') . "</strike></td></tr>";
                endif;
                if (!empty($order_shipcode)):
                    $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Frete via " . getShipmentTag($order_shipcode) . "</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_shipprice, '2', ',', '.') . " <b>* 1</b></td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_shipprice, '2', ',', '.') . "</td></tr>";
                endif;
                $BodyMail .= "<tr style='background: #cccccc;'><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px;'>{$i} produto(s) no pedido</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px; text-align: right;'>{$ItemsAmount} Itens</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 10px 10px 10px; text-align: right;'>R$ " . number_format($order_price, '2', ',', '.') . "</td></tr>";

                if (!empty($order_installments) && $order_installments > 1):
                    $BodyMail .= "<tr><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0;'>Pago em {$order_installments}x de R$ " . number_format($order_installment, '2', ',', '.') . "</td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>Total: </td><td style='border-bottom: 1px solid #cccccc; padding: 10px 0 10px 0; text-align: right;'>R$ " . number_format($order_installments * $order_installment, '2', ',', '.') . "</td></tr>";
                endif;
                $BodyMail .= "</table>";
            endif;

            $BodyMail .= "<p>Fique a vontade para escolher novos produtos e realizar um novo pedido em nossa loja! <a href='" . BASE . "' title='Produtos " . SITE_NAME . "'>Confira aqui nossos produtos!</a></p>";
            $BodyMail .= "<p>Qualquer dúvida não deixe de entrar em contato {$Client['user_name']}. Obrigado por sua preferência mais uma vez...</p>";
            $BodyMail .= "<p><i>Atenciosamente " . SITE_NAME . "!</i></p>";

            require '../../_cdn/widgets/ecommerce/cart.email.php';
            $Mensagem = str_replace('#mail_body#', $BodyMail, $MailContent);
            $Email->EnviarMontando("Pedido cancelado #" . str_pad($order_id, 7, 0, 0), $Mensagem, SITE_NAME, MAIL_USER, "{$Client['user_name']} {$Client['user_lastname']}", $Client['user_email']);

//ORDER CANCEL
            if ($order_status != 2):
                $UpdateOrder = ['order_status' => 2, 'order_update' => date('Y-m-d H:i:s')];
                $Update->ExeUpdate(DB_ORDERS, $UpdateOrder, "WHERE order_id = :orid", "orid={$order_id}");
            endif;
            $jSON['success'] = true;
            $jSON['trigger'] = AjaxErro("<b class='icon-warning'>PEDIDO CANCELADO:</b> Um e-mail foi enviado para {$Client['user_name']} ({$Client['user_email']}) avisando!");
            break;

        case 'delete':
            $Delete->ExeDelete(DB_ORDERS, "WHERE order_id = :order", "order={$PostData['del_id']}");
            $Delete->ExeDelete(DB_ORDERS_ITEMS, "WHERE order_id = :order", "order={$PostData['del_id']}");

            $jSON['trigger'] = AjaxErro('<b class="icon-checkmark">PEDIDO REMOVIDO COM SUCESSO!</b> <a style="font-size: 0.8em; margin-left: 10px" class="btn btn_green" href="dashboard.php?wc=orders/home" title="Ver Pedidos">VER PEDIDOS!</a>');
            break;

        case 'wcOrderCreateApp':
//SEARCH USER
            if (!empty($PostData['Search'])):
                $UserSearch = $PostData['Search'];
                $Read->FullRead("SELECT user_id, user_name, user_lastname, user_email, user_document FROM " . DB_USERS . " WHERE CONCAT_WS(' ', user_name, user_lastname) LIKE '%' :s '%' OR user_email LIKE '%' :s '%'", "s={$UserSearch}");
                if ($Read->getResult()):
                    $jSON['result'] = "<ul class='wc_createorder_user' style='margin-top: 15px;'>";
                    foreach ($Read->getResult() as $User):
                        $jSON['result'] .= "<li><label><input class='jwc_ordercreate_addr' type='radio' name='user_id' value='{$User['user_id']}'><p><b>NOME:</b> {$User['user_name']} {$User['user_lastname']}<br><b>E-MAIL:</b> {$User['user_email']}<br><b>CPF:</b> {$User['user_document']}</p></label></li>";
                    endforeach;
                    $jSON['result'] .= "</ul>";
                else:
                    $jSON['result'] = "<div class='trigger trigger_info' style='display: block; margin-top: 15px;'>Nada encontrado para {$UserSearch}!</div>";
                endif;
            endif;

            if (!empty($PostData['AddrUser'])):
                $UserId = $PostData['AddrUser'];
                $Read->ExeRead(DB_USERS_ADDR, "WHERE user_id = :id", "id={$UserId}");
                if ($Read->getResult()):
//CART SESSION
                    if (empty($_SESSION['oderCreate'])):
                        $_SESSION['oderCreate'] = array();
                    endif;
                    $_SESSION['oderCreate']['user_id'] = $UserId;

                    $jSON['result'] = "<ul class='wc_createorder_user'>";
                    foreach ($Read->getResult() as $Addr):
                        $jSON['result'] .= "<li><label><input class='jwc_ordercreate_addr' type='radio' name='addr_id' value='{$Addr['addr_id']}'><p><b>ENDEREÇO:</b> {$Addr['addr_name']}<br>{$Addr['addr_street']}, {$Addr['addr_number']}<br>{$Addr['addr_district']}, {$Addr['addr_city']}/{$Addr['addr_state']}<br>CEP: {$Addr['addr_zipcode']}</p></label></li>";
                    endforeach;
                    $jSON['result'] .= "</ul>";
                else:
                    $jSON['result'] = "<div class='trigger trigger_info' style='display: block'>O cliente não tem endereços cadastrados!</div>";
                endif;
            endif;

//ADDR SET
            if (!empty($PostData['setAddr'])):
                $_SESSION['oderCreate']['addr_id'] = $PostData['setAddr'];
            endif;

//SEARCH PRODUTCTS
            if (!empty($PostData['PdtSearch'])):
                $PdtSearch = $PostData['PdtSearch'];
                $Read->FullRead("SELECT stock_id, pdt_id, stock_inventory FROM " . DB_PDT_STOCK . " WHERE stock_inventory >= 1 AND pdt_id IN(SELECT pdt_id FROM " . DB_PDT . " WHERE pdt_title LIKE '%' :s '%' OR pdt_code LIKE '%' :s '%') ORDER BY stock_inventory DESC LIMIT 5", "s={$PdtSearch}");
                if (!$Read->getResult()):
                    $jSON['result'] = "<div class='trigger trigger_info' style='display: block; margin: 15px 0 0 0;'>Não foram encontratos produtos para os termpos {$PdtSearch}!</div>";
                else:
                    $jSON['result'] = null;
                    foreach ($Read->getResult() as $Stock):

                        /* CUSTOM BY ALISSON */
                        $Read->FullRead("SELECT (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Stock['stock_id']}");
                        $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span>" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']}); padding: 10px 20px;'></span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']}); padding: 10px 20px;'></span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : '')))));
                        /* CUSTOM BY ALISSON */

                        $Read->FullRead("SELECT pdt_title, pdt_cover, pdt_price, pdt_offer_price, pdt_offer_start, pdt_offer_end FROM " . DB_PDT . " WHERE pdt_id = :id", "id={$Stock['pdt_id']}");
                        if ($Read->getResult()):
                            extract($Read->getResult()[0]);

                            $PdtPrice = null;
                            if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                                $PdtPrice = $pdt_offer_price;
                            else:
                                $PdtPrice = $pdt_price;
                            endif;

                            $ForSelect = null;
                            for ($FC = 1; $FC <= $Stock['stock_inventory']; $FC++):
                                $ForSelect .= "<option value='{$FC}'>" . str_pad($FC, 3, 0, 0) . "</option>";

                            endfor;
                            $jSON['result'] .= "<article class='wc_order_create_item jwc_order_create_add' id='{$Stock['stock_id']}'>";
                            $jSON['result'] .= "<img src='../tim.php?src=uploads/{$pdt_cover}&w=180' alt='{$pdt_title}' title='{$pdt_title}'/><header>";
                            $jSON['result'] .= "<h1>{$pdt_title} $PdtVariation</h1>";
                            $jSON['result'] .= "<p>R$ " . number_format($PdtPrice, 2, ',', '.') . " - <span>{$Stock['stock_inventory']} em estoque</span></p>";
                            $jSON['result'] .= "</header><div class='add'>";
                            $jSON['result'] .= "<select name='pdt_inventory'>{$ForSelect}</select><span id='{$Stock['stock_id']}' class='btn btn_green'><b>ADD</b></span></div>";
                            if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s') && strtotime($pdt_offer_end) - strtotime(date('Y-m-d H:i:s')) <= 259200):
                                $jSON['result'] .= "<div class='countdown' data-expire='{$pdt_offer_end}'><div class='countdown_wrapper'><div><span>Oferta<br/><span class='countdown_legend'>Acaba em:</span></span></div><div><span><span class='days'>00</span>&nbsp;&nbsp;:&nbsp;&nbsp;<br/><span class='countdown_legend'>Dia</span></span><span><span class='hours'>00</span>&nbsp;&nbsp;:&nbsp;&nbsp;<br/><span class='countdown_legend'>Hrs</span></span><span><span class='minutes'>00</span>&nbsp;&nbsp;:&nbsp;&nbsp;<br/><span class='countdown_legend'>Min</span></span><span><span class='seconds'>00</span><br/><span class='countdown_legend'>Seg</span></span></div></div></div>";
                            endif;
                            $jSON['result'] .= "</article>";
                        endif;
                    endforeach;
                endif;
            endif;

            //ADD TO CARD
            if (!empty($PostData['StockId'])):
                $StockId = $PostData['StockId'];
                $StockQtd = $PostData['StockQtd'];
                $Read->ExeRead(DB_PDT, "WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :id AND stock_inventory >= :st)", "id={$StockId}&st={$StockQtd}");
                $Product = $Read->getResult();

                if ($Product):
                    $jSON['trigger'] = AjaxErro("( √ ) <b>{$StockQtd}</b> unidades do produto <b>{$Product[0]['pdt_title']}</b> adicionadas com sucesso!<p>( ! ) Quando terminar basta finalizar o pedido!</p>");
                    $_SESSION['oderCreate']['item'][$StockId] = $StockQtd;

                    $jSON['result'] = null;
                    $CartTotalItems = 0;
                    $CartTotalPrice = 0;
                    foreach ($_SESSION['oderCreate']['item'] as $Pdt => $Qtd):

                        $Read->ExeRead(DB_PDT_STOCK, "WHERE stock_id = :id", "id={$Pdt}");
                        extract($Read->getResult()[0]);

                        /* CUSTOM BY ALISSON */
                        $Read->FullRead("SELECT (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Read->getResult()[0]['stock_id']}");
                        $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span>" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']}); padding: 10px 20px;'></span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']}); padding: 10px 20px;'></span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : '')))));
                        /* CUSTOM BY ALISSON */

                        $Read->ExeRead(DB_PDT, "WHERE pdt_id = :st", "st={$pdt_id}");
                        extract($Read->getResult()[0]);

                        if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                            $PdtPrice = $pdt_offer_price;
                        else:
                            $PdtPrice = $pdt_price;
                        endif;

                        $jSON['result'] .= "<article class='item_{$Pdt} wc_ordercreate_itemcart'>
                                                        <h1>{$pdt_title} $PdtVariation
                                                        </h1><p class='col'>
                                                            {$Qtd} x R$ " . number_format($PdtPrice, 2, ',', '.') . "
                                                        </p><p class='col'>
                                                            R$ " . number_format($PdtPrice * $Qtd, 2, ',', '.') . "</p><p class='col'>
                                                            <span id='{$Pdt}' class='btn btn_red jwc_order_create_item_remove'>X</span>
                                                        </p></article>";

                        //CART TOTAL
                        $CartTotalItems += $Qtd;
                        $CartTotalPrice += $PdtPrice * $Qtd;
                    endforeach;
                    $_SESSION['oderCreate']['order_price'] = $CartTotalPrice;


                    if (isset($_SESSION['oderCreate']["nfce"])):
                        //$jSON['result'] .= "<p><span class='btn btn_green jwc_orderapp_finish_order'>FINALIZAR NFCE</span></p>";
                        $jSON['result_total'] = "<p>Subtotal<br><span class='jwc_order_create_shipment_cartprice'>" . number_format($CartTotalPrice, 2, ',', '.') . "</span></p>
                                                    <p>Desconto<br><span class='jwc_order_create_shipment_cartcupom'>0,00</span></p>
                                                    <p><b>Total<br><span class='jwc_order_create_shipment_carttotal'>0,00</span></b></p>";
                    elseif (isset($_SESSION['oderCreate']["nfe"])):
                        //$jSON['result'] .= "<p><span class='btn btn_green jwc_orderapp_finish_order'>FINALIZAR NFE</span></p>";
                        $jSON['result_total'] = number_format($CartTotalPrice, 2, ',', '.');
                    else:
                        $jSON['result'] .= "<div class='wc_ordercreate_totalcart'>";
                        $jSON['result'] .= "<p>{$CartTotalItems} produtos</p>";
                        $jSON['result'] .= "<p>Total: R$ " . number_format($CartTotalPrice, 2, ',', '.') . "</p>";
                        $jSON['result'] .= "<p><span class='btn btn_green jwc_orderapp_finish_order'>FINALIZAR PEDIDO</span></p>";
                        $jSON['result'] .= "</div>";
                    endif;

                else:
                    $jSON['trigger'] = AjaxErro("<b>( X )</b> O produto que você tentou adicionar não existe ou esta fora do estoque informado!", E_USER_WARNING);
                endif;
            endif;

            //REMOVE
            if (!empty($PostData['Remove'])):
                unset($_SESSION['oderCreate']['item'][$PostData['Remove']]);

                $jSON['result'] = null;
                $CartTotalItems = 0;
                $CartTotalPrice = 0;
                foreach ($_SESSION['oderCreate']['item'] as $Pdt => $Qtd):
                    $Read->ExeRead(DB_PDT_STOCK, "WHERE stock_id = :id", "id={$Pdt}");
                    extract($Read->getResult()[0]);

                    /* CUSTOM BY ALISSON */
                    $Read->FullRead("SELECT (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Read->getResult()[0]['stock_id']}");
                    $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span>" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " <span class='wc_cart_tag'>Cor: {$Read->getResult()[0]['attr_color_title']}</span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']}); padding: 10px 20px;'></span>" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " <span class='wc_cart_tag' title='Estampa: {$Read->getResult()[0]['attr_print_title']}' style='background-image: url(" . BASE . "/uploads/{$Read->getResult()[0]['attr_print_code']}); padding: 10px 20px;'></span> <span class='wc_cart_tag'>Tamanho: {$Read->getResult()[0]['attr_size_title']}</span>" : '')))));
                    /* CUSTOM BY ALISSON */

                    $Read->ExeRead(DB_PDT, "WHERE pdt_id = :st", "st={$pdt_id}");
                    extract($Read->getResult()[0]);

                    if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                        $PdtPrice = $pdt_offer_price;
                    else:
                        $PdtPrice = $pdt_price;
                    endif;

                    $jSON['result'] .= "<article class='item_{$Pdt} wc_ordercreate_itemcart'>
                                                        <h1>{$pdt_title} $PdtVariation
                                                        </h1><p class='col'>
                                                            {$Qtd} x R$ " . number_format($PdtPrice, 2, ',', '.') . "
                                                        </p><p class='col'>
                                                            R$ " . number_format($PdtPrice * $Qtd, 2, ',', '.') . "</p><p class='col'>
                                                            <span id='{$Pdt}' class='btn btn_red jwc_order_create_item_remove'>X</span>
                                                        </p></article>";

                    //CART TOTAL
                    $CartTotalItems += $Qtd;
                    $CartTotalPrice += $PdtPrice * $Qtd;
                endforeach;
                $_SESSION['oderCreate']['order_price'] = $CartTotalPrice;

                if (isset($_SESSION['oderCreate']["nfce"])):
                    //$jSON['result'] .= "<p><span class='btn btn_green jwc_orderapp_finish_order'>FINALIZAR NFCE</span></p>";
                    $jSON['result_total'] = "<p>Subtotal<br><span class='jwc_order_create_shipment_cartprice'>" . number_format($CartTotalPrice, 2, ',', '.') . "</span></p>
                                                    <p>Desconto<br><span class='jwc_order_create_shipment_cartcupom'>0,00</span></p>
                                                    <p><b>Total<br><span class='jwc_order_create_shipment_carttotal'>0,00</span></b></p>";
                elseif (isset($_SESSION['oderCreate']["nfe"])):
                    //$jSON['result'] .= "<p><span class='btn btn_green jwc_orderapp_finish_order'>FINALIZAR NFE</span></p>";
                    $jSON['result_total'] = number_format($CartTotalPrice, 2, ',', '.');
                else:
                    $jSON['result'] .= "<div class='wc_ordercreate_totalcart'>";
                    $jSON['result'] .= "<p>{$CartTotalItems} produtos</p>";
                    $jSON['result'] .= "<p>Total: R$ " . number_format($CartTotalPrice, 2, ',', '.') . "</p>";
                    $jSON['result'] .= "<p><span class='btn btn_green jwc_orderapp_finish_order'>FINALIZAR PEDIDO</span></p>";
                    $jSON['result'] .= "</div>";
                endif;

            endif;
            break;

        case 'OrderAppFinish':
            $CartTotal = 0;
            $VolumeTotal = 0;
            $WeightTotal = 0;
            $AmountTotal = 0;
            $xl = 0;
            $size = 0;
            $handlingFee = 0;
            foreach ($_SESSION['oderCreate']['item'] as $ItemId => $ItemAmount):
                $Read->ExeRead(DB_PDT, "WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :id)", "id={$ItemId}");
                if (!$Read->getResult()):
                    unset($_SESSION['oderCreate']['item']);
                else:
                    extract($Read->getResult()[0]);
                    $CartTotal += ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s') ? $pdt_offer_price : $pdt_price) * $ItemAmount;
                    $VolumeTotal += ($pdt_dimension_width / 100) * ($pdt_dimension_depth / 100) * ($pdt_dimension_heigth / 100) * $ItemAmount;
                    $WeightTotal += ($pdt_dimension_weight / 1000) * $ItemAmount;
                    $AmountTotal += $ItemAmount;
                    $size += (($pdt_dimension_width / 100) + ($pdt_dimension_depth / 100) + ($pdt_dimension_heigth / 100)) * $ItemAmount;
                    if (!ECOMMERCE_SHIPMENT_CORREIOS_BY_WEIGHT && ($pdt_dimension_width > 105 || $pdt_dimension_depth > 105 || $pdt_dimension_heigth > 105)):
                        $xl = 1;
                    endif;
                    if ($pdt_dimension_width > 70 || $pdt_dimension_depth > 70 || $pdt_dimension_heigth > 70):
                        $handlingFee = 1;
                    endif;
                endif;
            endforeach;

            /* Shipping Rule */

            if (!isset($_SESSION['oderCreate']["nfce"]) || isset($_SESSION['oderCreate']['addr_id'])):
                /* CUSTOM BY ALISSON */
                $Read->FullRead("SELECT addr_zipcode FROM " . DB_USERS_ADDR . " WHERE addr_id = :addr", "addr={$_SESSION['oderCreate']['addr_id']}");
                $ZipCode = (!empty($Read->getResult()[0]['addr_zipcode']) ? $Read->getResult()[0]['addr_zipcode'] : '00000-000');


                $url = curl_init("https://viacep.com.br/ws/{$ZipCode}/json/");
                curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
                $cep = json_decode(curl_exec($url));

                //            if (!empty($cep->erro) && $cep->erro === true) :
                if (empty($cep) || !empty($cep->erro) && $cep->erro === true) :
                    $jSON['trigger'] = AjaxErro("<b>OPPSSS:</b> O CEP digitado não foi encontrado na base dos correios. Confira isso :)", E_USER_WARNING);
                    $jSON['result'] = false;
                    $ErroZip = true;
                    break;
                else:

                    $uf = null;
                    $city = null;
                    $district = null;
                    $jSON['cart_shipment'] = null;

                    /* state */
                    $Read->ExeRead(
                            DB_FREIGHTS, 'WHERE uf = :uf AND city IS NULL AND district IS NULL', "uf={$cep->uf}"
                    );

                    if ($Read->getResult()):
                        $uf = true;

                        if ($Read->getResult()[0]['status'] == '0'):
                            $jSON['trigger'] = AjaxErro(
                                    "<b>Desculpe</b>! Não realizamos entrega para o seu estado", E_USER_WARNING
                            );

                            break;
                        endif;

                        $jSON['cart_shipment'] = "<li><li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='{$Read->getResult()[0]['price']}' type='radio' id='10003'/> De 01 a {$Read->getResult()[0]['days']} dias úteis - <b>R$ " . number_format($Read->getResult()[0]['price'], 2, ',', '.') . "</b></label></li></li>";
                    endif;

                    /* city */
                    $Read->ExeRead(
                            DB_FREIGHTS, 'WHERE uf = :uf AND city = :city AND district IS NULL', "uf={$cep->uf}&city={$cep->localidade}"
                    );

                    if ($Read->getResult()):
                        $city = true;

                        if ($Read->getResult()[0]['status'] == '0'):
                            $jSON['trigger'] = AjaxErro(
                                    "<b>Desculpe</b>! Não realizamos entrega para a sua cidade", E_USER_WARNING
                            );

                            $jSON['cart_shipment'] = null;
                            break;
                        endif;

                        $jSON['cart_shipment'] = "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='{$Read->getResult()[0]['price']}' type='radio' id='10003'/> De 01 a {$Read->getResult()[0]['days']} dias úteis - <b>R$ " . number_format($Read->getResult()[0]['price'], 2, ',', '.') . "</b></label></li>";
                    endif;

                    /* district */
                    $Read->ExeRead(
                            DB_FREIGHTS, 'WHERE uf = :uf AND city = :city AND district = :district', "uf={$cep->uf}&city={$cep->localidade}&district={$cep->bairro}"
                    );

                    if ($Read->getResult()):
                        $district = true;

                        if ($Read->getResult()[0]['status'] == '0'):
                            $jSON['trigger'] = AjaxErro(
                                    "<b>Desculpe</b>! Não realizamos entrega para o seu bairro", E_USER_WARNING
                            );

                            $jSON['cart_shipment'] = null;
                            break;
                        endif;

                        $jSON['cart_shipment'] = "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='{$Read->getResult()[0]['price']}' type='radio' id='10003'/> De 01 a {$Read->getResult()[0]['days']} dias úteis - <b>R$ " . number_format($Read->getResult()[0]['price'], 2, ',', '.') . "</b></label></li>";
                    endif;

                    if (ECOMMERCE_SHIPMENT_LOCAL_IN_PLACE):
                        $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='0' type='radio' id='10005'/> Retirar na Loja: <b>R$ 0,00</b></label></li>";
                    endif;

                    $CartPrice = (empty($_SESSION['wc_cupom']) ? $CartTotal : $CartTotal * ((100 - $_SESSION['wc_cupom']) / 100));
                    if (ECOMMERCE_SHIPMENT_FREE && $CartPrice > ECOMMERCE_SHIPMENT_FREE):
                        $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='0' type='radio' id='10002'/> Envio Gratuito: De 01 a " . str_pad(ECOMMERCE_SHIPMENT_DELAY + ECOMMERCE_SHIPMENT_FREE_DAYS, 2, 0, 0) . " dias úteis - <b>R$ 0,00</b></label></li>";
                    endif;

                    if ($uf || $city || $district):
                        $_SESSION['wc_shipment_zip'] = $ZipCode;
                        break;
                    endif;
                endif;

                if (!ECOMMERCE_SHIPMENT_CORREIOS_BY_WEIGHT && $size > 2):
                    $xl = 1;
                endif;

                $vlMercadoria = number_format($CartTotal, '2', '.', '');
                $totalweight = floatval($WeightTotal);
                $totalvolume = number_format($VolumeTotal, '4', '.', '');

                //AUTO INSTANCE OBJECT TNT
                if (empty($Tnt) && ECOMMERCE_SHIPMENT_TNT_QUOTE == 1):
                    $Tnt = new Tnt(ECOMMERCE_SHIPMENT_TNT_LOGIN, ECOMMERCE_SHIPMENT_TNT_SENHA, ECOMMERCE_SHIPMENT_TNT_CDDIVISAOCLIENTE, ECOMMERCE_SHIPMENT_TNT_TPPESSOAREMETENTE, ECOMMERCE_SHIPMENT_TNT_TPSITUACAOTRIBUTARIAREMETENTE, SITE_ADDR_CNPJ, SITE_ADDR_IE, SITE_ADDR_ZIP);
                endif;

                //AUTO INSTANCE OBJECT JAMEF
                if (empty($Jamef) && ECOMMERCE_SHIPMENT_JAMEF_QUOTE == 1):
                    $Jamef = new Jamef(SITE_ADDR_CNPJ, SITE_ADDR_CITY, SITE_ADDR_UF, ECOMMERCE_SHIPMENT_JAMEF_FILCOT, ECOMMERCE_SHIPMENT_JAMEF_USUARIO);
                endif;

                //AUTO INSTANCE OBJECT JADLOG
                if (empty($Jadlog) && ECOMMERCE_SHIPMENT_JADLOG_QUOTE == 1):
                    $Jadlog = new Jadlog(ECOMMERCE_SHIPMENT_JADLOG_PASSWORD, SITE_ADDR_CNPJ, SITE_ADDR_ZIP);
                endif;

                //AUTO INSTANCE OBJECT CORREIOS
                if (empty($Correios) && ECOMMERCE_SHIPMENT_CORREIOS_QUOTE == 1):
                    $Correios = new CorreiosCurl(SITE_ADDR_ZIP, ECOMMERCE_SHIPMENT_CORREIOS_CDEMPRESA, ECOMMERCE_SHIPMENT_CORREIOS_CDSENHA);
                endif;


                $jSON['cart_shipment'] = null;

                if (ECOMMERCE_SHIPMENT_TNT_QUOTE == 1):
                    $Tnt->setQuoteData($ZipCode, $totalweight, $vlMercadoria, $totalvolume, ECOMMERCE_SHIPMENT_TNT_TPFRETE, ECOMMERCE_SHIPMENT_TNT_TPSERVICO, 'F', 'NC', '12345', '', ECOMMERCE_SHIPMENT_TNT_BY_WEIGHT, ECOMMERCE_SHIPMENT_ADDITIONAL_PERCENT, ECOMMERCE_SHIPMENT_ADDITIONAL_CHARGE, ECOMMERCE_SHIPMENT_DELAY);
                    $TntRetorno = $Tnt->getQuote();
                    if ($TntRetorno['status'] === 'OK'):
                        $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='" . $TntRetorno['valorfrete'] . "' type='radio' id='{$TntRetorno['shipcode']}'/> " . getShipmentTag(intval($TntRetorno['shipcode'])) . " - R$ " . number_format($TntRetorno['valorfrete'], '2', ',', '') . " - {$TntRetorno['prazoentrega']} dias úteis</label></li>";
                    endif;
                endif;

                if (ECOMMERCE_SHIPMENT_JAMEF_QUOTE == 1):
                    $Jamef->setQuoteData($ZipCode, $totalweight, $vlMercadoria, $totalvolume, ECOMMERCE_SHIPMENT_JAMEF_TIPTRA, ECOMMERCE_SHIPMENT_JAMEF_SEGPROD, ECOMMERCE_SHIPMENT_JAMEF_BY_WEIGHT, ECOMMERCE_SHIPMENT_ADDITIONAL_PERCENT, ECOMMERCE_SHIPMENT_ADDITIONAL_CHARGE, ECOMMERCE_SHIPMENT_DELAY);
                    $JamefRetorno = $Jamef->getQuote();
                    if ($JamefRetorno['status'] === 'OK'):
                        $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='" . $JamefRetorno['valorfrete'] . "' type='radio' id='{$JamefRetorno['shipcode']}'/> " . getShipmentTag(intval($JamefRetorno['shipcode'])) . " - R$ " . number_format($JamefRetorno['valorfrete'], '2', ',', '') . " - {$JamefRetorno['prazoentrega']} dias úteis</label></li>";
                    endif;
                endif;

                if (ECOMMERCE_SHIPMENT_JADLOG_QUOTE == 1):
                    $Jadlog->setQuoteData($ZipCode, $totalweight, $vlMercadoria, $totalvolume, ECOMMERCE_SHIPMENT_JADLOG_FRAP, ECOMMERCE_SHIPMENT_JADLOG_TIPENTREGA, ECOMMERCE_SHIPMENT_JADLOG_VLCOLETA, ECOMMERCE_SHIPMENT_JADLOG_MODALIDADE, ECOMMERCE_SHIPMENT_JADLOG_SEGURO, ECOMMERCE_SHIPMENT_JADLOG_BY_WEIGHT, ECOMMERCE_SHIPMENT_ADDITIONAL_PERCENT, ECOMMERCE_SHIPMENT_ADDITIONAL_CHARGE);
                    $JadlogRetorno = $Jadlog->getQuote();
                    if ($JadlogRetorno['status'] === 'OK'):
                        $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='" . $JadlogRetorno['valorfrete'] . "' type='radio' id='{$JadlogRetorno['shipcode']}'/> " . getShipmentTag(intval($JadlogRetorno['shipcode'])) . " - R$ " . number_format($JadlogRetorno['valorfrete'], '2', ',', '') . " - " . ECOMMERCE_SHIPMENT_JADLOG_DAYS . " dias úteis</label></li>";
                    endif;
                endif;

                if (ECOMMERCE_SHIPMENT_CORREIOS_QUOTE == 1 && $xl == 0):
                    $additionalCharge = ECOMMERCE_SHIPMENT_ADDITIONAL_CHARGE;
                    if ($handlingFee) {
                        $additionalCharge += 79.00;
                    }
                    $Correios->setQuoteData($ZipCode, $totalweight, $vlMercadoria, $totalvolume, ECOMMERCE_SHIPMENT_CORREIOS_SERVICE, ECOMMERCE_SHIPMENT_CORREIOS_FORMAT, ECOMMERCE_SHIPMENT_CORREIOS_OWN_HAND, ECOMMERCE_SHIPMENT_CORREIOS_ALERT, ECOMMERCE_SHIPMENT_CORREIOS_DECLARE, ECOMMERCE_SHIPMENT_CORREIOS_BY_WEIGHT, ECOMMERCE_SHIPMENT_ADDITIONAL_PERCENT, $additionalCharge, ECOMMERCE_SHIPMENT_DELAY);
                    $CorreiosRetorno = $Correios->getQuote();
                    if (!array_key_exists('shipcode', $CorreiosRetorno)):
                        foreach ($CorreiosRetorno as $modalidade):
                            if ($modalidade['status'] === 'OK'):
                                $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='" . $modalidade['valorfrete'] . "' type='radio' id='{$modalidade['shipcode']}'/> " . getShipmentTag(intval($modalidade['shipcode'])) . " - R$ " . number_format($modalidade['valorfrete'], '2', ',', '') . " - {$modalidade['prazoentrega']} dias úteis</label></li>";
                            endif;
                        endforeach;
                    else:
                        if ($CorreiosRetorno['status'] === 'OK'):
                            $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='" . $CorreiosRetorno['valorfrete'] . "' type='radio' id='{$CorreiosRetorno['shipcode']}'/> " . getShipmentTag(intval($CorreiosRetorno['shipcode'])) . " - R$ " . number_format($CorreiosRetorno['valorfrete'], '2', ',', '') . " - {$CorreiosRetorno['prazoentrega']} dias úteis</label></li>";
                        endif;
                    endif;
                endif;

                $CompanyPrice = $CartTotal * (ECOMMERCE_SHIPMENT_COMPANY_VAL / 100);
                if (ECOMMERCE_SHIPMENT_COMPANY && $CompanyPrice >= ECOMMERCE_SHIPMENT_COMPANY_PRICE && empty($ErroZip)):
                    $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='{$CompanyPrice}' type='radio' id='10001'/> Envio Padrão: 01 a " . str_pad(ECOMMERCE_SHIPMENT_DELAY + ECOMMERCE_SHIPMENT_COMPANY_DAYS, 2, 0, 0) . " dias úteis - R$ " . number_format($CompanyPrice, '2', ',', '.') . "</label></li>";
                endif;

                $CartPrice = (empty($_SESSION['wc_cupom']) ? $CartTotal : $CartTotal * ((100 - $_SESSION['wc_cupom']) / 100));
                if (ECOMMERCE_SHIPMENT_FREE && $CartPrice > ECOMMERCE_SHIPMENT_FREE && empty($ErroZip)):
                    $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='0' type='radio' id='10002'/> Envio Gratuito: 01 a " . str_pad(ECOMMERCE_SHIPMENT_DELAY + ECOMMERCE_SHIPMENT_FREE_DAYS, 2, 0, 0) . " dias úteis - R$ 0,00</label></li>";
                endif;

                if (ECOMMERCE_SHIPMENT_FIXED):
                    $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='" . ECOMMERCE_SHIPMENT_FIXED_PRICE . "' type='radio' id='10003'/> Frete Fixo: 01 a " . str_pad(ECOMMERCE_SHIPMENT_DELAY + ECOMMERCE_SHIPMENT_FIXED_DAYS, 2, 0, 0) . " dias úteis - R$ " . number_format(ECOMMERCE_SHIPMENT_FIXED_PRICE, 2, ',', '.') . "</label></li>";
                endif;

                if (ECOMMERCE_SHIPMENT_LOCAL):
                    $City = json_decode(file_get_contents("https://viacep.com.br/ws/" . str_replace('-', '', $ZipCode) . "/json/"));
                    if (!empty($City) && !empty($City->localidade) && $City->localidade == ECOMMERCE_SHIPMENT_LOCAL):
                        $jSON['cart_shipment'] = "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='" . ECOMMERCE_SHIPMENT_LOCAL_PRICE . "' type='radio' id='10004'/> Taxa de entrega: R$ " . number_format(ECOMMERCE_SHIPMENT_LOCAL_PRICE, 2, ',', '.') . "</label></li>";
                    endif;

                    if (ECOMMERCE_SHIPMENT_LOCAL_IN_PLACE):
                        $jSON['cart_shipment'] .= "<li><label class='shiptag'><input required class='wc_shipment' name='shipment' value='0' type='radio' id='10005'/> Retirar na Loja: R$ 0,00</label></li>";
                    endif;
                endif;

                if (empty($jSON['cart_shipment']) && empty($ErroZip)):
                    $jSON['trigger'] = AjaxErro("<b>OPPSSS:</b> Não existem opções de entrega para o pedido atual. Você pode remover ou adicionar alguns produtos para tentar novamente!<p>Ou caso queira, entre em contato para que possamos te ajudar!</p><p>Fone: " . SITE_ADDR_PHONE_A . "<br>E-mail: " . SITE_ADDR_EMAIL . "</p>", E_USER_WARNING);
                elseif (empty($ErroZip)):
                    $_SESSION['wc_shipment_zip'] = $ZipCode;
                endif;
            endif;

            $jSON['wc_cart_total'] = number_format($_SESSION['oderCreate']['order_price'], '2', ',', '.');
            $jSON['success'] = true;
            break;

        case 'AppOrderCreateNFe':
            if (!isset($_SESSION['oderCreate']['order_price']) && $_SESSION['oderCreate']['total']):
                $_SESSION['oderCreate']['order_price'] = $_SESSION['oderCreate']['total'];
                unset($_SESSION['oderCreate']['total']);
            endif;

            if (isset($_SESSION['oderCreate']["itens"])):
                if (!isset($_SESSION['oderCreate']['item']) && $_SESSION['oderCreate']["itens"]):
                    $_SESSION['oderCreate']['item'] = $_SESSION['oderCreate']["itens"];
                    unset($_SESSION['oderCreate']["itens"]);
                endif;

                if (isset($_SESSION['oderCreate']["client"])):
                    $OrderClient = $_SESSION['oderCreate']["client"];
                    unset($_SESSION['oderCreate']["client"]);
                endif;

                $OrderShip = 0;
                $OrderCupomValue = 0;

                $_SESSION['oderCreate']['order_paid'] = isset($_SESSION['oderCreate']['paid']) ? $_SESSION['oderCreate']['paid'] : (($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue);
                unset($_SESSION['oderCreate']['paid']);

                unset($_SESSION['oderCreate']["nfce"]);
                $OrderClient = $_SESSION['oderCreate']["client"];
                $OrderConfig = $_SESSION['oderCreate']["config"];
                unset($_SESSION['oderCreate']["config"], $_SESSION['oderCreate']["client"]);
                $OrderCreate = $_SESSION['oderCreate'];
                unset($OrderCreate['item'], $OrderCreate['addr_id'], $OrderCreate['total'], $OrderCreate["itens"], $OrderCreate["config"]);
                $OrderCreate['order_addr'] = isset($_SESSION['oderCreate']['addr_id']) ? $_SESSION['oderCreate']['addr_id'] : null;
                $OrderCreate['order_price'] = ($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue;
                $OrderCreate['order_status'] = 3;
                $OrderCreate['order_payment'] = 1;
                $OrderCreate['order_type'] = 4;
                $OrderCreate['order_date'] = date('Y-m-d H:i:s');
                $OrderCreate['order_update'] = date('Y-m-d H:i:s');
                $OrderCreate['order_update'] = date('Y-m-d H:i:s');
                $OrderCreate["tipo_documento"] = isset($OrderConfig["tipo_documento"]) ? ($OrderConfig["tipo_documento"] == 1 ? 1 : 0) : 1;
                $OrderCreate["local_destino"] = $OrderConfig["local_destino"];
                $OrderCreate["presenca_comprador"] = $OrderConfig["presenca_comprador"];
                $OrderCreate["order_observacao"] = $OrderConfig["order_observacao"];
                $OrderCreate["modalidade_frete"] = $OrderConfig["modalidade_frete"];
                $OrderCreate["finalidade_emissao"] = isset($OrderConfig["finalidade_emissao"]) ? $OrderConfig["finalidade_emissao"] : 1;

                if (isset($OrderClient)):
                    if (isset($OrderClient["client_id"])):
                        $OrderCreate['user_id'] = $OrderClient["client_id"];
                    else:
                        $OrderClientAdress["addr_name"] = "Novo Endereço";
                        $OrderClientAdress["addr_zipcode"] = $OrderClient["addr_zipcode"];
                        $OrderClientAdress["addr_street"] = $OrderClient["addr_street"];
                        $OrderClientAdress["addr_number"] = $OrderClient["addr_number"];
                        $OrderClientAdress["addr_district"] = $OrderClient["addr_district"];
                        $OrderClientAdress["addr_city"] = $OrderClient["addr_city"];
                        $OrderClientAdress["addr_state"] = $OrderClient["addr_state"];

                        $CreateUser = [
                            "user_name" => $OrderClient["user_name"],
                            "user_document" => $OrderClient["user_document"],
                            "user_cell" => $OrderClient["user_cell"],
                            "user_registration" => date('Y-m-d H:i:s'),
                            "user_level" => 1
                        ];
                        $Create->ExeCreate(DB_USERS, $CreateUser);
                        $OrderClientAdress["user_id"] = $Create->getResult();
                        $Create->ExeCreate(DB_USERS_ADDR, $CreateUser);
                        $OrderCreate['user_id'] = $Create->getResult();
                    endif;
                endif;
                $Create->ExeCreate(DB_ORDERS, $OrderCreate);
                $OrderId = $Create->getResult();
                $OrderCreateItem = array();
                foreach ($_SESSION['oderCreate']['item'] as $Item => $Qtd):
                    $Read->FullRead("SELECT pdt_id, pdt_title, pdt_price, pdt_offer_price, pdt_offer_start, pdt_offer_end FROM " . DB_PDT . " WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :st)", "st={$Item}");
                    if ($Read->getResult()):
                        extract($Read->getResult()[0]);
                        if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                            $PdtPrice = $pdt_offer_price;
                        else:
                            $PdtPrice = $pdt_price;
                        endif;

                        if (isset($Qtd["price"])):
                            $PdtPrice = $Qtd["price"];
                        endif;

                        if (isset($Qtd["amount"])):
                            $Qtd = $Qtd["amount"];
                        endif;

                        $OrderCreateItem[] = [
                            'order_id' => $OrderId,
                            'pdt_id' => $pdt_id,
                            'stock_id' => $Item,
                            'item_name' => $pdt_title,
                            'item_price' => $PdtPrice,
                            'item_amount' => $Qtd,
                        ];
                        $PostData['itens'][] = $pdt_id;
                        $PostData['quantidade'][] = $Qtd;
                        $PostData['valor'][] = $PdtPrice;
                        $PostData['descricao'][] = $pdt_title;
                        $PostData['unidade'][] = "un";
                    endif;
                endforeach;
                $Create->ExeCreateMulti(DB_ORDERS_ITEMS, $OrderCreateItem);

                //Remover do Estoque
                $n = 0;
                while ($n < count($OrderCreateItem)):
                    //Stock
                    $Read->FullRead("SELECT stock_inventory, stock_sold FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");
                    $UpdatePdtStock = [
                        'stock_inventory' => $Read->getResult()[0]['stock_inventory'] - $OrderCreateItem[$n]['item_amount'],
                        'stock_sold' => $Read->getResult()[0]['stock_sold'] + $OrderCreateItem[$n]['item_amount']
                    ];
                    $Update->ExeUpdate(DB_PDT_STOCK, $UpdatePdtStock, "WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");

                    //Product
                    $Read->FullRead("SELECT pdt_inventory FROM " . DB_PDT . " WHERE pdt_id = :id", "id={$OrderCreateItem[$n]['pdt_id']}");
                    $UpdatePdt = [
                        'pdt_inventory' => $Read->getResult()[0]['pdt_inventory'] - $OrderCreateItem[$n]['item_amount']
                    ];
                    $Update->ExeUpdate(DB_PDT, $UpdatePdt, "WHERE pdt_id = :id", "id={$OrderCreateItem[$n]['pdt_id']}");
                    $n++;
                endwhile;

                //CONFIG GLOBAL - PESQUISAR DADOS DO EMISSOR
                $Read->ExeRead(DB_CFG_SAAS, "WHERE config_id = 1");
                $emissor["empresa"] = $Read->getResult()[0];

                $Read->ExeRead(DB_CFG_SAAS_ADDR, "WHERE config_id = 1");
                $emissor["endereco"] = $Read->getResult()[0];

                $Read->ExeRead(DB_CFG_SAAS_NFE, "WHERE config_id = 1");
                $emissor["nfe"] = $Read->getResult()[0];

                $server = $emissor["nfe"]["nfe_env"] == 1 ? "https://api.focusnfe.com.br" : "https://homologacao.focusnfe.com.br";
                $login = $emissor["nfe"]["nfe_env"] == 1 ? $emissor["nfe"]["nfe_focus_pd_token"] : $emissor["nfe"]["nfe_focus_hm_token"];
                $password = $emissor["nfe"]["nfe_focus_password"];

                $ref = $OrderId;

                $n1 = 0;
                $jSON['redirect'] = "dashboard.php?wc=orders/nfe&id={$OrderId}";

                $n2 = 0;
                $valor_total = 0;
                while ($n2 < count($PostData['itens'])):
                    $Read->ExeRead(DB_PDT_NFE, "WHERE pdt_id = :id", "id={$PostData['itens'][$n2]}");
                    if (!$Read->getResult()):
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">PRODUTO NÃO Configurado:</b> O produto ' . $PostData['descricao'][$n2] . ' não possui dados para gerar NFCe. Por favor, configure!', E_USER_ERROR);
                        //$jSON['redirect'] = "dashboard.php?wc=products/create&id={$PostData['itens'][$n2]}&cfg=1";
                        $jSON['error'] = true;
                    else:
                        $itens[$n1]["codigo_ncm"] = $Read->getResult()[0]["nfe_ncm"];
                        $itens[$n1]["cfop"] = $Read->getResult()[0]["nfe_cfop"];
                        $itens[$n1]["icms_situacao_tributaria"] = $Read->getResult()[0]["nfe_csosn"];
                        $itens[$n1]["icms_origem"] = $Read->getResult()[0]["nfe_tx_icms"];
                    endif;

                    $itens[$n1]["numero_item"] = $n1 + 1;
                    $itens[$n1]["codigo_produto"] = $PostData['itens'][$n2];
                    $itens[$n1]["descricao"] = $PostData['descricao'][$n2];
                    $itens[$n1]["unidade_comercial"] = $PostData['unidade'][$n2];
                    $itens[$n1]["quantidade_comercial"] = $PostData['quantidade'][$n2];
                    $itens[$n1]["valor_unitario_tributavel"] = $PostData['valor'][$n2];
                    $itens[$n1]["valor_unitario_comercial"] = $PostData['valor'][$n2];
                    $itens[$n1]["unidade_tributavel"] = $PostData['unidade'][$n2];
                    $itens[$n1]["quantidade_tributavel"] = $PostData['quantidade'][$n2];
                    $itens[$n1]["valor_bruto"] = $PostData['valor'][$n2];
                    $itens[$n1]["pis_situacao_tributaria"] = "07";
                    $itens[$n1]["cofins_situacao_tributaria"] = "07";
                    $valor_total += ($PostData['valor'][$n2] * $PostData['quantidade'][$n2]);
                    $n2++;
                endwhile;

                if (!isset($jSON['error'])):
                    $nfe = array(
                        "data_emissao" => date("Y-m-d") . "T" . date("H:i:s"),
                        "data_entrada_saida" => date("Y-m-d") . "T" . date("H:i:s"),
                        "modalidade_frete" => $OrderCreate["modalidade_frete"],
                        "natureza_operacao" => $OrderConfig['natureza_operacao'],
                        "local_destino" => $OrderCreate["local_destino"],
                        "presenca_comprador" => $OrderCreate["presenca_comprador"],
                        "order_observacao" => $OrderCreate["order_observacao"],
                        "tipo_documento" => $OrderCreate["tipo_documento"],
                        "finalidade_emissao" => $OrderCreate["finalidade_emissao"],
                        "cnpj_emitente" => str_replace(".", "", str_replace("/", "", str_replace("-", "", str_replace(" ", "", $emissor["empresa"]["config_cnpj"])))),
                        "inscricao_estadual_emitente" => str_replace(".", "", str_replace("/", "", str_replace("-", "", str_replace(" ", "", $emissor["empresa"]["config_cnpj_insc_est"])))),
                        "nome_emitente" => $emissor["empresa"]["config_name_social"],
                        "nome_fantasia_emitente" => $emissor["empresa"]["config_name_fantasia"],
                        "logradouro_emitente" => $emissor["endereco"]["addr_street"],
                        "numero_emitente" => $emissor["endereco"]["addr_number"],
                        "bairro_emitente" => $emissor["endereco"]["addr_district"],
                        "municipio_emitente" => $emissor["endereco"]["addr_city"],
                        "uf_emitente" => $emissor["endereco"]["addr_state"],
                        "cep_emitente" => $emissor["endereco"]["addr_zipcode"],
                        "nome_destinatario" => $emissor["nfe"]["nfe_env"] == 1 ? $OrderClient["user_name"] : "NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL",
                        "cpf_destinatario" => $OrderClient["user_document"],
                        "telefone_destinatario" => $OrderClient["user_document"],
                        "logradouro_destinatario" => $OrderClient["addr_street"],
                        "numero_destinatario" => $OrderClient["addr_number"],
                        "bairro_destinatario" => $OrderClient["addr_district"],
                        "municipio_destinatario" => $OrderClient["addr_city"],
                        "uf_destinatario" => $OrderClient["addr_state"],
                        "pais_destinatario" => "Brasil",
                        "cep_destinatario" => $OrderClient["addr_zipcode"],
                        "valor_frete" => "0.0",
                        "valor_seguro" => "0",
                        //"valor_total" => $valor_total,
                        //"valor_produtos" => $valor_total,
                        "itens" => $itens
                    );

                    // Inicia o processo de envio das informações usando o cURL.
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $server . "/v2/nfe?ref=" . $ref);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($nfe));
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
                    $result = (array) json_decode(curl_exec($ch));
                    $result_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);


                    if ($result_code == 202):
                        if ($result["status"] == "processando_autorizacao"):
                            $jSON['trigger'] = AjaxErro('<b class="icon-warning">PROCESSANDO NFE:</b> Retorne daqui a pouco.');
                            $jSON['redirect'] = "dashboard.php?wc=orders/nfe&id={$OrderId}";
                        endif;

                        if ($result["status"] != "processando_autorizacao"):
                            var_dump($result, $result_code);
                        endif;
                    endif;
                    /*
                      if ($result_code == 422):
                      if ($result["codigo"] == "already_processed"):
                      $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b> A nota fiscal já foi autorizada!', E_USER_ERROR);
                      endif;

                      if (isset($result["erros"])):
                      $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b> ' . $result["erros"][0]->mensagem, E_USER_ERROR);
                      endif;
                      endif;

                      if ($result_code == 201):
                      $CreateNFCE = [
                      "order_id" => $OrderId,
                      "nfce_serie" => isset($result["serie"]) ? $result["serie"] : "",
                      "nfce_numero" => isset($result["numero"]) ? $result["numero"] : "",
                      "nfce_chave" => isset($result["chave_nfe"]) ? $result["chave_nfe"] : "",
                      "nfce_xml" => isset($result["caminho_xml_nota_fiscal"]) ? $server . $result["caminho_xml_nota_fiscal"] : "",
                      "nfce_danfe" => isset($result["caminho_danfe"]) ? $server . $result["caminho_danfe"] : "",
                      "nfce_qrcode" => isset($result["qrcode_url"]) ? $result["qrcode_url"] : "",
                      "nfce_status" => $result["status"]
                      ];
                      $Create->ExeCreate(DB_ORDERS_NFCE, $CreateNFCE);

                      if ($result["status"] == "autorizado"):
                      $jSON['trigger'] = AjaxErro('<b class="icon-warning">NFCE CRIADA:</b>' . $result["mensagem_sefaz"]);
                      $jSON['redirect'] = "dashboard.php?wc=orders/pdv&id={$OrderId}&act=print";
                      if ($CreateNFCE["nfce_xml"]):
                      $jSON['redirect'] = "dashboard.php?wc=orders/pdv";
                      $jSON['printer'] = $CreateNFCE["nfce_xml"];
                      endif;
                      else:
                      $jSON['redirect'] = "dashboard.php?wc=orders/home";
                      $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b>' . $result["mensagem_sefaz"], E_USER_ERROR);
                      endif;
                      endif;
                     */

                    $finanLanc = [
                        'category_id' => 13,
                        'fin_type' => "rec",
                        'order_id' => $OrderId,
                        'fin_title' => "Venda Pedido #{$OrderId}",
                        'fin_value' => $OrderCreate['order_price'],
                        'fin_date' => $OrderCreate['order_date'],
                        'fin_due_date' => $OrderCreate['order_date'],
                        'fin_payment_form' => 1,
                        'fin_split' => 1,
                        'fin_author' => $_SESSION['userLogin']['user_id'],
                        'fin_status' => 1
                    ];

                    switch ($OrderCreate['order_payment_mode']):
                        case 1:
                            $Create->ExeCreate(DB_FINAN, $finanLanc);
                            if ($Create->getResult()):
                                $FinanId = $Create->getResult();
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'],
                                    'fin_split_date' => $OrderCreate['order_date'],
                                    'fin_split_number' => 1,
                                    'fin_split_method' => 1,
                                    'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            endif;
                            break;
                        case 2:
                            $finanLanc["fin_payment_form"] = 2;
                            $Create->ExeCreate(DB_FINAN, $finanLanc);
                            if ($Create->getResult()):
                                $FinanId = $Create->getResult();
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'],
                                    'fin_split_date' => $OrderCreate['order_date'],
                                    'fin_split_number' => 1,
                                    'fin_split_method' => 3,
                                    'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            endif;
                            break;
                        case 3:
                        case 4:
                        case 6:
                            $finanLanc["fin_payment_form"] = 2;
                            $finanLanc["fin_split"] = 2;
                            $Create->ExeCreate(DB_FINAN, $finanLanc);
                            if ($Create->getResult()):
                                $FinanId = $Create->getResult();
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'] / 2,
                                    'fin_split_date' => $OrderCreate['order_date'],
                                    'fin_split_number' => 1,
                                    'fin_split_method' => 3,
                                    'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'] / 2,
                                    'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                    'fin_split_number' => 2,
                                    'fin_split_method' => 3,
                                    'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            endif;
                            break;
                        case 5:
                        case 8:
                            $finanLanc["fin_payment_form"] = 2;
                            $finanLanc["fin_split"] = 3;
                            $Create->ExeCreate(DB_FINAN, $finanLanc);
                            if ($Create->getResult()):
                                $FinanId = $Create->getResult();
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'] / 3,
                                    'fin_split_date' => $OrderCreate['order_date'],
                                    'fin_split_number' => 1,
                                    'fin_split_method' => 3,
                                    'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'] / 3,
                                    'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                    'fin_split_number' => 2,
                                    'fin_split_method' => 3,
                                    'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'] / 3,
                                    'fin_split_date' => date('Y-m-d', strtotime('+2 month', strtotime($OrderCreate['order_date']))),
                                    'fin_split_number' => 3,
                                    'fin_split_method' => 3,
                                    'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            endif;
                            break;
                            break;
                        case 7:
                            $finanLanc["fin_payment_form"] = 2;
                            $Create->ExeCreate(DB_FINAN, $finanLanc);
                            if ($Create->getResult()):
                                $FinanId = $Create->getResult();
                                $finanParc = [
                                    'fin_id' => $FinanId,
                                    'fin_split_price' => $OrderCreate['order_price'],
                                    'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                    'fin_split_number' => 1,
                                    'fin_split_method' => 3,
                                    'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                ];
                                $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            endif;
                            break;
                    endswitch;

                //endif;
                else:
                    $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO TRANSMITIR NFE:</b> Tente novamente.', E_USER_ERROR);
                endif;
            //unset($jSON['redirect']);

            else:
                $jSON['trigger'] = AjaxErro('<b class="icon-warning">NENHUM PRODUTO:</b> Tente novamente.', E_USER_ERROR);
                $jSON['redirect'] = "dashboard.php?wc=orders/nfe";
            endif;
            break;

        case 'AppOrderCreateNFCe':
            if (isset($_SESSION["reenvia_id"])):
                $Read->ExeRead(DB_ORDERS_ITEMS, "WHERE order_id = :order", "order={$_SESSION["reenvia_id"]}");
                foreach ($Read->getResult() as $Item):
                    $itens[] = $Item;
                endforeach;

                $Read->ExeRead(DB_ORDERS, "WHERE order_id = :order", "order={$_SESSION["reenvia_id"]}");
                $OrderCreate = $Read->getResult()[0];

                $Read->ExeRead(DB_ORDERS_PAY, "WHERE pay_id = {$OrderCreate['order_payment']}");
                $OrderCreate['forma_pagamento'] = $Read->getResult()[0]["pay_nfe"];

                //CONFIG GLOBAL - PESQUISAR DADOS DO EMISSOR
                $Read->ExeRead(DB_CFG_SAAS, "WHERE config_id = 1");
                $emissor["empresa"] = $Read->getResult()[0];

                $Read->ExeRead(DB_CFG_SAAS_NFE, "WHERE config_id = 1");
                $emissor["nfe"] = $Read->getResult()[0];

                $server = $emissor["nfe"]["nfe_env"] == 1 ? "https://api.focusnfe.com.br" : "https://homologacao.focusnfe.com.br";
                $login = $emissor["nfe"]["nfe_env"] == 1 ? $emissor["nfe"]["nfe_focus_pd_token"] : $emissor["nfe"]["nfe_focus_hm_token"];
                $password = $emissor["nfe"]["nfe_focus_password"];

                $OrderCreate['indicador_inscricao_estadual_destinatario'] = 9;

                $ref = $_SESSION["reenvia_id"];
                $nfe = array(
                    "cnpj_emitente" => str_replace(".", "", str_replace("/", "", str_replace("-", "", str_replace(" ", "", $emissor["empresa"]["config_cnpj"])))),
                    "data_emissao" => date("Y-m-d") . "T" . date("H:i:s"),
                    "indicador_inscricao_estadual_destinatario" => $OrderCreate['indicador_inscricao_estadual_destinatario'],
                    "modalidade_frete" => $OrderCreate['modalidade_frete'],
                    "local_destino" => $OrderCreate['local_destino'],
                    "presenca_comprador" => $OrderCreate['presenca_comprador'],
                    "natureza_operacao" => "VENDA AO CONSUMIDOR",
                    "valor_troco" => ($OrderCreate['order_paid'] - $OrderCreate['order_price']),
                    "tipo_integracao" => 2,
                    "itens" => $itens,
                    "formas_pagamento" => array(
                        array(
                            "forma_pagamento" => $OrderCreate['forma_pagamento'],
                            "valor_pagamento" => $OrderCreate['order_paid'],
                            //"nome_credenciadora" => "Cielo",
                            "bandeira_operadora" => ($OrderCreate['forma_pagamento'] == "03" || $OrderCreate['forma_pagamento'] == "04") ? "02" : "",
                        //"numero_autorizacao" => "R07242"
                        )
                    ),
                );

                // Inicia o processo de envio das informações usando o cURL.
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $server . "/v2/nfce?ref=" . $ref);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($nfe));
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
                $result = (array) json_decode(curl_exec($ch));
                $result_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($result_code == 422):
                    if ($result["codigo"] == "already_processed"):
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b> A nota fiscal já foi autorizada!', E_USER_ERROR);
                    endif;

                    if (isset($result["erros"])):
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b> ' . $result["erros"][0]->mensagem, E_USER_ERROR);
                    endif;
                endif;

                if ($result_code == 201):
                    $CreateNFCE = [
                        "order_id" => $OrderId,
                        "nfce_serie" => isset($result["serie"]) ? $result["serie"] : "",
                        "nfce_numero" => isset($result["numero"]) ? $result["numero"] : "",
                        "nfce_chave" => isset($result["chave_nfe"]) ? $result["chave_nfe"] : "",
                        "nfce_xml" => isset($result["caminho_xml_nota_fiscal"]) ? $server . $result["caminho_xml_nota_fiscal"] : "",
                        "nfce_danfe" => isset($result["caminho_danfe"]) ? $server . $result["caminho_danfe"] : "",
                        "nfce_qrcode" => isset($result["qrcode_url"]) ? $result["qrcode_url"] : "",
                        "nfce_status" => $result["status"]
                    ];
                    $Create->ExeCreate(DB_ORDERS_NFCE, $CreateNFCE);

                    if ($result["status"] == "autorizado"):
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">NFCE CRIADA:</b>' . $result["mensagem_sefaz"]);
                        $jSON['redirect'] = "dashboard.php?wc=orders/pdv&id={$OrderId}&act=print";
                        if ($CreateNFCE["nfce_xml"]):
                            $jSON['redirect'] = "dashboard.php?wc=orders/pdv";
                            $jSON['printer'] = $CreateNFCE["nfce_danfe"];
                        endif;
                    else:
                        $jSON['redirect'] = "dashboard.php?wc=orders/home";
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b>' . $result["mensagem_sefaz"], E_USER_ERROR);
                    endif;
                endif;
            else:

                if (!isset($_SESSION['oderCreate']['order_price']) && $_SESSION['oderCreate']['total']):
                    $_SESSION['oderCreate']['order_price'] = $_SESSION['oderCreate']['total'];
                    unset($_SESSION['oderCreate']['total']);
                endif;

                if (!isset($_SESSION['oderCreate']['item']) && $_SESSION['oderCreate']["itens"]):
                    $_SESSION['oderCreate']['item'] = $_SESSION['oderCreate']["itens"];
                    unset($_SESSION['oderCreate']["itens"]);
                endif;

                if (isset($_SESSION['oderCreate']["client"])):
                    $OrderClient = $_SESSION['oderCreate']["client"];
                    unset($_SESSION['oderCreate']["client"]);
                endif;

                $OrderShip = 0;
                $OrderCupomValue = 0;

                $_SESSION['oderCreate']['order_paid'] = isset($_SESSION['oderCreate']['paid']) ? $_SESSION['oderCreate']['paid'] : (($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue);
                unset($_SESSION['oderCreate']['paid']);

                unset($_SESSION['oderCreate']["nfce"]);
                $OrderCreate = $_SESSION['oderCreate'];
                $OrderConfig = $_SESSION['oderCreate']["config"];
                unset($OrderCreate['item'], $OrderCreate['addr_id'], $OrderCreate['total'], $OrderCreate["itens"], $OrderCreate["config"]);
                $OrderCreate['order_addr'] = isset($_SESSION['oderCreate']['addr_id']) ? $_SESSION['oderCreate']['addr_id'] : null;
                $OrderCreate['order_price'] = ($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue;
                $OrderCreate['order_status'] = 3;
                $OrderCreate['order_payment'] = 1;
                $OrderCreate['order_type'] = 3;
                $OrderCreate['order_date'] = date('Y-m-d H:i:s');
                $OrderCreate['order_update'] = date('Y-m-d H:i:s');

                if (isset($OrderClient)):
                    if (isset($OrderClient["client_id"])):
                        $OrderCreate['user_id'] = $OrderClient["client_id"];
                    else:
                        $OrderClientAdress["addr_name"] = "Novo Endereço";
                        $OrderClientAdress["addr_zipcode"] = $OrderClient["addr_zipcode"];
                        $OrderClientAdress["addr_street"] = $OrderClient["addr_street"];
                        $OrderClientAdress["addr_number"] = $OrderClient["addr_number"];
                        $OrderClientAdress["addr_district"] = $OrderClient["addr_district"];
                        $OrderClientAdress["addr_city"] = $OrderClient["addr_city"];
                        $OrderClientAdress["addr_state"] = $OrderClient["addr_state"];

                        $CreateUser = [
                            "user_name" => $OrderClient["user_name"],
                            "user_document" => $OrderClient["user_document"],
                            "user_cell" => $OrderClient["user_cell"],
                            "user_registration" => date('Y-m-d H:i:s'),
                            "user_level" => 1
                        ];
                        $Create->ExeCreate(DB_USERS, $CreateUser);
                        $OrderClientAdress["user_id"] = $Create->getResult();
                        $Create->ExeCreate(DB_USERS_ADDR, $CreateUser);
                        $OrderCreate['user_id'] = $Create->getResult();
                    endif;
                endif;
                $Create->ExeCreate(DB_ORDERS, $OrderCreate);
                $OrderId = $Create->getResult();
                $OrderCreateItem = array();
                foreach ($_SESSION['oderCreate']['item'] as $Item => $Qtd):
                    $Read->FullRead("SELECT pdt_id, pdt_title, pdt_price, pdt_offer_price, pdt_offer_start, pdt_offer_end FROM " . DB_PDT . " WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :st)", "st={$Item}");
                    if ($Read->getResult()):
                        extract($Read->getResult()[0]);
                        if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                            $PdtPrice = $pdt_offer_price;
                        else:
                            $PdtPrice = $pdt_price;
                        endif;

                        if (isset($Qtd["price"])):
                            $PdtPrice = $Qtd["price"];
                        endif;

                        if (isset($Qtd["amount"])):
                            $Qtd = $Qtd["amount"];
                        endif;

                        $OrderCreateItem[] = [
                            'order_id' => $OrderId,
                            'pdt_id' => $pdt_id,
                            'stock_id' => $Item,
                            'item_name' => $pdt_title,
                            'item_price' => $PdtPrice,
                            'item_amount' => $Qtd,
                        ];
                        $PostData['itens'][] = $pdt_id;
                        $PostData['quantidade'][] = $Qtd;
                        $PostData['valor'][] = $PdtPrice;
                        $PostData['descricao'][] = $pdt_title;
                        $PostData['unidade'][] = "un";
                    endif;
                endforeach;
                $Create->ExeCreateMulti(DB_ORDERS_ITEMS, $OrderCreateItem);

                //Remover do Estoque
                $n = 0;
                while ($n < count($OrderCreateItem)):
                    //Stock
                    $Read->FullRead("SELECT stock_inventory, stock_sold FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");
                    $UpdatePdtStock = [
                        'stock_inventory' => $Read->getResult()[0]['stock_inventory'] - $OrderCreateItem[$n]['item_amount'],
                        'stock_sold' => $Read->getResult()[0]['stock_sold'] + $OrderCreateItem[$n]['item_amount']
                    ];
                    $Update->ExeUpdate(DB_PDT_STOCK, $UpdatePdtStock, "WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");

                    //Product
                    $Read->FullRead("SELECT pdt_inventory FROM " . DB_PDT . " WHERE pdt_id = :id", "id={$OrderCreateItem[$n]['pdt_id']}");
                    $UpdatePdt = [
                        'pdt_inventory' => $Read->getResult()[0]['pdt_inventory'] - $OrderCreateItem[$n]['item_amount']
                    ];
                    $Update->ExeUpdate(DB_PDT, $UpdatePdt, "WHERE pdt_id = :id", "id={$OrderCreateItem[$n]['pdt_id']}");
                    $n++;
                endwhile;

                if (!isset($PostData["modalidade_frete"])):
                    $PostData["modalidade_frete"] = $OrderConfig["modalidade_frete"];
                endif;

                if (!isset($PostData["local_destino"])):
                    $PostData["local_destino"] = $OrderConfig["local_destino"];
                endif;

                if (!isset($PostData["presenca_comprador"])):
                    $PostData["presenca_comprador"] = $OrderConfig["presenca_comprador"];
                endif;
                /*
                  if (!isset($PostData["order_observacao"])):
                  $PostData["order_observacao"] = $OrderConfig["order_observacao"];
                  endif; */

                if (!isset($PostData["natureza_operacao"])):
                    $PostData["natureza_operacao"] = $OrderConfig["natureza_operacao"];
                endif;

                if ($PostData['itens'] && isset($PostData['modalidade_frete']) && isset($PostData['local_destino']) && isset($PostData['presenca_comprador']) && isset($PostData['natureza_operacao'])):

                    $PostData['indicador_inscricao_estadual_destinatario'] = 9;

                    //CONFIG GLOBAL - PESQUISAR DADOS DO EMISSOR
                    $Read->ExeRead(DB_CFG_SAAS, "WHERE config_id = 1");
                    $emissor["empresa"] = $Read->getResult()[0];

                    $Read->ExeRead(DB_CFG_SAAS_NFE, "WHERE config_id = 1");
                    $emissor["nfe"] = $Read->getResult()[0];

                    $server = $emissor["nfe"]["nfe_env"] == 1 ? "https://api.focusnfe.com.br" : "https://homologacao.focusnfe.com.br";
                    $login = $emissor["nfe"]["nfe_env"] == 1 ? $emissor["nfe"]["nfe_focus_pd_token"] : $emissor["nfe"]["nfe_focus_hm_token"];
                    $password = $emissor["nfe"]["nfe_focus_password"];

                    $ref = $OrderId;

                    $n1 = 0;
                    if ($emissor["nfe"]["nfe_env"] == 0):
                        $itens[$n1]["numero_item"] = $n1 + 1;
                        $itens[$n1]["codigo_ncm"] = "62044200";
                        $itens[$n1]["quantidade_comercial"] = "1.00";
                        $itens[$n1]["quantidade_tributavel"] = "1.00";
                        $itens[$n1]["cfop"] = "5102";
                        $itens[$n1]["valor_unitario_tributavel"] = "0.00";
                        $itens[$n1]["valor_unitario_comercial"] = "0.00";
                        $itens[$n1]["valor_desconto"] = "0.00";
                        $itens[$n1]["descricao"] = "NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL";
                        $itens[$n1]["codigo_produto"] = "1";
                        $itens[$n1]["icms_origem"] = "0";
                        $itens[$n1]["icms_situacao_tributaria"] = "102";
                        $itens[$n1]["unidade_comercial"] = "un";
                        $itens[$n1]["unidade_tributavel"] = "un";
                        $itens[$n1]["valor_total_tributos"] = "0.00";
                        $n1++;
                    endif;

                    $jSON['redirect'] = "dashboard.php?wc=orders/pdv&id={$OrderId}";

                    $n2 = 0;
                    while ($n2 < count($PostData['itens'])):
                        $Read->ExeRead(DB_PDT_NFE, "WHERE pdt_id = :id", "id={$PostData['itens'][$n2]}");
                        if (!$Read->getResult()):
                            $jSON['trigger'] = AjaxErro('<b class="icon-warning">PRODUTO NÃO Configurado:</b> O produto ' . $PostData['descricao'][$n2] . ' não possui dados para gerar NFCe. Por favor, configure!', E_USER_ERROR);
                            //$jSON['redirect'] = "dashboard.php?wc=products/create&id={$PostData['itens'][$n2]}&cfg=1";
                            $jSON['error'] = true;
                        else:
                            $itens[$n1]["codigo_ncm"] = $Read->getResult()[0]["nfe_ncm"];
                            $itens[$n1]["cfop"] = $Read->getResult()[0]["nfe_cfop"];
                            $itens[$n1]["icms_situacao_tributaria"] = $Read->getResult()[0]["nfe_csosn"];
                            $itens[$n1]["icms_origem"] = $Read->getResult()[0]["nfe_tx_icms"];
                        endif;

                        $itens[$n1]["numero_item"] = $n1 + 1;
                        $itens[$n1]["quantidade_comercial"] = $PostData['quantidade'][$n2];
                        $itens[$n1]["quantidade_tributavel"] = $PostData['quantidade'][$n2];
                        $itens[$n1]["valor_unitario_tributavel"] = $PostData['valor'][$n2];
                        $itens[$n1]["valor_unitario_comercial"] = $PostData['valor'][$n2];
                        $itens[$n1]["valor_desconto"] = "0.00";
                        $itens[$n1]["descricao"] = $PostData['descricao'][$n2];
                        $itens[$n1]["codigo_produto"] = $PostData['itens'][$n2];
                        $itens[$n1]["unidade_comercial"] = $PostData['unidade'][$n2];
                        $itens[$n1]["unidade_tributavel"] = $PostData['unidade'][$n2];
                        $itens[$n1]["valor_total_tributos"] = "1.00";
                        $n2++;
                    endwhile;

                    $Read->ExeRead(DB_ORDERS_PAY, "WHERE pay_id = {$OrderCreate['order_payment']}");
                    $PostData['forma_pagamento'] = $Read->getResult()[0]["pay_nfe"];

                    if (!isset($jSON['error'])):
                        $nfe = array(
                            "cnpj_emitente" => str_replace(".", "", str_replace("/", "", str_replace("-", "", str_replace(" ", "", $emissor["empresa"]["config_cnpj"])))),
                            "data_emissao" => date("Y-m-d") . "T" . date("H:i:s"),
                            "indicador_inscricao_estadual_destinatario" => $PostData['indicador_inscricao_estadual_destinatario'],
                            "modalidade_frete" => $PostData['modalidade_frete'],
                            "local_destino" => $PostData['local_destino'],
                            "presenca_comprador" => $PostData['presenca_comprador'],
                            "natureza_operacao" => $PostData['natureza_operacao'],
                            "valor_troco" => ($OrderCreate['order_paid'] - $OrderCreate['order_price']),
                            "tipo_integracao" => 2,
                            "itens" => $itens,
                            "formas_pagamento" => array(
                                array(
                                    "forma_pagamento" => $PostData['forma_pagamento'],
                                    "valor_pagamento" => $OrderCreate['order_paid'],
                                    //"nome_credenciadora" => "Cielo",
                                    "bandeira_operadora" => ($PostData['forma_pagamento'] == "03" || $PostData['forma_pagamento'] == "04") ? "02" : "",
                                //"numero_autorizacao" => "R07242"
                                )
                            ),
                        );

                        // Inicia o processo de envio das informações usando o cURL.
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $server . "/v2/nfce?ref=" . $ref);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($nfe));
                        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                        curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
                        $result = (array) json_decode(curl_exec($ch));
                        $result_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($result_code == 422):
                            if ($result["codigo"] == "already_processed"):
                                $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b> A nota fiscal já foi autorizada!', E_USER_ERROR);
                            endif;

                            if (isset($result["erros"])):
                                $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b> ' . $result["erros"][0]->mensagem, E_USER_ERROR);
                            endif;
                        endif;

                        if ($result_code == 201):
                            $CreateNFCE = [
                                "order_id" => $OrderId,
                                "nfce_serie" => isset($result["serie"]) ? $result["serie"] : "",
                                "nfce_numero" => isset($result["numero"]) ? $result["numero"] : "",
                                "nfce_chave" => isset($result["chave_nfe"]) ? $result["chave_nfe"] : "",
                                "nfce_xml" => isset($result["caminho_xml_nota_fiscal"]) ? $server . $result["caminho_xml_nota_fiscal"] : "",
                                "nfce_danfe" => isset($result["caminho_danfe"]) ? $server . $result["caminho_danfe"] : "",
                                "nfce_qrcode" => isset($result["qrcode_url"]) ? $result["qrcode_url"] : "",
                                "nfce_status" => $result["status"]
                            ];
                            $Create->ExeCreate(DB_ORDERS_NFCE, $CreateNFCE);

                            if ($result["status"] == "autorizado"):
                                $jSON['trigger'] = AjaxErro('<b class="icon-warning">NFCE CRIADA:</b>' . $result["mensagem_sefaz"]);
                                $jSON['redirect'] = "dashboard.php?wc=orders/pdv&id={$OrderId}&act=print";
                                if ($CreateNFCE["nfce_xml"]):
                                    $jSON['redirect'] = "dashboard.php?wc=orders/pdv";
                                    $jSON['printer'] = $CreateNFCE["nfce_danfe"];
                                endif;
                            else:
                                $jSON['redirect'] = "dashboard.php?wc=orders/home";
                                $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO GERAR NFCE:</b>' . $result["mensagem_sefaz"], E_USER_ERROR);
                            endif;
                        endif;

                        $finanLanc = [
                            'category_id' => 12,
                            'fin_type' => "rec",
                            'order_id' => $OrderId,
                            'fin_title' => "Venda NFCE #{$OrderId}",
                            'fin_value' => $OrderCreate['order_price'],
                            'fin_date' => $OrderCreate['order_date'],
                            'fin_due_date' => $OrderCreate['order_date'],
                            'fin_payment_form' => 1,
                            'fin_split' => 1,
                            'fin_author' => $_SESSION['userLogin']['user_id'],
                            'fin_status' => 1
                        ];

                        switch ($OrderCreate['order_payment_mode']):
                            case 1:
                                $Create->ExeCreate(DB_FINAN, $finanLanc);
                                if ($Create->getResult()):
                                    $FinanId = $Create->getResult();
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'],
                                        'fin_split_date' => $OrderCreate['order_date'],
                                        'fin_split_number' => 1,
                                        'fin_split_method' => 1,
                                        'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                endif;
                                break;
                            case 2:
                                $finanLanc["fin_payment_form"] = 2;
                                $Create->ExeCreate(DB_FINAN, $finanLanc);
                                if ($Create->getResult()):
                                    $FinanId = $Create->getResult();
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'],
                                        'fin_split_date' => $OrderCreate['order_date'],
                                        'fin_split_number' => 1,
                                        'fin_split_method' => 3,
                                        'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                endif;
                                break;
                            case 3:
                            case 4:
                            case 6:
                                $finanLanc["fin_payment_form"] = 2;
                                $finanLanc["fin_split"] = 2;
                                $Create->ExeCreate(DB_FINAN, $finanLanc);
                                if ($Create->getResult()):
                                    $FinanId = $Create->getResult();
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'] / 2,
                                        'fin_split_date' => $OrderCreate['order_date'],
                                        'fin_split_number' => 1,
                                        'fin_split_method' => 3,
                                        'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'] / 2,
                                        'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                        'fin_split_number' => 2,
                                        'fin_split_method' => 3,
                                        'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                endif;
                                break;
                            case 5:
                            case 8:
                                $finanLanc["fin_payment_form"] = 2;
                                $finanLanc["fin_split"] = 3;
                                $Create->ExeCreate(DB_FINAN, $finanLanc);
                                if ($Create->getResult()):
                                    $FinanId = $Create->getResult();
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'] / 3,
                                        'fin_split_date' => $OrderCreate['order_date'],
                                        'fin_split_number' => 1,
                                        'fin_split_method' => 3,
                                        'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'] / 3,
                                        'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                        'fin_split_number' => 2,
                                        'fin_split_method' => 3,
                                        'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'] / 3,
                                        'fin_split_date' => date('Y-m-d', strtotime('+2 month', strtotime($OrderCreate['order_date']))),
                                        'fin_split_number' => 3,
                                        'fin_split_method' => 3,
                                        'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                endif;
                                break;
                                break;
                            case 7:
                                $finanLanc["fin_payment_form"] = 2;
                                $Create->ExeCreate(DB_FINAN, $finanLanc);
                                if ($Create->getResult()):
                                    $FinanId = $Create->getResult();
                                    $finanParc = [
                                        'fin_id' => $FinanId,
                                        'fin_split_price' => $OrderCreate['order_price'],
                                        'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                        'fin_split_number' => 1,
                                        'fin_split_method' => 3,
                                        'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                                    ];
                                    $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                                endif;
                                break;
                        endswitch;
                    else:
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO TRANSMITIR NFCE:</b> Tente novamente.', E_USER_ERROR);
                    endif;
                else:
                    $jSON['trigger'] = AjaxErro('<b class="icon-warning">ERRO AO TRANSMITIR NFCE:</b> Tente novamente.', E_USER_ERROR);
                endif;
            //unset($jSON['redirect']);
            endif;
            break;

        case 'AppOrderCreatePed':
            //var_dump($_SESSION['oderCreate']);
            if (!isset($_SESSION['oderCreate']['order_price']) && $_SESSION['oderCreate']['total']):
                $_SESSION['oderCreate']['order_price'] = $_SESSION['oderCreate']['total'];
                unset($_SESSION['oderCreate']['total']);
            endif;

            if (!isset($_SESSION['oderCreate']['item']) && $_SESSION['oderCreate']["itens"]):
                $_SESSION['oderCreate']['item'] = $_SESSION['oderCreate']["itens"];
                unset($_SESSION['oderCreate']["itens"]);
            endif;

            if (isset($_SESSION['oderCreate']["client"])):
                $OrderClient = $_SESSION['oderCreate']["client"];
                unset($_SESSION['oderCreate']["client"]);
            endif;

            if (isset($_SESSION['oderCreate']['config']['order_observacao'])):
                $_SESSION['oderCreate']['order_observacao'] = $_SESSION['oderCreate']['config']['order_observacao'];
            endif;
            unset($_SESSION['oderCreate']['config']);

            $OrderShip = 0;
            $OrderCupomValue = 0;

            $_SESSION['oderCreate']['order_paid'] = isset($_SESSION['oderCreate']['paid']) ? $_SESSION['oderCreate']['paid'] : (($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue);
            unset($_SESSION['oderCreate']['paid']);

            unset($_SESSION['oderCreate']["nfce"]);
            $OrderCreate = $_SESSION['oderCreate'];
            unset($OrderCreate['item'], $OrderCreate['addr_id'], $OrderCreate['total'], $OrderCreate["itens"], $OrderCreate["config"]);
            $OrderCreate['order_addr'] = isset($_SESSION['oderCreate']['addr_id']) ? $_SESSION['oderCreate']['addr_id'] : null;
            $OrderCreate['order_price'] = ($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue;
            $OrderCreate['order_status'] = 3;
            $OrderCreate['order_payment'] = 1;
            $OrderCreate['order_type'] = 2;
            $OrderCreate['order_date'] = date('Y-m-d H:i:s');
            $OrderCreate['order_update'] = date('Y-m-d H:i:s');

            if (isset($OrderClient)):
                if (isset($OrderClient["client_id"])):
                    $OrderCreate['user_id'] = $OrderClient["client_id"];
                else:
                    $OrderClientAdress["addr_name"] = "Novo Endereço";
                    $OrderClientAdress["addr_zipcode"] = $OrderClient["addr_zipcode"];
                    $OrderClientAdress["addr_street"] = $OrderClient["addr_street"];
                    $OrderClientAdress["addr_number"] = $OrderClient["addr_number"];
                    $OrderClientAdress["addr_district"] = $OrderClient["addr_district"];
                    $OrderClientAdress["addr_city"] = $OrderClient["addr_city"];
                    $OrderClientAdress["addr_state"] = $OrderClient["addr_state"];

                    $CreateUser = [
                        "user_name" => $OrderClient["user_name"],
                        "user_document" => $OrderClient["user_document"],
                        "user_cell" => $OrderClient["user_cell"],
                        "user_registration" => date('Y-m-d H:i:s'),
                        "user_level" => 1
                    ];
                    $Create->ExeCreate(DB_USERS, $CreateUser);
                    $OrderClientAdress["user_id"] = $Create->getResult();
                    $OrderCreate['user_id'] = $Create->getResult();
                    $Create->ExeCreate(DB_USERS_ADDR, $OrderClientAdress);
                endif;
            endif;
            $Create->ExeCreate(DB_ORDERS, $OrderCreate);
            $OrderId = $Create->getResult();
            $OrderCreateItem = array();
            foreach ($_SESSION['oderCreate']['item'] as $Item => $Qtd):
                $Read->FullRead("SELECT pdt_id, pdt_title, pdt_price, pdt_offer_price, pdt_offer_start, pdt_offer_end FROM " . DB_PDT . " WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :st)", "st={$Item}");
                if ($Read->getResult()):
                    extract($Read->getResult()[0]);
                    if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                        $PdtPrice = $pdt_offer_price;
                    else:
                        $PdtPrice = $pdt_price;
                    endif;

                    if (isset($Qtd["price"])):
                        $PdtPrice = $Qtd["price"];
                    endif;

                    if (isset($Qtd["amount"])):
                        $Qtd = $Qtd["amount"];
                    endif;

                    $OrderCreateItem[] = [
                        'order_id' => $OrderId,
                        'pdt_id' => $pdt_id,
                        'stock_id' => $Item,
                        'item_name' => $pdt_title,
                        'item_price' => $PdtPrice,
                        'item_amount' => $Qtd,
                    ];
                    $PostData['itens'][] = $pdt_id;
                    $PostData['quantidade'][] = $Qtd;
                    $PostData['valor'][] = $PdtPrice;
                    $PostData['descricao'][] = $pdt_title;
                    $PostData['unidade'][] = "un";
                endif;
            endforeach;
            $Create->ExeCreateMulti(DB_ORDERS_ITEMS, $OrderCreateItem);
            if ($OrderId):
                //Remover do Estoque
                $n = 0;
                while ($n < count($OrderCreateItem)):
                    //Stock
                    $Read->FullRead("SELECT stock_inventory, stock_sold FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");
                    $UpdatePdtStock = [
                        'stock_inventory' => $Read->getResult()[0]['stock_inventory'] - $OrderCreateItem[$n]['item_amount'],
                        'stock_sold' => $Read->getResult()[0]['stock_sold'] + $OrderCreateItem[$n]['item_amount']
                    ];
                    $Update->ExeUpdate(DB_PDT_STOCK, $UpdatePdtStock, "WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");

                    //Product
                    $Read->FullRead("SELECT pdt_inventory FROM " . DB_PDT . " WHERE pdt_id = :id", "id={$OrderCreateItem[$n]['pdt_id']}");
                    $UpdatePdt = [
                        'pdt_inventory' => $Read->getResult()[0]['pdt_inventory'] - $OrderCreateItem[$n]['item_amount']
                    ];
                    $Update->ExeUpdate(DB_PDT, $UpdatePdt, "WHERE pdt_id = :id", "id={$OrderCreateItem[$n]['pdt_id']}");
                    $n++;
                endwhile;

                $finanLanc = [
                    'category_id' => 3,
                    'fin_type' => "rec",
                    'order_id' => $OrderId,
                    'fin_title' => "Venda Pedido #{$OrderId}",
                    'fin_value' => $OrderCreate['order_price'],
                    'fin_date' => $OrderCreate['order_date'],
                    'fin_due_date' => $OrderCreate['order_date'],
                    'fin_payment_form' => 1,
                    'fin_split' => 1,
                    'fin_author' => $_SESSION['userLogin']['user_id'],
                    'fin_status' => 1
                ];

                switch ($OrderCreate['order_payment_mode']):
                    case 1:
                        $Create->ExeCreate(DB_FINAN, $finanLanc);
                        if ($Create->getResult()):
                            $FinanId = $Create->getResult();
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'],
                                'fin_split_date' => $OrderCreate['order_date'],
                                'fin_split_number' => 1,
                                'fin_split_method' => 1,
                                'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                        endif;
                        break;
                    case 2:
                        $finanLanc["fin_payment_form"] = 2;
                        $Create->ExeCreate(DB_FINAN, $finanLanc);
                        if ($Create->getResult()):
                            $FinanId = $Create->getResult();
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'],
                                'fin_split_date' => $OrderCreate['order_date'],
                                'fin_split_number' => 1,
                                'fin_split_method' => 3,
                                'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                        endif;
                        break;
                    case 3:
                    case 4:
                    case 6:
                        $finanLanc["fin_payment_form"] = 2;
                        $finanLanc["fin_split"] = 2;
                        $Create->ExeCreate(DB_FINAN, $finanLanc);
                        if ($Create->getResult()):
                            $FinanId = $Create->getResult();
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'] / 2,
                                'fin_split_date' => $OrderCreate['order_date'],
                                'fin_split_number' => 1,
                                'fin_split_method' => 3,
                                'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'] / 2,
                                'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                'fin_split_number' => 2,
                                'fin_split_method' => 3,
                                'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                        endif;
                        break;
                    case 5:
                    case 8:
                        $finanLanc["fin_payment_form"] = 2;
                        $finanLanc["fin_split"] = 3;
                        $Create->ExeCreate(DB_FINAN, $finanLanc);
                        if ($Create->getResult()):
                            $FinanId = $Create->getResult();
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'] / 3,
                                'fin_split_date' => $OrderCreate['order_date'],
                                'fin_split_number' => 1,
                                'fin_split_method' => 3,
                                'fin_split_status' => 4 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'] / 3,
                                'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                'fin_split_number' => 2,
                                'fin_split_method' => 3,
                                'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'] / 3,
                                'fin_split_date' => date('Y-m-d', strtotime('+2 month', strtotime($OrderCreate['order_date']))),
                                'fin_split_number' => 3,
                                'fin_split_method' => 3,
                                'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                        endif;
                        break;
                        break;
                    case 7:
                        $finanLanc["fin_payment_form"] = 2;
                        $Create->ExeCreate(DB_FINAN, $finanLanc);
                        if ($Create->getResult()):
                            $FinanId = $Create->getResult();
                            $finanParc = [
                                'fin_id' => $FinanId,
                                'fin_split_price' => $OrderCreate['order_price'],
                                'fin_split_date' => date('Y-m-d', strtotime('+1 month', strtotime($OrderCreate['order_date']))),
                                'fin_split_number' => 1,
                                'fin_split_method' => 3,
                                'fin_split_status' => 1 //1 - Aguardando / 4 - Realizado
                            ];
                            $Create->ExeCreate(DB_FINAN_SPLITS, $finanParc);
                        endif;
                        break;
                endswitch;

                $jSON['trigger'] = AjaxErro('<b class="icon-warning">PEDIDO CRIADO:</b>');
                $jSON['redirect'] = "dashboard.php?wc=orders/pdv";
                $jSON['printer'] = BASE . "/admin/cupom.php?id={$OrderId}";
            else:
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>ERRO AO CRIAR PEDIDO:</b> Revise os dados e tente novamente!", E_USER_WARNING);
            endif;
            break;

        case 'AppOrderCreateOrc':
            //var_dump($_SESSION['oderCreate']);
            if (!isset($_SESSION['oderCreate']['order_price']) && $_SESSION['oderCreate']['total']):
                $_SESSION['oderCreate']['order_price'] = $_SESSION['oderCreate']['total'];
                unset($_SESSION['oderCreate']['total']);
            endif;

            if (!isset($_SESSION['oderCreate']['item']) && $_SESSION['oderCreate']["itens"]):
                $_SESSION['oderCreate']['item'] = $_SESSION['oderCreate']["itens"];
                unset($_SESSION['oderCreate']["itens"]);
            endif;

            if (isset($_SESSION['oderCreate']["client"])):
                $OrderClient = $_SESSION['oderCreate']["client"];
                unset($_SESSION['oderCreate']["client"]);
            endif;

            $OrderShip = 0;
            $OrderCupomValue = 0;

            $_SESSION['oderCreate']['order_paid'] = isset($_SESSION['oderCreate']['paid']) ? $_SESSION['oderCreate']['paid'] : (($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue);
            unset($_SESSION['oderCreate']['paid']);

            unset($_SESSION['oderCreate']["nfce"]);
            $OrderCreate = $_SESSION['oderCreate'];
            unset($OrderCreate['item'], $OrderCreate['addr_id'], $OrderCreate['total'], $OrderCreate["itens"], $OrderCreate["config"]);
            $OrderCreate['order_addr'] = isset($_SESSION['oderCreate']['addr_id']) ? $_SESSION['oderCreate']['addr_id'] : null;
            $OrderCreate['order_price'] = ($_SESSION['oderCreate']['order_price'] + $OrderShip) - $OrderCupomValue;
            $OrderCreate['order_status'] = 7;
            $OrderCreate['order_payment'] = 1;
            $OrderCreate['order_type'] = 1;
            $OrderCreate['order_date'] = date('Y-m-d H:i:s');
            $OrderCreate['order_update'] = date('Y-m-d H:i:s');
            $OrderCreate['orcamento_reserva'] = isset($_SESSION['oderCreate']["config"]["orcamento_reserva"]) ? 1 : 0;

            if (isset($OrderClient)):
                if (isset($OrderClient["client_id"])):
                    $OrderCreate['user_id'] = $OrderClient["client_id"];
                else:
                    $OrderClientAdress["addr_name"] = "Novo Endereço";
                    $OrderClientAdress["addr_zipcode"] = $OrderClient["addr_zipcode"];
                    $OrderClientAdress["addr_street"] = $OrderClient["addr_street"];
                    $OrderClientAdress["addr_number"] = $OrderClient["addr_number"];
                    $OrderClientAdress["addr_district"] = $OrderClient["addr_district"];
                    $OrderClientAdress["addr_city"] = $OrderClient["addr_city"];
                    $OrderClientAdress["addr_state"] = $OrderClient["addr_state"];

                    $CreateUser = [
                        "user_name" => $OrderClient["user_name"],
                        "user_document" => $OrderClient["user_document"],
                        "user_cell" => $OrderClient["user_cell"],
                        "user_registration" => date('Y-m-d H:i:s'),
                        "user_level" => 1
                    ];
                    $Create->ExeCreate(DB_USERS, $CreateUser);
                    $OrderClientAdress["user_id"] = $Create->getResult();
                    $Create->ExeCreate(DB_USERS_ADDR, $CreateUser);
                    $OrderCreate['user_id'] = $Create->getResult();
                endif;
            endif;
            $Create->ExeCreate(DB_ORDERS, $OrderCreate);
            $OrderId = $Create->getResult();
            $OrderCreateItem = array();
            foreach ($_SESSION['oderCreate']['item'] as $Item => $Qtd):
                $Read->FullRead("SELECT pdt_id, pdt_title, pdt_price, pdt_offer_price, pdt_offer_start, pdt_offer_end FROM " . DB_PDT . " WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :st)", "st={$Item}");
                if ($Read->getResult()):
                    extract($Read->getResult()[0]);
                    if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                        $PdtPrice = $pdt_offer_price;
                    else:
                        $PdtPrice = $pdt_price;
                    endif;

                    if (isset($Qtd["price"])):
                        $PdtPrice = $Qtd["price"];
                    endif;

                    if (isset($Qtd["amount"])):
                        $Qtd = $Qtd["amount"];
                    endif;

                    $OrderCreateItem[] = [
                        'order_id' => $OrderId,
                        'pdt_id' => $pdt_id,
                        'stock_id' => $Item,
                        'item_name' => $pdt_title,
                        'item_price' => $PdtPrice,
                        'item_amount' => $Qtd,
                    ];
                    $PostData['itens'][] = $pdt_id;
                    $PostData['quantidade'][] = $Qtd;
                    $PostData['valor'][] = $PdtPrice;
                    $PostData['descricao'][] = $pdt_title;
                    $PostData['unidade'][] = "un";
                endif;
            endforeach;
            $Create->ExeCreateMulti(DB_ORDERS_ITEMS, $OrderCreateItem);
            if ($OrderId):
                if (isset($_SESSION['oderCreate']["config"]["orcamento_reserva"])):
                    //Remover do Estoque
                    $n = 0;
                    while ($n < count($OrderCreateItem)):
                        //Stock
                        $Read->FullRead("SELECT stock_reserva FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");
                        $UpdatePdtStock = [
                            'stock_reserva' => $Read->getResult()[0]['stock_reserva'] + $OrderCreateItem[$n]['item_amount']
                        ];
                        $Update->ExeUpdate(DB_PDT_STOCK, $UpdatePdtStock, "WHERE stock_id = :id", "id={$OrderCreateItem[$n]['stock_id']}");
                        $n++;
                    endwhile;
                endif;

                $jSON['trigger'] = AjaxErro('<b class="icon-warning">ORÇAMENTO CRIADO:</b>');
                $jSON['redirect'] = "dashboard.php?wc=orders/pdv";
                $jSON['printer'] = BASE . "/admin/cupom.php?id={$OrderId}";
            else:
                $jSON['trigger'] = AjaxErro("<b class='icon-warning'>ERRO AO CRIAR ORÇAMENTO:</b> Revise os dados e tente novamente!", E_USER_WARNING);
            endif;
            break;

        case 'orderCreateSearch':
            $PdtSearch = $PostData['search'];

            //stock_inventory >= 1 AND
            $Read->FullRead("SELECT stock_id, pdt_id, stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id IN(SELECT pdt_id FROM " . DB_PDT . " WHERE pdt_title LIKE '%{$PdtSearch}%' OR  pdt_code LIKE '%{$PdtSearch}%') ORDER BY stock_inventory DESC LIMIT 10", "");
            if (!$Read->getResult()):
                $jSON['result'] = "<div class='trigger trigger_info' style='display: block; margin: 15px 0 0 0;'>Não foram encontratos produtos para os termos {$PdtSearch}!</div>";
            else:
                $jSON['result'] = null;
                foreach ($Read->getResult() as $Stock):

                    /* CUSTOM BY ALISSON */
                    $Read->FullRead("SELECT (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Stock['stock_id']}");
                    $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " | Cor: {$Read->getResult()[0]['attr_color_title']}" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " | Cor: {$Read->getResult()[0]['attr_color_title']} | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? '' : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : '')))));
                    /* CUSTOM BY ALISSON */

                    $Read->FullRead("SELECT pdt_title, pdt_code, pdt_price, pdt_offer_price, pdt_offer_start, pdt_offer_end, pdt_inventory FROM " . DB_PDT . " WHERE pdt_id = :id", "id={$Stock['pdt_id']}");
                    if ($Read->getResult()):
                        extract($Read->getResult()[0]);
                        //data-value='{$Stock['stock_id']}'
                        $jSON['result'] .= "<optgroup label='ID: {$Stock['pdt_id']} | COD: {$pdt_code} | Estoque {$pdt_inventory} | R$ " . number_format($pdt_price, 2, ',', '.') . "'>";
                        $jSON['result'] .= "<option value='{$Stock['stock_id']}'>{$pdt_title}{$PdtVariation}</option>";
                        $jSON['result'] .= "</optgroup>";
                    endif;
                endforeach;
            endif;
            break;

        case 'orderCreateSelect':
            //var_dump($PostData);
            $Stock = $PostData['stock'];
            if ($PostData['stock'] != $PostData['stock2']):
                $Read->ExeRead(DB_PDT, "WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :id)", "id={$Stock}");
                if ($Read->getResult()):
                    extract($Read->getResult()[0]);
                    if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                        $PdtPrice = $pdt_offer_price;
                    else:
                        $PdtPrice = $pdt_price;
                    endif;

                    $jSON['success']['price'] = number_format($PdtPrice, 2, ',', '.');
                    $jSON['success']['stock'] = $Stock;
                else:
                    //Busca por nome
                    $Read->FullRead("SELECT stock_id, pdt_id, stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id IN(SELECT pdt_id FROM " . DB_PDT . " WHERE pdt_title LIKE '%{$Stock}%' OR pdt_code LIKE '%{$Stock}%') ORDER BY stock_inventory DESC LIMIT 10", "");
                    if ($Read->getResult()[0]["stock_id"]):
                        $Stock = $Read->getResult()[0]["stock_id"];
                        $Read->ExeRead(DB_PDT, "WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :id)", "id={$Stock}");
                        if ($Read->getResult()):
                            extract($Read->getResult()[0]);
                            if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                                $PdtPrice = $pdt_offer_price;
                            else:
                                $PdtPrice = $pdt_price;
                            endif;

                            $jSON['success']['price'] = number_format($PdtPrice, 2, ',', '.');
                            $jSON['success']['stock'] = $Stock;
                        endif;
                    endif;
                endif;
            else:
                $Read->FullRead("SELECT stock_id, pdt_id, stock_inventory FROM " . DB_PDT_STOCK . " WHERE pdt_id IN(SELECT pdt_id FROM " . DB_PDT . " WHERE pdt_title LIKE '%{$Stock}%' OR pdt_code LIKE '%{$Stock}%') ORDER BY stock_inventory DESC LIMIT 10", "");
                if ($Read->getResult()[0]["stock_id"]):
                    $Stock = $Read->getResult()[0]["stock_id"];
                    $Read->ExeRead(DB_PDT, "WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :id)", "id={$Stock}");
                    if ($Read->getResult()):
                        extract($Read->getResult()[0]);
                        if ($pdt_offer_price && $pdt_offer_start <= date('Y-m-d H:i:s') && $pdt_offer_end >= date('Y-m-d H:i:s')):
                            $PdtPrice = $pdt_offer_price;
                        else:
                            $PdtPrice = $pdt_price;
                        endif;

                        $jSON['success']['price'] = number_format($PdtPrice, 2, ',', '.');
                        $jSON['success']['stock'] = $Stock;
                    endif;
                endif;
            endif;
            break;

        case 'orderCreateADD':
            if (empty($_SESSION['oderCreate'])):
                $_SESSION['oderCreate'] = array();
            endif;

            //Buscar Preço Atual
            if (isset($_SESSION['oderCreate']['itens'][$PostData['stock_id']])):
                $PostData['qtd'] = $PostData['qtd'] + $_SESSION['oderCreate']['itens'][$PostData['stock_id']]["amount"];
                $PostData['price'] = str_replace('.', ',', $_SESSION['oderCreate']['itens'][$PostData['stock_id']]["price"]);
            endif;
            $_SESSION['oderCreate']['itens'][$PostData['stock_id']] = [
                'amount' => $PostData['qtd'],
                'price' => str_replace(['.', ','], ['', '.'], $PostData['price'])
            ];
            break;

        case 'orderCreateRefresh':
            $i = 0;
            $Items = '';
            $ItemsPrice = 0;
            $ItemsTotal = 0;
            foreach ($_SESSION['oderCreate']['itens'] as $Stock => $Values):

                /* CUSTOM BY ALISSON */
                $Read->FullRead("SELECT stock_inventory, (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Stock}");
                //$Read->FullRead("SELECT stock_code FROM " . DB_PDT_STOCK . " WHERE stock_id = :stid", "stid={$Item['stock_id']}");
                $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " | Cor: {$Read->getResult()[0]['attr_color_title']}" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " | Cor: {$Read->getResult()[0]['attr_color_title']} | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? '' : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : '')))));
                //$PdtVariation = ($Read->getResult() && $Read->getResult()[0]['stock_code'] != 'default' ? " - <b>{$Read->getResult()[0]['stock_code']}</b>" : null);
                //var_dump($Stock);
                $Read->ExeRead(DB_PDT, "WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :id)", "id={$Stock}");
                $Item = $Read->getResult()[0];
                //var_dump($Item);

                $i++;
                $Items .= "<div class='single_order_items_item'>";
                $Items .= "<p>" . str_pad($i, 5, 0, STR_PAD_LEFT);
                $Items .= " | <b>" . str_pad($Item['pdt_code'], 5, 0, STR_PAD_LEFT) . "</b>";
                $Items .= " | {$Item['pdt_title']} {$PdtVariation}</p>";

                $Items .= "<p><b>{$Values['amount']}</b> * R$ " . number_format($Values['price'], '2', ',', '.');
                $Items .= "<a href='#0' class='btn btn_blue btn_small icon-pencil icon-notext j_modalOpen' data-stock='{$Stock}' style='margin-left:10px !important'></a>";
                $Items .= "<a href='#0' class='btn btn_red btn_small icon-cross icon-notext j_order--remove' data-stock='{$Stock}' style='margin-left:10px !important'></a>";
                $Items .= "</p></div>";
                $ItemsPrice += $Values['price'];
                $ItemsTotal += $Values['price'] * $Values['amount'];
            endforeach;

            $Total = isset($_SESSION['oderCreate']['order_discount']) ? ($ItemsTotal - $_SESSION['oderCreate']['order_discount']) : $ItemsTotal;
            $Total = isset($_SESSION['oderCreate']['order_acrescimo']) ? ($Total + $_SESSION['oderCreate']['order_acrescimo']) : $Total;
            $Total = isset($_SESSION['oderCreate']['order_frete']) ? ($Total + $_SESSION['oderCreate']['order_frete']) : $Total;
            $_SESSION['oderCreate']['total'] = str_replace(['.', ','], ['', '.'], $Total);

            $jSON['success']['items'] = $Items;
            $jSON['success']['total'] = number_format($_SESSION['oderCreate']['total'], '2', ',', '.');
            if (!empty($_SESSION['oderCreate']['paid'])):
                $Paid = $_SESSION['oderCreate']['paid'] - $_SESSION['oderCreate']['total'];
                $jSON['success']['paid'] = number_format($Paid, '2', ',', '.');
            endif;
            break;

        case 'orderCreateModalData':
            $Stock = $PostData['stock'];

            /* CUSTOM BY ALISSON */
            $Read->FullRead("SELECT stock_inventory, (SELECT attr_size_code FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_code, (SELECT attr_size_title FROM " . DB_PDT_ATTR_SIZES . " WHERE size_id = attr_size_id) AS attr_size_title, (SELECT attr_color_code FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_code, (SELECT attr_color_title FROM " . DB_PDT_ATTR_COLORS . " WHERE color_id = attr_color_id) AS attr_color_title, (SELECT attr_print_code FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_code, (SELECT attr_print_title FROM " . DB_PDT_ATTR_PRINTS . " WHERE print_id = attr_print_id) AS attr_print_title FROM " . DB_PDT_STOCK . " WHERE stock_id = :id", "id={$Stock}");
            //$Read->FullRead("SELECT stock_code FROM " . DB_PDT_STOCK . " WHERE stock_id = :stid", "stid={$Item['stock_id']}");
            $PdtVariation = ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " | Cor: {$Read->getResult()[0]['attr_color_title']}" : ($Read->getResult() && empty($Read->getResult()[0]['attr_color_code']) && empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) ? " | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_color_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_print_code']) ? " | Cor: {$Read->getResult()[0]['attr_color_title']} | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? '' : ($Read->getResult() && !empty($Read->getResult()[0]['attr_print_code']) && !empty($Read->getResult()[0]['attr_size_code']) && empty($Read->getResult()[0]['attr_color_code']) ? " | Tamanho: {$Read->getResult()[0]['attr_size_title']}" : '')))));
            //$PdtVariation = ($Read->getResult() && $Read->getResult()[0]['stock_code'] != 'default' ? " - <b>{$Read->getResult()[0]['stock_code']}</b>" : null);

            $Read->ExeRead(DB_PDT, "WHERE pdt_id = (SELECT pdt_id FROM " . DB_PDT_STOCK . " WHERE stock_id = :id)", "id={$Stock}");
            $Item = $Read->getResult()[0];

            $jSON['success']['title'] = $Item['pdt_title'] . $PdtVariation;
            $jSON['success']['amount'] = $_SESSION['oderCreate']['itens'][$Stock]['amount'];
            $jSON['success']['price'] = number_format($_SESSION['oderCreate']['itens'][$Stock]['price'], '2', ',', '.');
            $jSON['success']['total'] = number_format($jSON['success']['amount'] * $_SESSION['oderCreate']['itens'][$Stock]['price'], '2', ',', '.');
            break;

        case 'orderCreateRange':
            $Range = $PostData['amount'];
            $Value = $PostData['price'];
            $Stock = $PostData['stock'];

            $formatedValue = str_replace(['.', ','], ['', '.'], $Value);
            $newValue = $formatedValue * $Range;
            $jSON['success']['total'] = number_format($newValue, 2, ',', '.');

            $_SESSION['oderCreate']['itens'][$Stock]['amount'] = $Range;
            $_SESSION['oderCreate']['itens'][$Stock]['price'] = $formatedValue;
            break;

        case 'orderCreateRemove':
            $Stock = $PostData['stock'];
            unset($_SESSION['oderCreate']['itens'][$Stock]);
            $jSON['success'] = true;
            break;

        case 'orderCreateSetFormaPag':
            $_SESSION['oderCreate']['order_payment_mode'] = $PostData['value'];
            $jSON['success'] = true;
            break;

        case 'orderCreateSetAcrescimo':
            $_SESSION['oderCreate']['order_acrescimo'] = (float) str_replace(['.', ','], ['', '.'], $PostData['value']);
            $jSON['success'] = true;
            break;

        case 'orderCreateSetFrete':
            $_SESSION['oderCreate']['order_frete'] = (float) str_replace(['.', ','], ['', '.'], $PostData['value']);
            $jSON['success'] = true;
            break;

        case 'orderCreateSetDiscount':
            $_SESSION['oderCreate']['order_discount'] = (float) str_replace(['.', ','], ['', '.'], $PostData['value']);
            $jSON['success'] = true;
            break;

        case 'orderCreateSetPaid':
            $_SESSION['oderCreate']['paid'] = (float) str_replace(['.', ','], ['', '.'], $PostData['value']);
            $jSON['success'] = true;
            break;

        case 'orderCreateClient':
            $_SESSION['oderCreate']['client'] = $PostData;
            //var_dump($_SESSION['oderCreate']['client']);
            $jSON['success'] = true;
            break;

        case 'orderSelectClient':
            $_SESSION['oderCreate']['client'] = $PostData;
            if ($PostData["client_id"] == 0):
                unset($_SESSION['oderCreate']['client']);
            endif;
            $jSON['success'] = true;
            break;

        case 'orderVendedorConfig':
            $_SESSION['oderCreate']["vendedor_id"] = $PostData["vendedor_id"];
            $jSON['success'] = true;
            break;

        case 'orderCreateConfig':
            $_SESSION['oderCreate']['config'] = $PostData;
            $jSON['success'] = true;
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
