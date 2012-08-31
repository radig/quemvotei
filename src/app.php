<?php
$app = new Silex\Application();

$app->get('/', function() use($app) {
    return 'Em quem votei na última eleição?';
});

$app->get('/{token}/meus-candidatos/', function($token) use($app) {
	// @TODO checar token de autenticação e carregar dados do usuário
	return 'Olá ';
});

return $app;