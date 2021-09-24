<?php

if (!$WorkControlDefineConf):
    /*
     * SITE CONFIG
     */
    define('SITE_NAME', 'Harmoniser'); //Nome do site do cliente
    define('SITE_SUBNAME', ''); //Nome do site do cliente
    define('SITE_DESC', 'A sua plataforma de análise facial'); //Descrição do site do cliente

    define('SITE_FONT_NAME', 'Open Sans'); //Tipografia do site (https://www.google.com/fonts)
    define('SITE_FONT_WHIGHT', '300,400,600,700,800'); //Tipografia do site (https://www.google.com/fonts)

    /*
     * SHIP CONFIG
     * DADOS DO SEU CLIENTE/DONO DO SITE
     */
    define('SITE_ADDR_NAME', 'Harmoniser'); //Nome de remetente
    define('SITE_ADDR_RS', 'Harmoniser'); //Razão Social
    define('SITE_ADDR_EMAIL', 'contato@site.com.br'); //E-mail de contato
    define('SITE_ADDR_SITE', 'www.site.com.br'); //URL descrita
    define('SITE_ADDR_CNPJ', '00.000.000/0000-00'); //CNPJ da empresa
    define('SITE_ADDR_IE', '000/0000000'); //Inscrição estadual da empresa
    define('SITE_ADDR_PHONE_A', '(00) 00000-0000'); //Telefone 1
    define('SITE_ADDR_PHONE_B', '(00) 00000-0000'); //Telefone 2
    define('SITE_ADDR_ADDR', 'Rua de teste, 123'); //ENDEREÇO: rua, número (complemento)
    define('SITE_ADDR_CITY', 'Rio de Janeiro'); //ENDEREÇO: cidade
    define('SITE_ADDR_DISTRICT', 'Centro'); //ENDEREÇO: bairro
    define('SITE_ADDR_UF', 'RJ'); //ENDEREÇO: UF do estado
    define('SITE_ADDR_ZIP', '00000-00'); //ENDEREÇO: CEP
    define('SITE_ADDR_COUNTRY', 'Brasil'); //ENDEREÇO: País


    /**
     * Social Config
     */
    define('SITE_SOCIAL_NAME', '');
    /*
     * Google
     */
    define('SITE_SOCIAL_GOOGLE', 1);
    define('SITE_SOCIAL_GOOGLE_AUTHOR', ''); //https://plus.google.com/????? (**ID DO USUÁRIO)
    define('SITE_SOCIAL_GOOGLE_PAGE', ''); //https://plus.google.com/???? (**ID DA PÁGINA)
    /*
     * Facebook
     */
    define('SITE_SOCIAL_FB', 1);
    define('SITE_SOCIAL_FB_APP', 0); //Opcional APP do facebook
    define('SITE_SOCIAL_FB_AUTHOR', ''); //https://www.facebook.com/?????
    define('SITE_SOCIAL_FB_PAGE', ''); //https://www.facebook.com/?????
    /*
     * Twitter
     */
    define('SITE_SOCIAL_TWITTER', ''); //https://www.twitter.com/?????
    /*
     * YouTube
     */
    define('SITE_SOCIAL_YOUTUBE', ''); //https://www.youtube.com/user/?????
    /*
     * Instagram
     */
    define('SITE_SOCIAL_INSTAGRAM', ''); //https://www.instagram.com/?????
    /*
     * Snapchat
     */
    define('SITE_SOCIAL_SNAPCHAT', ''); //https://www.snapchat.com/add/?????
endif;