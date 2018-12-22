<?php

class TMAwesomeBox_AwesomeBox_Adminhtml_AwesomeboxController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {
        $this->loadLayout()->renderLayout();
    }

    public function ruleAction() {
        $this->loadLayout()->renderLayout();
    }

    public function postruleAction() {


        ini_set("display_errors", "1");
        error_reporting(E_ALL);

        //get Custom price
        $getCustomsPriceArray = $_POST['customs_price'];
        $getCustomPriceSet= array_filter($getCustomsPriceArray,
            function($checkIndex) {
                return !empty($checkIndex);
            });

        //get product custom type
        $getCustomsProductTypeArray = $_POST['product_type'];
        $getCustomProductTypeSet= array_filter($getCustomsProductTypeArray,
            function($checkProductInfoIndex) {
                return !empty($checkProductInfoIndex);
            });


        $post = $this->getRequest()->getPost();
        try {

            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $rule_enable = $this->getRequest()->getPost('rule_enable');
            $rule_name = $this->getRequest()->getPost('rule_name');
            $description = $this->getRequest()->getPost('description');
            $dateFrom = $this->getRequest()->getPost('datefrom');
            $bucket_id = $this->getRequest()->getPost('bucket_id');
            $products_id = $this->getRequest()->getPost('product');
            $products_price = implode(",",$getCustomPriceSet);


            $product_id = array();
            foreach ($products_id as $product)
                $product_id[] = addslashes($product);
                $product_id = implode(",", $product_id);

            //Save customer info into vip_user_info
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->beginTransaction();

            $__fields = array();
            $__fields['enable'] = $rule_enable;
            $__fields['name'] = $rule_name;
            $__fields['description'] = $description;
            $__fields['date_from'] = $dateFrom;
            $__fields['bucket_id'] = $bucket_id;
            $__fields['products_id'] = $product_id;
            $__fields['products_price'] = $products_price;
			$__fields['rule_status'] = 0;
            //print_r($__fields);

            //Add Rule
            $connection->insert('awesome_new_rule', $__fields);
            $awesome_new_rule_id = $connection->lastInsertId();
            $start=0;
            $types=$this->reIndex($start, $getCustomProductTypeSet);
            $price=$this->reIndex($start, $getCustomPriceSet);

            //Add associate info of product into awesome_product_info
            $pi=0;
            foreach ($products_id as $setProduct) {
                $_p_fields = array();
                $_p_fields['product_id'] = $setProduct;
                $_p_fields['type'] = $types[$pi];
                $_p_fields['price'] = $price[$pi];
                $_p_fields['bucket_id'] = $bucket_id;
                $_p_fields['rule_id'] = $awesome_new_rule_id;
                $connection->insert('awesome_product_info',$_p_fields);

                $pi++;
            }
            //print_r($_p_fields);


            $connection->commit();

            $message = $this->__('Your form has been submitted successfully.');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/rule');
    }



    public function editruleAction() {

        //get Custom price
        $getCustomsPriceArray = $_POST['customs_price'];
        $getCustomPriceSet= array_filter($getCustomsPriceArray,
            function($checkIndex) {
                return !empty($checkIndex);
            });

        //get product custom type
        $getCustomsProductTypeArray = $_POST['product_type'];
        $getCustomProductTypeSet= array_filter($getCustomsProductTypeArray,
            function($checkProductInfoIndex) {
                return !empty($checkProductInfoIndex);
            });


        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $rule_enable = $this->getRequest()->getPost('rule_enable');
            $rule_name = $this->getRequest()->getPost('rule_name');
            $description = $this->getRequest()->getPost('description');
            $datefrom = $this->getRequest()->getPost('datefrom');
            $bucket_id = $this->getRequest()->getPost('bucket_id');
            $products_id = $this->getRequest()->getPost('product');
            $products_price = implode(",",$getCustomPriceSet);
            $rule_id = $this->getRequest()->getPost('rule_id');


            $product_id = array();
            foreach ($products_id as $product)
                $product_id[] = addslashes($product);
            $product_id = implode(",", $product_id);

            //Save customer info into vip_user_info
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->beginTransaction();

            $__fields = array();
            $__fields['enable'] = $rule_enable;
            $__fields['name'] = $rule_name;
            $__fields['description'] = $description;
            $__fields['date_from'] = $datefrom;
            $__fields['bucket_id'] = $bucket_id;
            $__fields['products_id'] = $product_id;
            $__fields['products_price'] = $products_price;
            $__fields['rule_status'] = 0;

            $__where = $connection->quoteInto('id =?', $rule_id);
            $connection->update('awesome_new_rule', $__fields, $__where);


            $start=0;
            $types=$this->reIndex($start, $getCustomProductTypeSet);
            $price=$this->reIndex($start, $getCustomPriceSet);

            //Add associate info of product into awesome_product_info
            $pi=0;
            $__condition = array($connection->quoteInto('rule_id=?', $rule_id));
            $connection->delete('awesome_product_info', $__condition);
            $connection->commit();
            
            foreach ($products_id as $setProduct) {
                $_p_fields = array();
                $_p_fields['product_id'] = $setProduct;
                $_p_fields['type'] = $types[$pi];
                $_p_fields['price'] = $price[$pi];
                $_p_fields['bucket_id'] = $bucket_id;
                $_p_fields['rule_id'] = $rule_id;
                $connection->insert('awesome_product_info',$_p_fields);

                $pi++;
            }


            $connection->commit();

            $message = $this->__('Your form has been updated successfully.');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/rule');
    }


    public  function reIndex($start, $array)
    {
        /*** the end number of keys minus one ***/
        $end = ($start+count($array))-1;

        /*** the range of numbers to use as keys ***/
        $keys = range($start, $end);

        /*** combine the arrays with the new keys and values ***/
        return array_combine($keys, $array);
    }

    public function packageAction() {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $bucket_enable = $this->getRequest()->getPost('bucket_enable');
            $bucket_name = $this->getRequest()->getPost('bucket_name');
            $description = $this->getRequest()->getPost('description');
            $signup_style = $this->getRequest()->getPost('signup_style');
            $signup_color = $this->getRequest()->getPost('signup_color');
            $signup_package_price = $this->getRequest()->getPost('signup_package_price');

            //Save bucket info into awesome_new_bucket
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->beginTransaction();

            $__fields = array();
            $__fields['enable'] = $bucket_enable;
            $__fields['name'] = $bucket_name;
            $__fields['description'] = $description;
            $__fields['signup_style'] = $signup_style;
            $__fields['signup_color'] = $signup_color;
            $__fields['signup_package_price'] = $signup_package_price;

            $connection->insert('awesome_new_bucket', $__fields);
            $connection->commit();

            $message = $this->__('Your form has been submitted successfully.');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
	
	public function removeruleAction() {
		
		$id = $_GET["id"];
		try {
			 if (empty($id)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
			//Remove rule information from awesome_new_rule table
			$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
			$connection->beginTransaction();
			$__condition = array($connection->quoteInto('id=?', $id));
			$connection->delete('awesome_new_rule', $__condition);

            $awesome_product_info_info = array($connection->quoteInto('rule_id=?', $id));
            $connection->delete('awesome_product_info', $awesome_product_info_info);

			$connection->commit();
			
			$message = $this->__('Your rule has been remove successfully.');
			Mage::getSingleton('adminhtml/session')->addSuccess($message);
		}
		catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
		
		$this->_redirect('*/*/rule');
	}
	
	public function removebucketAction() {
		
		$id = $_GET["id"];
		$bucketName = $_GET["name"];
		
		 try {
			 if (empty($id)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
			
			//Remove rule information from awesome_new_rule table
			$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
			$connection->beginTransaction();
			$__condition = array($connection->quoteInto('id=?', $id));
			$connection->delete('awesome_new_bucket', $__condition);
			$connection->commit();
			
			//get rule id
			$connect = Mage::getSingleton('core/resource')->getConnection('core_read');  
			$select = $connect->select()  
				->from('awesome_new_rule', array('id'))
				->where('bucket_id=?',$id);
			$rowsArray = $connect->fetchAll($select);
			
			foreach($rowsArray as $row) { 
				$rowId = $row['id'];
				//Remove rule information from awesome_new_rule table
				$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
				$conn->beginTransaction();
				
				$__condition = array($conn->quoteInto('id=?', $rowId));
				$connection->delete('awesome_new_rule', $__condition);
				
				$conn->commit();
			}
			
			$message = $this->__('Your bucket has been removed successfully.');
			Mage::getSingleton('adminhtml/session')->addSuccess($message);
		} 
		catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
		
		$this->_redirect('*/*');
	}
	
	public function editbucketAction() {
		$post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            $bucket_id = $this->getRequest()->getPost('bucket_id');
            $bucket_enable = $this->getRequest()->getPost('bucket_enable');
            $bucket_name = $this->getRequest()->getPost('bucket_name');
            $description = $this->getRequest()->getPost('description');
            $signup_style = $this->getRequest()->getPost('signup_style');
            $signup_color = $this->getRequest()->getPost('signup_color');
            $signup_package_price = $this->getRequest()->getPost('signup_package_price');

            //Update bucket info into awesome_new_bucket
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $connection->beginTransaction();

            $__fields = array();
            $__fields['enable'] = $bucket_enable;
            $__fields['name'] = $bucket_name;
            $__fields['description'] = $description;
            $__fields['signup_style'] = $signup_style;
            $__fields['signup_color'] = $signup_color;
            $__fields['signup_package_price'] = $signup_package_price;
			
			$__where = $connection->quoteInto('id =?', $bucket_id);

            $connection->update('awesome_new_bucket', $__fields, $__where);



            $connection->commit();



            $message = $this->__('Your form has been update successfully.');
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
	}

}
