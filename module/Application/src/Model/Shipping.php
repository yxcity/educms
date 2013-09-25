<?php
namespace module\Application\src\Model;

/**
 * 各快递物流公司运费计算公式 
 * 
 * @todo 完善中...
 */
class Shipping {
	
	
	public static $s_shentong = 10;//申通快递
	public static $s_yuantong = 20;//圆通快递
	public static $s_zhongtong = 30;//中通速递
	public static $s_shunfeng = 40;//顺风快递
	public static $s_presswork = 50;//挂号印刷品
	public static $s_postmail = 60;//邮局平邮
	public static $s_postexpress = 70;//邮政包裹
	public static $s_fpd = 80;//到付运费
	public static $s_flat = 90;//市内快递
	public static $s_ems = 100;//EMS
	public static $s_city = 110;//城际快递
	public static $s_cat = 120;//上门取货
	
	private $_configure = array();
	
	function __construct() {
		
	}
	
	
	function doCalculate($s_type,$goods_weight, $goods_amount, $goods_number = 0)
	{
		$config = $this->_getConfig($s_type);
		if(!$config){
			return FALSE;
		}
		foreach($config as $key=>$val)
        {
            $this->_configure[$val['name']] = $val['value'];
        }
		
		$fee = 0;
		switch($s_type){
			case self::$s_shentong:
			$fee = $this->_shentong($goods_weight, $goods_amount, $goods_number);
			break;
		}
		return $fee;
	}
	
	
	private function _getConfig($s_type = NULL)
	{
		$data = array(
			self::$s_shentong => array(),
			self::$s_yuantong => array(),
			self::$s_zhongtong => array(),
			self::$s_shunfeng => array(),
			self::$s_presswork => array(),
			self::$s_postmail => array(),
			self::$s_postexpress => array(),
			self::$s_fpd => array(),
			self::$s_flat => array(),
			self::$s_ems => array(),
			self::$s_city => array(),
			self::$s_cat => array()
		);
		if(isset($data[$s_type])){
			return $data[$s_type];
		}
		return FALSE;
	}
	
	/**
     * 计算订单的配送费用的函数
     *
     * @param   float   $goods_weight   商品重量
     * @param   float   $goods_amount   商品金额
     * @param   float   $goods_amount   商品件数
     * @return  decimal
     */
    private function _shentong($goods_weight, $goods_amount, $goods_number)
    {
        if ($this->_configure['free_money'] > 0 && $goods_amount >= $this->_configure['free_money'])
        {
            return 0;
        }
        else
        {
            @$fee = $this->_configure['base_fee'];
            $this->_configure['fee_compute_mode'] = !empty($this->_configure['fee_compute_mode']) ? $this->_configure['fee_compute_mode'] : 'by_weight';

             if ($this->_configure['fee_compute_mode'] == 'by_number')
            {
                $fee = $goods_number * $this->_configure['item_fee'];
            }
            else
            {
                if ($goods_weight > 1)
                {
                    $fee += (ceil(($goods_weight - 1))) * $this->_configure['step_fee'];
                }
            }

            return $fee;
        }
    }
	
	
}
?>