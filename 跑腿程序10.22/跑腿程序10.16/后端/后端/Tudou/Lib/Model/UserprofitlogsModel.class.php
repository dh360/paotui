
<?php
class UserprofitlogsModel extends CommonModel{
    protected $pk = 'log_id';
    protected $tableName = 'user_profit_logs';
	
	protected $Type = array(
        'goods' => '商城',
		'appoint' => '家政',
		'tuan' => '抢购',
		'ele' => '外卖',
		'booking'  => '订座',
		'breaks'=>'优惠买单',
		'hotel' =>'酒店',
		'farm'=>'农家乐', 
		'rank'=>'会员购买等级', 
		'grade'=>'商家购买等级', 
		'market' => '菜市场',
		'store' => '便利店',
    );
	
	protected $separate = array(
        1 => '已分成',
        2 => '已取消',
    );

    public function getType(){
        return $this->Type;
    }

    public function getSeparate(){
        return $this->separate;
    }
	
	//反转数组
	public function get_money_type($type){
		$types = $this->getType();
		$result = array_flip($types);//反转数组
		$types = array_search($type, $result);
		if(!empty($types)){
			return $types;
		}else{
			return false;
		}
        return false;
	}
	
	
	protected $_type = array(
		'tuan' => '抢购', 
		'ele' => '外卖', 
		'farm' => '农家乐', 
		'goods' => '商城', 
		'booking' => '订座', 
		'hotel' => '酒店',
		'appoint' => '家政',
		'breaks' => '优惠买单',
		'market' => '菜市场',
		'store' => '便利店',
	);
	
