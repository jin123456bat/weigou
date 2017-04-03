<?php
namespace application\model;
use system\core\model;
class countryModel extends model
{
	function __construct($table)
	{
		parent::__construct($table);
	}
	
	function get($id)
	{
		return $this->table('upload','left join','upload.id=country.logo')->where('country.id=?',[$id])->find('country.id,country.name,upload.path as logo');
	}
}