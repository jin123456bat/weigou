<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
/**
 * @author jin12
 *
 */
class theme extends ajax
{
	function moveUpSubTheme()
	{
		$theme_id = $this->post('theme_id');
		$id = $this->post('id');
		if(empty($theme_id) || empty($id))
			return new json(json::PARAMETER_ERROR);
		
		$subtheme = $this->model('subtheme')->where('theme_id=?',[$theme_id])->orderby('sort','asc')->select();
		foreach($subtheme as $index => $st)
		{
			if($st['id'] == $id && isset($subtheme[$index-1]))
			{
				$temp = $subtheme[$index];
				$subtheme[$index] = $subtheme[$index-1];
				$subtheme[$index-1] = $temp;
				break;
			}
		}
		
		foreach($subtheme as $index => $st)
		{
			$this->model('subtheme')->where('id=?',[$st['id']])->limit(1)->update('sort',$index);
		}
		
		return new json(json::OK);
	}
	
	function moveDownSubTheme()
	{
		$theme_id = $this->post('theme_id');
		$id = $this->post('id');
		if(empty($theme_id) || empty($id))
			return new json(json::PARAMETER_ERROR);
		
		$subtheme = $this->model('subtheme')->where('theme_id=?',[$theme_id])->select();
		foreach($subtheme as $index => $st)
		{
			if($st['id'] == $id && isset($subtheme[$index+1]))
			{
				$temp = $subtheme[$index];
				$subtheme[$index] = $subtheme[$index+1];
				$subtheme[$index+1] = $temp;
				break;
			}
		}
		
		foreach($subtheme as $index => $st)
		{
			$this->model('subtheme')->where('id=?',[$st['id']])->limit(1)->update('sort',$index);
		}
		
		return new json(json::OK);
	}
	
	function modifyTitleOrSubtitle()
	{
		$id = $this->post('id');
		$name = $this->post('name');
		$value = $this->post('value');
		if (in_array($name, ['title','subtitle']))
		{
			if (!empty($value))
			{
				if ($this->model('subtheme')->where('id=?',[$id])->update($name,$value))
				{
					return new json(json::OK);
				}
			}
		}
		return new json(json::PARAMETER_ERROR,'name参数错误');
	}
	
	/**
	 * 保存编辑的主题
	 */
	function save()
	{
        $admin=$this->session->id;
		$id = $this->post('id');
		$title = $this->post('title','');
		$logo = $this->post('logo',NULL,'intval');
		if(empty($logo))
			$logo = NULL;
		
		if($this->model('theme')->where('id=?',[$id])->limit(1)->update([
			'title' => $title,
			'logo' => $logo,
			'modifytime' => $_SERVER['REQUEST_TIME']
		]))
		{
			$data = $this->post('data');
			if (is_array($data) && !empty($data))
			{
				foreach ($data as $theme_product)
				{
					$this->model('subtheme_product')->where('subtheme_id=?',[$theme_product['subtheme_id']])->delete();
					if (is_array($theme_product['pid']) && !empty($theme_product['pid']))
					{
						foreach ($theme_product['pid'] as $product)
						{
							$this->model('subtheme_product')->insert([
								'subtheme_id' => $theme_product['subtheme_id'],
								'product_id' => $product,
							]);
						}
					}
				}
			}
            $this->model("admin_log")->insertlog($admin, '保存主题成功，id：' . $id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '保存主题失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 删除快速连接
	 */
	function removeSubTheme()
	{
        $admin = $this->session->id;
		$id = $this->post('id');
		if($this->model('subtheme')->where('id=?',[$id])->delete())
		{
            $this->model("admin_log")->insertlog($admin, '主题删除快速链接成功,id:' . $id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '主题删除快速链接成功');
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 删除主题
	 * @return \application\message\json
	 */
	function remove()
	{
        $admin = $this->session->id;
		$id = $this->post('id');
		if($this->model('theme')->where('id=?',[$id])->update([
			'isdelete'=>1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{

            $this->model("admin_log")->insertlog($admin, '删除主题成功,id:' . $id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '删除主题失败（请求参数不对）');
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 创建主题中的快速连接
	 */
	function createSubTheme()
	{
        $admin=$this->session->id;
		$theme_id = $this->post('theme_id');
		$title = $this->post('title','');
		$subtitle = $this->post('subtitle','');
		$sort = $this->post('sort',1,'intval');
		if(empty($theme_id) || empty($title) || empty($subtitle))
			return new json(json::PARAMETER_ERROR);
		
		$total = $this->model('subtheme')->where('theme_id=?',[$theme_id])->select('count(*)');
		
		if($this->model('subtheme')->insert([
			'theme_id' => $theme_id,
			'title' => $title,
			'subtitle' => $subtitle,
			'sort' => $total[0]['count(*)'],
		]))
		{
			$data = [
				'id' => $this->model('subtheme')->lastInsertId(),
				'theme_id' => $theme_id,
				'title' => $title,
				'subtitle' => $subtitle,
				'sort' => $total[0]['count(*)'],
			];
            $this->model("admin_log")->insertlog($admin, '主题添加快速链接成功,id:'.$theme_id, 1);
			return new json(json::OK,NULL,$data);
		}
        $this->model("admin_log")->insertlog($admin, '主题添加快速链接失败（请求参数错误）', 1);
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 创建主题
	 */
	function createTheme()
	{
        $admin=$this->session->id;
		$title = $this->post('title','新主题');
		$logo = $this->post('logo',NULL,'intval');
		if(empty($logo))
			$logo = NULL;
		
		if($this->model('theme')->insert([
			'title' => $title,
			'logo'=> $logo,
			'createtime' => $_SERVER['REQUEST_TIME'],
			'modifytime' => $_SERVER['REQUEST_TIME'],
			'isdelete' => 0,
			'deletetime' => 0,
		]))
		{
			$data = [
				'id' => $this->model('theme')->lastInsertId(),
				'title' => $title,
				'logo' => $this->model('upload')->get($logo,'path'),
			];
            $this->model("admin_log")->insertlog($admin, '创建主题成功', 1);
			return new json(json::OK,NULL,$data);
		}
        $this->model("admin_log")->insertlog($admin, '创建主题失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
}