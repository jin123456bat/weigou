<?php
namespace application\entity;

use system\core\entity;

class brand extends entity
{
	function __rule()
	{
		return array(
			array('logo'=>'required,int','message'=>'请上传LOGO'),
			array('name_cn' => 'required','message'=>'请填写中文名'),
			array('name_en' => 'required','message'=>'请填写英文名'),
			array('origin'=>'required,int','message'=>'请选择国籍'),
			array('description'=>'maxlength','maxlength'=>256,'message'=>'描述长度不能超过256个字符'),
		);
	}
}