	protected $_profit_price_type = array(
		'1' => '用户实付金额', 
		'2' => '商家结算金额', 
		'3' => '用户实付金额-商家结算金额=差价', 
	);
	
	
	//分销判断权限
	public function determinePower($uid){
		$config = D('Setting')->fetchAll();
		$Users = D('Users')->find($uid);
		
		if($config['profit']['profit_min_rank_id'] == 0){
			return true;
		}
		
		$rank = D('Userrank')->find($config['profit']['profit_min_rank_id']);//后台分销配置
		$userRank = D('Userrank')->find($Users['rank_id']);//会员的分销配置
 		
        if($rank){
            if($userRank && $userRank['integral'] >= $rank['integral']){
                return true;
            }else{
               return false;
            }
			return false;
        }
		return true;
	}
	
	
	 //新版N级分销，二开QQ  120+585+022
	public function profitUsers($order_id,$id,$shop_id, $jiesuan_price, $type){
		//p($order_id.'----'.$id.'----'.$shop_id.'----'.$jiesuan_price.'----'. $type);die;
		$config = D('Setting')->fetchAll();
		$Shop = D('Shop')->where(array('shop_id'=>$shop_id))->find();
		
		//如果开通分销
		if($Shop['is_profit']){
			list($user_id,$money)= $this->getModelMoneyUser($order_id,$id,$jiesuan_price,$type);
			$obj = D('Users');
			$Users = $obj->find($user_id);
			
			
			if($money > 0){
				if($Users['fuid1'] && (true == $this->determinePower($Users['fuid1']))){
					
					$order = M('Order')->find($order_id);
					$goods_profit = M('GoodsProfit')->find($order['goods_id']);
					
					if($type == 'goods' && $goods_profit['profit_enable'] == 1 && ($goods_profit['profit_rate1']*100) > 1){
						$money1 = round($goods_profit['profit_rate1'] * $money / 100);
						$rate1 = $goods_profit['profit_rate1'];
						$info1 = $this->_type[$type]. '商城订单ID:' . $order_id . ', 一级分成: ' . round($money1 / 100, 2).'元，商品独立分成比例【'.$rate1.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'goods'){
						$money1 = round($config['profit']['goods_profit_rate1'] * $money / 100);
						$rate1 = $config['profit']['goods_profit_rate1'];
						$info1 = $this->_type[$type]. '商城订单ID:' . $order_id . ', 一级分成: ' . round($money1 / 100, 2).'元，分成比例【'.$rate1.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'hotel'){
						$money1 = round($config['profit']['hotel_profit_rate1'] * $money / 100);
						$rate1 = $config['profit']['hotel_profit_rate1'];
						$info1 = $this->_type[$type]. '酒店订单ID:' . $order_id . ', 一级分成: ' . round($money1 / 100, 2).'元，分成比例【'.$rate1.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'farm'){
						$money1 = round($config['profit']['farm_profit_rate1'] * $money / 100);
						$rate1 = $config['profit']['farm_profit_rate1'];
						$info1 = $this->_type[$type]. '农家乐订单ID:' . $order_id . ', 一级分成: ' . round($money1 / 100, 2).'元，分成比例【'.$rate1.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'breaks'){
						$money1 = round($config['profit']['breaks_profit_rate1'] * $money / 100);
						$rate1 = $config['profit']['breaks_profit_rate1'];
						$info1 = $this->_type[$type]. '优惠买单订单ID:' . $order_id . ', 一级分成: ' . round($money1 / 100, 2).'元，分成比例【'.$rate1.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}else{
						$money1 = round($config['profit']['currency_profit_rate1'] * $money / 100);
						$rate1 = $config['profit']['currency_profit_rate1'];
						$info1 = $this->_type[$type]. '订单ID:' . $order_id . ', 一级分成: ' . round($money1 / 100, 2).'元，分成比例【'.$rate1.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}
					
					if($money1 > 0){
						$fuser1 = $obj->find($Users['fuid1']);
						if ($fuser1){
							$obj->addMoney($Users['fuid1'], $money1, $info1);
							$obj->addProfit($Users['fuid1'], $order_type = 0, $type, $order_id, $shop_id,$money1, $is_separate = '1', $info1);
						}
					}
				}
				
				
				
				if($Users['fuid2'] && (true == $this->determinePower($Users['fuid2']))){
					
					
					$order = M('Order')->find($order_id);
					$goods_profit = M('GoodsProfit')->find($order['goods_id']);
					
					if($type == 'goods' && $goods_profit['profit_enable'] == 1 && ($goods_profit['profit_rate2']*100) > 1){
						$money2 = round($goods_profit['profit_rate2'] * $money / 100);
						$rate2 = $goods_profit['profit_rate2'];
						$info2 = $this->_type[$type]. '订单ID:' . $order_id . ', 二级分成: ' . round($money2/ 100, 2).'元，商品独立分成比例【'.$rate2.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'goods'){
						$money2 = round($config['profit']['goods_profit_rate2'] * $money / 100);
						$rate2 = $config['profit']['goods_profit_rate2'];
						$info2 = $this->_type[$type]. '订单ID:' . $order_id . ', 二级分成: ' . round($money2/ 100, 2).'元，分成比例【'.$rate2.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'hotel'){
						$money2 = round($config['profit']['hotel_profit_rate2'] * $money / 100);
						$rate2 = $config['profit']['hotel_profit_rate2'];
						$info2 = $this->_type[$type]. '订单ID:' . $order_id . ', 二级分成: ' . round($money2/ 100, 2).'元，分成比例【'.$rate2.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'farm'){
						$money2 = round($config['profit']['farm_profit_rate2'] * $money / 100);
						$rate2 = $config['profit']['farm_profit_rate2'];
						$info2 = $this->_type[$type]. '订单ID:' . $order_id . ', 二级分成: ' . round($money2/ 100, 2).'元，分成比例【'.$rate2.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'breaks'){
						$money2 = round($config['profit']['breaks_profit_rate2'] * $money / 100);
						$rate2 = $config['profit']['breaks_profit_rate2'];
						$info2 = $this->_type[$type]. '优惠买单订单ID:' . $order_id . ', 二级分成: ' . round($money2 / 100, 2).'元，分成比例【'.$rate2.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}else{
						$money2 = round($config['profit']['currency_profit_rate2'] * $money / 100);
						$rate2 = $config['profit']['currency_profit_rate2'];
						$info2 = $this->_type[$type]. '订单ID:' . $order_id . ', 二级分成: ' . round($money2/ 100, 2).'元，分成比例【'.$rate2.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}
					
					
					if($money2 > 0){
						$fuser2 = $obj->find($Users['fuid2']);
						if($fuser2){
							$obj->addMoney($Users['fuid2'], $money2, $info2);
							$obj->addProfit($Users['fuid2'], $order_type = 0, $type, $order_id, $shop_id,$money2, $is_separate = '1', $info2);
						}
					}
				}
				
				if($Users['fuid3'] && (true == $this->determinePower($Users['fuid3']))){
					
					$order = M('Order')->find($order_id);
					$goods_profit = M('GoodsProfit')->find($order['goods_id']);
					
					if($type == 'goods' && $goods_profit['profit_enable'] == 1 && ($goods_profit['profit_rate3']*100) > 1){
						$money3 = round($goods_profit['profit_rate3'] * $money / 100);
						$rate3 = $goods_profit['profit_rate3'];
						$info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'元，商品独立分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'goods'){
						$money3 = round($config['profit']['goods_profit_rate3'] * $money / 100);
						$rate3 = $config['profit']['goods_profit_rate3'];
						$info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'元，分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'hotel'){
						$money3 = round($config['profit']['hotel_profit_rate3'] * $money / 100);
						$rate3 = $config['profit']['hotel_profit_rate3'];
						$info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'元，分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'farm'){
						$money3 = round($config['profit']['farm_profit_rate3'] * $money / 100);
						$rate3 = $config['profit']['farm_profit_rate3'];
						$info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'元，分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}elseif($type == 'breaks'){
						$money3 = round($config['profit']['breaks_profit_rate3'] * $money / 100);
						$rate3 = $config['profit']['breaks_profit_rate3'];
						$info3 = $this->_type[$type]. '优惠买单订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'元，分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}else{
						$money3 = round($config['profit']['currency_profit_rate3'] * $money / 100);
						$rate3 = $config['profit']['currency_profit_rate3'];
						$info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'元，分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
					}
					
					
					if($money3 > 0){
						$fuser3 = $obj->find($Users['fuid3']);
						if($fuser3){
							$obj->addMoney($Users['fuid3'], $money3, $info3);
							$obj->addProfit($Users['fuid3'], $order_type = 0, $type, $order_id, $shop_id,$money3, $is_separate = '1', $info3);
						}
					}
				}
				
				
				/*
				$UserRank1 = M('UserRank')->find(1);//市级代理
				$money4 = round($UserRank1['rate'] * $money / 10000);
				$rate4 = $UserRank1['rate'];
				$info4 = $this->_type[$type].'订单ID:'.$order_id.',总价【'.round($money/100,2).'】元，【'.$UserRank1['rank_name'].'】分成:'.round($money4/100,2).'元，分成比例【'.round($rate4/100,2).'】%';
				if($money4 > 0){
					$getUserRank1 = D('Userprofitlogs')->getUserRank($user_id,1);//市级代理
					if($getUserRank1){
						$obj->addMoney($getUserRank1,$money4,$info4);
						$obj->addProfit($getUserRank1,$order_type=0,$type,$order_id,$shop_id,$money4,$is_separate='1',$info4);
					}
				}
				
				$UserRank2 = M('UserRank')->find(2);//市级代理
				$money5 = round($UserRank2['rate'] * $money / 10000);
				$rate5 = $UserRank2['rate'];
				$info5 = $this->_type[$type].'订单ID:'.$order_id.',总价【'.round($money/100,2).'】元,【'.$UserRank2['rank_name'].'】分成:'.round($money5/100,2).'元，分成比例【'.round($rate5/100,2).'】%';
				if($money5 > 0){
					$getUserRank2 = D('Userprofitlogs')->getUserRank($user_id,2);//市级代理
					if($getUserRank2){
						$obj->addMoney($getUserRank2,$money5,$info5);
						$obj->addProfit($getUserRank2,$order_type=0,$type,$order_id,$shop_id,$money5,$is_separate='1',$info5);
					}
				}
				
				$UserRank3 = M('UserRank')->find(3);//市级代理
				$money6 = round($UserRank3['rate'] * $money / 10000);
				$rate6 = $UserRank3['rate'];
				$info6 = $this->_type[$type].'订单ID:'.$order_id.',总价【'.round($money/100,2).'】元,【'.$UserRank3['rank_name'].'】分成:'.round($money6/100,2).'元，分成比例【'.round($rate6/100,2).'】%';
				if($money6 > 0){
					$getUserRank3 = D('Userprofitlogs')->getUserRank($user_id,3);//市级代理
					if($getUserRank3){
						$obj->addMoney($getUserRank3,$money6,$info6);
						$obj->addProfit($getUserRank3,$order_type=0,$type,$order_id,$shop_id,$money6,$is_separate='1',$info6);
					}
				}
				
				*/
				
				return $money1 + $money2 + $money3;//返回分成金额
				
			}
			return 0;//返回分成金额0元
		}
		
	}
	
	
	
