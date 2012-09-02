<?php
$app = new Silex\Application();
$app['debug'] = true;

$app->register(new SilexExtension\MongoDbExtension(), array());

$app->get('/', function() use($app) {
    return 'Em quem votei na última eleição?';
});

$app->get('/{token}/meus-candidatos/', function($token) use($app) {
	return 'Olá ';
});

$app->get('/init', function() use($app) {
	$cands = new Radig\Parsers\CandidatosTse();
	$cands->start($app['mongodb']->selectDatabase('quemvotei'));
	$cands->syncDb();

	return 'Inicializado';
});

return $app;