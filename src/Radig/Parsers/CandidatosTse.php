<?php
namespace Radig\Parsers;

use QueryPath;

class CandidatosTse
{
	public static $ufPath = 'http://divulgacand2012.tse.jus.br/divulgacand2012/abrirTelaPesquisaCandidatosPorUF.action?siglaUFSelecionada=:UF:';

	public static $completePath = 'http://divulgacand2012.tse.jus.br/divulgacand2012/pesquisarCandidato.action?siglaUFSelecionada=:UF:&codigoMunicipio=:CODMUN:&codigoCargo=:TIPO:&codigoSituacao=12';

	public static $candTypes = array(
		'vereador'    => array('cod' => 13, 'scope' => 'city'),
		'prefeito'    => array('cod' => 11, 'scope' => 'city'),
		'depestadual' => array('cod' => 11, 'scope' => 'uf'),
		'depfederal'  => array('cod' => 11, 'scope' => 'uf'),
		'governador'  => array('cod' => 11, 'scope' => 'uf'),
		'senador'     => array('cod' => 11, 'scope' => 'uf'),
		'presidente'  => array('cod' => 11, 'scope' => 'national'),
	);

	public static $ufs = array(
		'AC','AL','AM','AP',
		'BA','CE','DF','ES',
		'GO','MA','MG','MS',
		'MT','PA','PB','PE',
		'PI','PR','RJ','RN',
		'RO','RR','RS','SC',
		'SE','SP','TO'
	);

	protected $currentUf = null;

	protected $currentCity = null;

	protected $db = null;

	/**
	 * Prepara o ínicio do crawler para varrer o
	 * TSE.
	 *
	 * Inicializa conexão com o BD para controlar quais
	 * recursos já foram recuperados.
	 *
	 * Caso seja passado o segundo parâmetro como true,
	 * reinicia o DB para começar a varredura do zero.
	 *
	 * @param  resource $db        Conexão com o DB
	 * @param  boolean  $override  Se a varredura deve ser
	 * re-iniciada ou continuar de onde parou.
	 *
	 * @return void
	 */
	public function start($db, $override = false)
	{
		$this->db = $db;
		$this->run();
	}

	/**
	 * Varre iterativamente todos os recursos ainda
	 * não percorridos para buscar os candidatos no TSE.
	 *
	 * @return void
	 */
	public function run()
	{
		$coll = $this->db->selectCollection('ufs');
		$data = array();

		foreach(self::$ufs as $uf) {
			$check = current($coll->find(array('abbr' => $uf))->toArray());

			if(!empty($check) && $check['parsed']) {
				continue;
			}

			$data = array('abbr' => $uf, 'parsed' => true);
			$cities = $this->_readUfCities($uf);

			$data['cities'] = $cities;
			$data = json_encode($data);

			$this->db->execute("db.ufs.insert({$data})");
		}
	}

	/**
	 * Recupera a lista de candidatos baseada nos critérios "tipo",
	 * "estado" e "cidade".
	 *
	 *
	 * @param  string  $type   Algum tipo válido de candidato:
	 *  - vereador:     vereadores
	 *  - prefeito:     prefeitos
	 *  - depestadual:  deputados estaduais
	 *  - depfederal:   deputados federals
	 *  - governador:   governadores
	 *  - senador:      senadores
	 *  - presidente:   presidentes
	 *
	 * @param  string  $uf     Sigla da UF. Opcional dependente do tipo
	 * @param  integer $cityId Código da cidade, de acordo com o TSE. Opcional dependendo do tipo
	 *
	 * @return array Coleção de candidatos com os dados:
	 *  - ID
	 *  - Nome do candidato
	 *  - Nome para urna
	 *  - Número
	 *  - Partido
	 *  - Coligação
	 */
	public function readCandidates($type, $uf = null, $cityId = null)
	{
		if(!isset(self::$candTypes[$type])) {
			throw new Exception("Tipo '{$type}' não é suportado pelo parser", 1);
		}

		extract(self::$candTypes[$type]);

		$specialized = '_read' . ucfirst($scope) . 'Candidates';

		if(!method_exists($this, $specialized)) {
			throw new Exception("Escopo '{$scope}' não é implementado no parser", 1);
		}

		return $this->{$specialized}($cod, $uf, $cityId);
	}

	/**
	 * Recupera o código de cada cidade de um determinado estado.
	 * Retorna um array com o código das cidades.
	 *
	 * @param  string $uf Sigla da UF
	 * @return array Coleção das cidades, com seus
	 * nomes e código.
	 */
	protected function _readUfCities($uf)
	{
		$cities = array();
		$url =  str_replace(':UF:', $uf, self::$ufPath);

		foreach(QueryPath::withHtml($url, '#tabMunicipio tr.gradeX') as $city) {
			$cities[] = array(
				'name' => $this->_extractCityName($city->find('td:first')->text()),
				'cod' => $this->_extractCityCode($city->parent()->find('img')->attr('onclick'))
			);
		}

		return $cities;
	}

	/**
	 * Recupera os candidatos a nível municipal
	 *
	 * @param  string  $uf Sigla da UF
	 * @param  integer $cityId Código da cidade de acordo com TSE
	 * @param  midex   $type Filtrar os tipos de candidatos que serão retornados,
	 * valores válidos são:
	 *  - 'prefeito' : apenas prefeitos
	 *  - 'vereador' : apenas vereadores
	 *
	 * @return array Lista com os seguintes dados para cada candidato
	 *  - ID
	 *  - Nome do candidato
	 *  - Nome para urna
	 *  - Número
	 *  - Partido
	 *  - Coligação
	 */
	protected function _readCityCandidates($typeId, $uf, $cityId)
	{
		$candidates = array();

		return $candidates;
	}

	/**
	 * Recupera os candidatos a nível estadual
	 *
	 * @param  string $uf Sigla da UF
	 * @param  midex  $type Filtrar os tipos de candidatos que serão retornados,
	 * valores válidos são:
	 *  - 'depestadual' : apenas deputados estaduais
	 *  - 'depfederal' : apenas deputados federais
	 *  - 'senador' : apenas senadores
	 *  - 'governador' : apenas governadores
	 *
	 * @return array Lista com os seguintes dados para cada candidato
	 *  - ID
	 *  - Nome do candidato
	 *  - Nome para urna
	 *  - Número
	 *  - Partido
	 *  - Coligação
	 */
	protected function _readUfCandidates($typeId, $uf)
	{
		$candidates = array();

		return $candidates;
	}

	/**
	 * Recupera da página todos os candidatos de
	 * escopo nacional (Presidentes)
	 *
	 * @return CandidatosTse
	 */
	protected function _readNationalCandidates()
	{
		$candidates = array();

		return $candidates;
	}

	/**
	 * Extraí o nome da cidade da tag passada
	 *
	 * @param  string $text Tag da coluna que contém o nome
	 * da cidade
	 *
	 * @return string Nome da cidade
	 */
	private function _extractCityName($text)
	{
		return str_replace(array("\n","\r","\t"), '', trim($text));
	}

	/**
	 * Extraí o código do município da tag
	 *
	 * @param  string $text Conteúdo do atributo onclick que contém
	 * o código do município
	 *
	 * @return integer Código do município
	 */
	private function _extractCityCode($text)
	{
		$m = array();
		preg_match('/onPesquisaClick\(this, [0-9]+, \"([0-9]+)\"\);/', $text, $m);
		return (int)$m[1];
	}
}