	//获取上级
	public function getUserRank($user_id,$rank_id){
		$Users = M('Users')->find($user_id);
		
		if($Users['fuid1']){
			$fuid1 = M('Users')->find($Users['fuid1']);
			if($fuid1['rank_id'] == $rank_id){
				$uid = $Users['fuid1'];
			}
		}
		
		if($Users['fuid2']){
			$fuid2 = M('Users')->find($Users['fuid2']);
			if($fuid2['rank_id'] == $rank_id){
				$uid = $Users['fuid2'];
			}
		}
		if($Users['fuid3']){
			$fuid3 = M('Users')->find($Users['fuid3']);
			if($fuid3['rank_id'] == $rank_id){
				$uid = $Users['fuid3'];
			}
		}
		if($Users['fuid4']){
			$fuid4 = M('Users')->find($Users['fuid4']);
			if($fuid4['rank_id'] == $rank_id){
				$uid = $Users['fuid4'];
			}
		}
		if($Users['fuid5']){
			$fuid5 = M('Users')->find($Users['fuid5']);
			if($fuid5['rank_id'] == $rank_id){
				$uid = $Users['fuid5'];
			}
		}
		if($Users['fuid6']){
			$fuid6 = M('Users')->find($Users['fuid6']);
			if($fuid6['rank_id'] == $rank_id){
				$uid = $Users['fuid6'];
			}
		}
		if($Users['fuid7']){
			$fuid7 = M('Users')->find($Users['fuid7']);
			if($fuid7['rank_id'] == $rank_id){
				$uid = $Users['fuid7'];
			}
		}
		if($Users['fuid8']){
			$fuid8 = M('Users')->find($Users['fuid8']);
			if($fuid8['rank_id'] == $rank_id){
				$uid = $Users['fuid8'];
			}
		}
		if($Users['fuid9']){
			$fuid9 = M('Users')->find($Users['fuid9']);
			if($fuid9['rank_id'] == $rank_id){
				$uid = $Users['fuid9'];
			}
		}
		if($Users['fuid10']){
			$fuid10 = M('Users')->find($Users['fuid10']);
			if($fuid10['rank_id'] == $rank_id){
				$uid = $Users['fuid10'];
			}
		}
		if($Users['fuid11']){
			$fuid11 = M('Users')->find($Users['fuid11']);
			if($fuid11['rank_id'] == $rank_id){
				$uid = $Users['fuid11'];
			}
		}
		if($Users['fuid12']){
			$fuid6 = M('Users')->find($Users['fuid12']);
			if($fuid12['rank_id'] == $rank_id){
				$uid = $Users['fuid12'];
			}
		}
		if($Users['fuid13']){
			$fuid6 = M('Users')->find($Users['fuid13']);
			if($fuid13['rank_id'] == $rank_id){
				$uid = $Users['fuid13'];
			}
		}
		if($Users['fuid14']){
			$fuid14 = M('Users')->find($Users['fuid14']);
			if($fuid14['rank_id'] == $rank_id){
				$uid = $Users['fuid14'];
			}
		}
		if($Users['fuid15']){
			$fuid15 = M('Users')->find($Users['fuid15']);
			if($fuid15['rank_id'] == $rank_id){
				$uid = $Users['fuid15'];
			}
		}
		if($Users['fuid16']){
			$fuid16 = M('Users')->find($Users['fuid16']);
			if($fuid16['rank_id'] == $rank_id){
				$uid = $Users['fuid16'];
			}
		}
		if($Users['fuid17']){
			$fuid17 = M('Users')->find($Users['fuid17']);
			if($fuid17['rank_id'] == $rank_id){
				$uid = $Users['fuid17'];
			}
		}
		if($Users['fuid18']){
			$fuid18 = M('Users')->find($Users['fuid18']);
			if($fuid18['rank_id'] == $rank_id){
				$uid = $Users['fuid18'];
			}
		}
		if($Users['fuid19']){
			$fuid19 = M('Users')->find($Users['fuid19']);
			if($fuid19['rank_id'] == $rank_id){
				$uid = $Users['fuid19'];
			}
		}
		if($Users['fuid20']){
			$fuid20 = M('Users')->find($Users['fuid20']);
			if($fuid20['rank_id'] == $rank_id){
				$uid = $Users['fuid20'];
			}
		}
		return $uid;//返回分成金额0元
	}
	
	
	
	
   //获取会员ID，金额，模型
   public function getModelMoneyUser($order_id,$id,$jiesuan_price, $type){
	    $config = D('Setting')->fetchAll();
		if($type == 'ele'){
			if($config['profit']['profit_is_ele']){
				$order = D('Eleorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['need_pay'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['settlement_price'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $order['need_pay'] - $order['settlement_price'];
				}else{
					$money = 0;
				}
				D('Eleorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			}
		}elseif($type == 'market'){
			if($config['profit']['profit_is_market']){
				$order = D('Marketorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['need_pay'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['settlement_price'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $order['need_pay'] - $order['settlement_price'];
				}else{
					$money = 0;
				}
				D('Marketorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			 }
		}elseif($type == 'store'){
			if($config['profit']['profit_is_store']){
				$order = D('Storeorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['need_pay'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['settlement_price'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $order['need_pay'] - $order['settlement_price'];
				}else{
					$money = 0;
				}
				D('Storeorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			}
		}elseif($type == 'farm'){
			if($config['profit']['profit_is_farm']){
				$order = D('FarmOrder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['amount']*100;
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['jiesuan_amount']*100;
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = ($order['amount'] - $order['jiesuan_amount'])*100;
				}else{
					$money = 0;
				}
				D('FarmOrder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			 }
		}elseif($type == 'goods'){
			if($config['profit']['profit_is_goods']){
				$Order = D('Order')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $Order['need_pay'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $jiesuan_price;
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $Order['need_pay']- $jiesuan_price;
				}else{
					$money = 0;
				}
				return array($Order['user_id'],$money);
			}
		}elseif($type == 'tuan'){
			if($config['profit']['profit_is_tuan']){
				$Tuancode = D('Tuancode')->find($id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $Tuancode['real_money'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $Tuancode['settlement_price'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $Tuancode['real_money']-$Tuancode['settlement_price'];
				}else{
					$money = 0;
				}
				D('Tuancode')->save(array('code_id' => $id, 'is_profit' => 1));	
				return array($Tuancode['user_id'],$money);
			}
		}elseif($type == 'booking'){
			if($config['profit']['profit_is_booking']){
				$order = D('Bookingorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['amount'];
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['amount'];
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = $order['amount'];
				}else{
					$money = 0;
				}
				D('Bookingorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			}
		}elseif($type == 'hotel'){
			if($config['profit']['profit_is_hotel']){
				$order = D('Hotelorder')->find($order_id);
				if($config['profit']['profit_price_type'] == 1){
					$money = $order['amount']*100;
				}elseif($config['profit']['profit_price_type'] == 2){
					$money = $order['jiesuan_amount']*100;
				}elseif($config['profit']['profit_price_type'] == 3){
					$money = ($order['amount'] - $order['jiesuan_amount'])*100;
				}else{
					$money = 0;
				}
				D('Hotelorder')->save(array('order_id' => $order_id, 'is_profit' => 1));	
				return array($order['user_id'],$money);
			 }
		}elseif($type == 'breaks'){
			//如果是优惠买单
			if($config['profit']['profit_is_breaks']){
				$order = D('Breaksorder')->find($order_id);
				return array($order['user_id'],$order['need_pay']*100);
			}
			
		}
		
	
   }
   
   
   //会员购买等级三级分成，会员id，购买会员等级金额，等级昵称
	public function pay_rank_profit_user($user_id, $price,$rank_name){
		$config = D('Setting')->fetchAll();
		$obj = D('Users');
		$Users = $obj->find($user_id);
		
		if($Users['fuid1']) {
			$money1 = round($price * $config['profit']['rank_profit_rate1'] / 100);
			if ($money1 > 0) {
				$info1 = '会员昵称:' . $Users['nickanme'] . ', 购买会员等级【'.$rank_name.'】一级分成: ' . round($money1 / 100, 2);
				$fuser1 = $obj->find($Users['fuid1']);
				if($fuser1){
					$obj->addMoney($Users['fuid1'], $money1, $info1);
					$obj->addProfit($Users['fuid1'], $order_type = 0, $type = 'rank', $order_id = '0', $shop_id = '0',$money1, $is_separate = '1', $info1);
				}
			}
		}
		
		if($Users['fuid2']) {
			$money2 = round($price * $config['profit']['rank_profit_rate2'] / 100);
			if ($money2 > 0) {
				$info2 = '会员昵称:' . $Users['nickanme'] . ', 购买会员等级【'.$rank_name.'】二级分成: ' . round($money2 / 100, 2);
				$fuser2 = $obj->find($Users['fuid2']);
				if($fuser2){
					$obj->addMoney($Users['fuid2'], $money2, $info2);
					$obj->addProfit($Users['fuid2'], $order_type = 0, $type = 'rank', $order_id = '0', $shop_id = '0',$money2, $is_separate = '1', $info2);
				}
			}
		}
		
		if($Users['fuid3']) {
			$money3 = round($price * $config['profit']['rank_profit_rate3'] / 100);
			if ($money3 > 0) {
				$info3 = '会员昵称:' . $Users['nickanme'] . ', 购买会员等级【'.$rank_name.'】一级分成: ' . round($money3 / 100, 2);
				$fuser3 = $obj->find($Users['fuid3']);
				if($fuser3){
					$obj->addMoney($Users['fuid3'], $money3, $info3);
					$obj->addProfit($Users['fuid3'], $order_type = 0, $type = 'rank', $order_id = '0', $shop_id = '0',$money3, $is_separate = '1', $info3);
				}
			}
		}
   }
   
   
   
    //无折卡分销开始，传订单详情
	public function pay_zhe_profit_user($detail){
		$config = D('Setting')->fetchAll();
		$obj = D('Users');
		$Users = $obj->find($detail['user_id']);
		
		$price = $detail['need_pay'];
		
		if($Users['user_id']) {
			$money1 = round($price * $config['profit']['zhe_profit_rate1'] / 100);
			if ($money1 > 0) {
				$info1 = '会员昵称:' . $Users['nickanme'] . ', 购买无折卡订单ID【'.$detail['order_id'].'】1级分成: ' . round($money1 / 100, 2);
				$fuser1 = $obj->find($Users['fuid1']);
				if($fuser1){
					$obj->addMoney($Users['user_id'], $money1, $info1);
					$obj->addProfit($Users['user_id'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money1, $is_separate = '1', $info1);
				}
			}
		}
		
		if($Users['fuid1']) {
			$money2 = round($price * $config['profit']['zhe_profit_rate2'] / 100);
			if ($money2 > 0) {
				$info2 = '会员昵称:' . $Users['nickanme'] . ', 购买无折卡订单ID【'.$detail['order_id'].'】2级分成: ' . round($money2 / 100, 2);
				$fuser2 = $obj->find($Users['fuid1']);
				if($fuser2){
					$obj->addMoney($Users['fuid1'], $money2, $info2);
					$obj->addProfit($Users['fuid1'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money2, $is_separate = '1', $info2);
				}
			}
		}
		
		if($Users['fuid2']) {
			$money3 = round($price * $config['profit']['zhe_profit_rate3'] / 100);
			if ($money3 > 0) {
				$info3 = '会员昵称:' . $Users['nickanme'] . ', 购买无折卡订单ID【'.$detail['order_id'].'】3级分成: ' . round($money3 / 100, 2);
				$fuser3 = $obj->find($Users['fuid2']);
				if($fuser3){
					$obj->addMoney($Users['fuid2'], $money3, $info3);
					$obj->addProfit($Users['fuid2'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money3, $is_separate = '1', $info3);
				}
			}
		}
		
		/*
		if($Users['fuid1']) {
			$money1 = round($price * $config['profit']['zhe_profit_rate1'] / 100);
			if ($money1 > 0) {
				$info1 = '会员昵称:' . $Users['nickanme'] . ', 购买无折卡订单ID【'.$detail['order_id'].'】1级分成: ' . round($money1 / 100, 2);
				$fuser1 = $obj->find($Users['fuid1']);
				if($fuser1){
					$obj->addMoney($Users['fuid1'], $money1, $info1);
					$obj->addProfit($Users['fuid1'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money1, $is_separate = '1', $info1);
				}
			}
		}
		
		if($Users['fuid2']) {
			$money2 = round($price * $config['profit']['zhe_profit_rate2'] / 100);
			if ($money2 > 0) {
				$info2 = '会员昵称:' . $Users['nickanme'] . ', 购买无折卡订单ID【'.$detail['order_id'].'】2级分成: ' . round($money2 / 100, 2);
				$fuser2 = $obj->find($Users['fuid2']);
				if($fuser2){
					$obj->addMoney($Users['fuid2'], $money2, $info2);
					$obj->addProfit($Users['fuid2'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money2, $is_separate = '1', $info2);
				}
			}
		}
		
		if($Users['fuid3']) {
			$money3 = round($price * $config['profit']['zhe_profit_rate3'] / 100);
			if ($money3 > 0) {
				$info3 = '会员昵称:' . $Users['nickanme'] . ', 购买无折卡订单ID【'.$detail['order_id'].'】3级分成: ' . round($money3 / 100, 2);
				$fuser3 = $obj->find($Users['fuid3']);
				if($fuser3){
					$obj->addMoney($Users['fuid3'], $money3, $info3);
					$obj->addProfit($Users['fuid3'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money3, $is_separate = '1', $info3);
				}
			}
		}
		*/
   }
   
   
    //商家购买等级三级分成，商家id，购买等级金额，购买商家等级名称
	public function buy_shop_grade_profit_user($shop_id,$price,$grade_name){
		$config = D('Setting')->fetchAll();
		$obj = D('Users');
		$Shop = D('Shop')->find($shop_id);
		$Users = $obj->find($Shop['user_id']);
		
		if($Users['fuid1']) {
			$money1 = round($price * $config['profit']['grade_profit_rate1'] / 100);
			if ($money1 > 0) {
				$info1 = '商家【:' . $Shop['shop_name'] . '】, 购买商家等级【'.$grade_name.'】一级分成: ' . round($money1 / 100, 2);
				$fuser1 = $obj->find($Users['fuid1']);
				if($fuser1){
					$obj->addMoney($Users['fuid1'], $money1, $info1);
					$obj->addProfit($Users['fuid1'], $order_type = 0, $type = 'grade', $order_id = '0', $Shop['shop_id'],$money1, $is_separate = '1', $info1);
				}
			}
		}
		
		if($Users['fuid2']) {
			$money2 = round($price * $config['profit']['grade_profit_rate2'] / 100);
			if ($money2 > 0) {
				$info2 = '商家【:' . $Shop['shop_name'] . '】, 购买商家等级【'.$grade_name.'】二级分成: ' . round($money2 / 100, 2);
				$fuser2 = $obj->find($Users['fuid2']);
				if($fuser2){
					$obj->addMoney($Users['fuid2'], $money2, $info2);
					$obj->addProfit($Users['fuid2'], $order_type = 0, $type = 'grade', $order_id = '0', $Shop['shop_id'],$money2, $is_separate = '1', $info2);
				}
			}
		}
		
		if($Users['fuid3']) {
			$money3 = round($price * $config['profit']['grade_profit_rate3'] / 100);
			if ($money3 > 0) {
				$info3 = '商家【:' . $Shop['shop_name'] . '】, 购买商家等级【'.$grade_name.'】三级分成: ' . round($money3 / 100, 2);
				$fuser3 = $obj->find($Users['fuid3']);
				if($fuser3){
					$obj->addMoney($Users['fuid3'], $money3, $info3);
					$obj->addProfit($Users['fuid3'], $order_type = 0, $type = 'grade', $order_id = '0', $Shop['shop_id'],$money3, $is_separate = '1', $info3);
				}
			}
		}
   }
   

}
