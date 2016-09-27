<?php
namespace application\control\ajax;
use system\core\ajax;
use application\message\json;
use system\core\random;
class couponno extends ajax
{
	/**
	 * 删除优惠编码
	 * @return \application\message\json
	 */
	function remove()
	{
        $admin = $this->session->id;
		$id = $this->post('id');
		if($this->model('couponno')->where('id=?',[$id])->limit(1)->update([
			'isdelete'=>1,
			'deletetime' => $_SERVER['REQUEST_TIME']
		]))
		{
            $this->model("admin_log")->insertlog($admin, '删除优惠码成功,优惠卷id：' . $id, 1);
			return new json(json::OK);
		}
        $this->model("admin_log")->insertlog($admin, '删除优惠码失败（请求参数错误）');
		return new json(json::PARAMETER_ERROR);
	}
	
	/**
	 * 添加优惠编码
	 * @return \application\message\json
	 */
	function create()
	{
        $admin=$this->session->id;
		$data = $this->post();
		$data['isdelete'] = 0;
		$data['deletetime'] = 0;
		$data['createtime'] = $_SERVER['REQUEST_TIME'];
		$data['times'] = $data['total'];
		$data['product_id'] = empty($data['product_id'])?NULL:intval($data['product_id']);
		$data['couponno_starttime'] = strtotime($data['couponno_starttime']);
		$data['couponno_endtime'] = strtotime($data['couponno_endtime']);
		if (!empty($data['couponno_endtime']))
		{
			$data['couponno_endtime'] += 24*3600;
		}
		$data['coupon_endtime'] = strtotime($data['coupon_endtime']);
		if (!empty($data['coupon_endtime']))
		{
			$data['coupon_endtime'] += 24*3600;
		}
		
		if(empty($data['couponno'])) {
            $this->model("admin_log")->insertlog($admin, '新增优惠码失败（优惠编码不能为空）');
            return new json(json::PARAMETER_ERROR, '优惠编码不能为空');
        }
		if(empty($this->model('couponno')->where('couponno=?',[$data['couponno']])->find()))
		{
			if($this->model('couponno')->insert($data))
			{
				$data['id'] = $this->model('couponno')->lastInsertId();
                $this->model("admin_log")->insertlog($admin, '新增优惠码成功',1);
				return new json(json::OK,NULL,$data);
			}
            $this->model("admin_log")->insertlog($admin, '新增优惠码失败（请求参数错误）');
			return new json(json::PARAMETER_ERROR);
		}
        $this->model("admin_log")->insertlog($admin, '新增优惠码成功（优惠编码已经使用）');
		return new json(json::PARAMETER_ERROR,'优惠编码已经使用');
	}
	
	/**
	 * 随机生成优惠编码
	 * @return \application\message\json
	 */
	function couponno()
	{
		do{
			$couponno = random::word(12);
		}
		while (!empty($this->model('couponno')->where('couponno=?',[$couponno])->find()));
		return new json(json::OK,NULL,$couponno);
	}
}