<?php
/**
 * Created by PhpStorm.
 * Date: 3/1/2015
 * Time: 3:12 AM
 */

class TMAwesomeBox_AwesomeBox_Model_Observer
{
    //  event for sales_order_save_commit_after
	public function syncOrder($observer)
	{
		$order = $observer->getOrder();
		$orderId = $observer->getOrder()->getId();
		$orderIncrementId = $observer->getOrder()->getIncrementId();
		$orderGrandTotal = $observer->getOrder()->getGrandTotal();
        $response = array(
            'orderId' => $orderId,
            'orderIncrementId' => $orderIncrementId,
            'orderGrandTotal' => $orderGrandTotal
        );
		//if($order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE){
		//if($order->getState() == Mage_Sales_Model_Order::STATE_SUBSCRIBE){
		if($order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING){

		    return $response;
		}
	}
}