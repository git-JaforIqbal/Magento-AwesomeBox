<?php

//Load thrid party lib
require_once(Mage::getBaseDir('lib') . '/cybersource/CybsSoapClient.php');
//load php mailer
require_once(Mage::getBaseDir('lib') . '/PHPMailer/PHPMailerAutoload.php');
require_once(Mage::getBaseDir('app') . '/Mage.php');

class TMAwesomeBox_AwesomeBox_IndexController extends Mage_Core_Controller_Front_Action
{

    private $information;
    private $shipinformation;
    private $billinformation;

    // Fix Package Info

    // Temporary package name for dev env
    Private $packageA = "Package A $75/month";
    Private $packageALbl = "";
    Private $packageAPrice = "";
    Private $PackageAPID = "";
    // Fix Package Info
    Private $packageB = "Package B $150/month";
    Private $packageBLbl = "";
    Private $packageBPrice = "";
    Private $PackageBPID = "";


    Private $adminEmail = "";
    Private $companyEmail = "";
    Private $replyto ="";


    //Payment system access info
    Private $loginname = "";
    Private $transactionkey = "";
    Private $host = "";
    Private $path = "";



    /*
    * ----------------------------------------------------------------
    * Landing page (Select Package) for subscription process
    * ----------------------------------------------------------------
    */

    public function indexAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()):
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }

    /*
    * ----------------------------------------------------------------
    * Mail confirmation for subscription process
    * ----------------------------------------------------------------
    */
    public function sendMailAction($data)
    {
        $mail = Mage::getModel('core/email');

        $mail->setToName($data->name);
        $mail->setToEmail($data->emailTo);
        $mail->setBody($data->mailBody);
        $mail->setSubject($data->mailSubject);
        $mail->setFromEmail($data->mailFrom);
        $mail->setFromName($data->mailFromName);
        $mail->setType('html');
        $html = $data->mailHtmlBody;
        $mail->setBodyHTML($html);
        try {
            $mail->send();
            Mage::getSingleton('core/session')->addSuccess('Your request has been sent');
            return 1;
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError('Unable to send.');
            return 0;
        }
    }

    /*
    * ----------------------------------------------------------------
    * Registration to get subscription process steps
    * ----------------------------------------------------------------
    */
    public function authregistrationAction()
    {
        if (isset($_POST['email']) && isset($_POST['password'])) {
            $customer = Mage::getModel('customer/customer');
            //$customer  = new Mage_Customer_Model_Customer();
            $email = $_POST['email'];
            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $password = $_POST['password'];


            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->loadByEmail($email);
            //Zend_Debug::dump($customer->debug()); exit;

            if (!$customer->getId()) {

                $customer->setEmail($email);
                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);
                $customer->setPassword($password);
            }

            try {
                $customer->save();
                $customer->setConfirmation(null);
                $customer->save();

                //Make a "login" of new customer
                Mage::getSingleton('customer/session')->loginById($customer->getId());
                $url = Mage::getBaseUrl() . 'awesomebox/index';
                $this->_redirectUrl($url);
            } catch (Exception $ex) {
                $msg = array('message' => 'Failed to create user.');
                Mage::getSingleton('core/session')->setCauthmessage($msg);
            }
        } else {
            Mage::getSingleton('core/session')->unsCauthmessage();
        }
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    /*
    * ----------------------------------------------------------------
    * Get login and register page for subscription process
    * ----------------------------------------------------------------
    */
    public function authenticationAction()
    {


        if (isset($_POST['username']) && isset($_POST['password'])) {
            $email = $_POST['username'];
            $password = $_POST['password'];
            require_once("app/Mage.php");
            umask(0);
            ob_start();
            session_start();
            Mage::app('default');
            Mage::getSingleton("core/session", array("name" => "frontend"));

            $websiteId = Mage::app()->getWebsite()->getId();
            $store = Mage::app()->getStore();
            $customer = Mage::getModel("customer/customer");
            $customer->website_id = $websiteId;
            $customer->setStore($store);

            try {
                $customer->loadByEmail($email);
                $session = Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
                $session->login($email, $password);
                $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
                if ($groupId == 4) {
                    $url = Mage::getBaseUrl() . 'customer/account';
                    $this->_redirectUrl($url);
                } else {
                    $url = Mage::getBaseUrl() . 'awesomebox/index';
                    $this->_redirectUrl($url);
                }
            } catch (Exception $e) {
                $msg = array('message' => 'Invalid username or password.');
                Mage::getSingleton('core/session')->setCauthmessage($msg);
            }
        } else {
            Mage::getSingleton('core/session')->unsCauthmessage();
        }


        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    /*
    * ----------------------------------------------------------------
    * Join us page to go subscription process
    * ----------------------------------------------------------------
    */
    public function joinusAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()):

            $this->loadLayout();
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }

    /*
     * ----------------------------------------------------------------
     * Uses inside function :
     * Params : attribute code, input label, option value array
     * $values = array('S/M', 'L/XL');
     * $this->selectAttrGenerator('selhat','Hat Size', $values);
     * ----------------------------------------------------------------
     */

    public function selectAttrGenerator($attCode, $inputLabel, $values)
    {
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        $entityTypeId = $setup->getEntityTypeId('customer');
        $attributeSetId = $setup->getDefaultAttributeSetId($entityTypeId);
        //Not using but please don't delete
        $attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
        //$helper = Mage::helper('awesomebox');

        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        $setup->startSetup();

        $setup->addAttribute("customer", "$attCode", array(
            "type" => "varchar",
            "label" => "$inputLabel",
            "input" => "select",
            "source" => "eav/entity_attribute_source_table",
            'option' => array('values' => $values),
            "visible" => true,
            "required" => false,
            'user_defined' => 0,
            "default" => "",
            "frontend" => "",
            "unique" => false,
            "note" => ""
        ));

        $attribute = Mage::getSingleton("eav/config")->getAttribute("customer", "$attCode");
        $used_in_forms = array();

        $used_in_forms[] = "adminhtml_customer";

        $attribute->setData("used_in_forms", $used_in_forms)
            ->setData("is_used_for_customer_segment", true)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 1)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100);
        $attribute->save();
    }


    /*
    * ----------------------------------------------------------------
    * Second page (Select Style) for subscription process
    * ----------------------------------------------------------------
    */
    public function secondstepAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()):
            if ($this->getRequest()->isPost()) {
                $package = $this->getRequest()->getPost('package');

                Mage::getSingleton('core/session')->setPackage($package);
            }

            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }

    /*
    * ----------------------------------------------------------------
    * Calling this function when payment getaway process is done
    * ----------------------------------------------------------------
    */
    public function createOrder($customerData, $product_id, $billinformation, $shipinformation)
    {
        Mage::app();
        $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());
        $customer = Mage::getModel('customer/customer')
            ->setWebsiteId(1)
            ->loadByEmail($customerData['email']);
        $product = Mage::getModel('catalog/product')->load($product_id);
        $buyInfo = array(
            'qty' => 1,
        );
        $quote->addProduct($product, new Varien_Object($buyInfo));
        $billingAddressData = array(
            'firstname' => $billinformation[0],
            'lastname' => $billinformation[2],
            'street' => $billinformation[4],
            'city' => $billinformation[5],
            'postcode' => $billinformation[8],
            'telephone' => $billinformation[9],
            'country_id' => $billinformation[6],
            'region_id' => $billinformation[7],
        );

        $shippingAddressData = array(
            'firstname' => $shipinformation[0],
            'lastname' => $shipinformation[2],
            'street' => $shipinformation[4],
            'city' => $shipinformation[5],
            'postcode' => $shipinformation[8],
            'telephone' => $shipinformation[9],
            'country_id' => $shipinformation[6],
            'region_id' => $shipinformation[7],
        );

        $_shipping_custom_address = array(
            'firstname' => $shipinformation[0],
            'lastname' => $shipinformation[2],
            'street' => array(
                '0' => $shipinformation[4],
                '1' => '',
            ),

            'city' => $shipinformation[5],
            'region_id' => $shipinformation[7],
            'region' => '',
            'postcode' => $shipinformation[8],
            'country_id' => $shipinformation[6],
            'telephone' => $shipinformation[9],
        );
        $_billing_custom_address = array(
            'firstname' => $shipinformation[0],
            'lastname' => $shipinformation[2],
            'street' => array(
                '0' => $shipinformation[4],
                '1' => '',
            ),

            'city' => $shipinformation[5],
            'region_id' => $shipinformation[7],
            'region' => '',
            'postcode' => $shipinformation[8],
            'country_id' => $shipinformation[6],
            'telephone' => $shipinformation[9],
        );
        $customAddress = Mage::getModel('customer/address');
        $customerData = Mage::getSingleton('customer/session')->getCustomer();
        //$customAddress = new Mage_Customer_Model_Address();
        $customAddress->setData($_shipping_custom_address)
            ->setCustomerId($customerData->getId())
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');

        $customAddress->setData($_billing_custom_address)
            ->setCustomerId($customerData->getId())
            ->setIsDefaultBilling('1')
            ->setSaveInAddressBook('1');

        try {
            $customAddress->save();
        } catch (Exception $ex) {
            //Zend_Debug::dump($ex->getMessage());
        }


        $quote->getBillingAddress()->addData($billingAddressData);
        $shippingAddress = $quote->getShippingAddress()->addData($shippingAddressData);


        $shippingAddress->setFreeShipping(true)
            ->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping')
            ->setPaymentMethod('checkmo');


        $quote->getPayment()->importData(array('method' => 'checkmo'));
        $quote->collectTotals()->save();

        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();

        return $order->getIncrementId();
    }


    /*
    * ----------------------------------------------------------------
    * Calling this function to change order status after make a
    * order
    * ----------------------------------------------------------------
    */
    public function changeOrderStatus($orderId)
    {
        try {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $order->setData('state', "complete");
            $order->setStatus("subscribe");
            $order->save();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    /*
    * ----------------------------------------------------------------
    * Third step (Select Color) for subscription process
    * ----------------------------------------------------------------
    */
    public function thirdstepAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()):
            if ($this->getRequest()->isPost()) {
                $style = $this->getRequest()->getPost('style');
                Mage::getSingleton('core/session')->setStyle($style);
            }
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }


    /*
    * ----------------------------------------------------------------
    * Fourth step (Select Sizing) for subscription process
    * ----------------------------------------------------------------
    */
    public function fourthstepAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()):
            if ($this->getRequest()->isPost()) {
                $color = $this->getRequest()->getPost('color');
                Mage::getSingleton('core/session')->setColor($color);
            }
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }


    /*
    * ----------------------------------------------------------------
    * Fourth step (Select Sizing) for subscription process
    * ----------------------------------------------------------------
    */
    public function fifthstepAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()):
            if ($this->getRequest()->isPost()) {
                $shirt_polos = $this->getRequest()->getPost('shirt-polos');
                $outerwear = $this->getRequest()->getPost('outerwear');
                $bottoms = $this->getRequest()->getPost('bottoms');
                $shoes = $this->getRequest()->getPost('shoes');
                $belt = $this->getRequest()->getPost('belt');
                $hat = $this->getRequest()->getPost('hat');

                $selected_size = array($shirt_polos, $outerwear, $bottoms, $shoes, $belt, $hat);
                Mage::getSingleton('core/session')->setSize($selected_size);
            }
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }


    /*
    * ----------------------------------------------------------------
    * Fifth step (Account Information + Payment)
    * for subscription process
    * ----------------------------------------------------------------
    */
    public function sixstepAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()):
            if ($this->getRequest()->isPost()) {


                /** Shipping information **/
                $ship_firstname = $this->getRequest()->getPost('ship_firstname');
                $ship_middlename = $this->getRequest()->getPost('ship_middlename');
                $ship_lastname = $this->getRequest()->getPost('ship_lastname');
                $ship_company = $this->getRequest()->getPost('ship_company');
                $ship_street = $this->getRequest()->getPost('ship_street');
                $ship_city = $this->getRequest()->getPost('ship_city');
                $ship_country_id = $this->getRequest()->getPost('ship_country_id');
                $ship_state = $this->getRequest()->getPost('ship_state');
                $ship_postcode = $this->getRequest()->getPost('ship_postcode');
                $ship_telephone = $this->getRequest()->getPost('ship_telephone');
                $ship_fax = $this->getRequest()->getPost('ship_fax');

                /** Billing  information **/
                $bill_firstname = $this->getRequest()->getPost('bill_firstname');
                $bill_middlename = $this->getRequest()->getPost('bill_middlename');
                $bill_lastname = $this->getRequest()->getPost('bill_lastname');
                $bill_company = $this->getRequest()->getPost('bill_company');
                $bill_street = $this->getRequest()->getPost('bill_street');
                $bill_city = $this->getRequest()->getPost('bill_city');
                $bill_country_id = $this->getRequest()->getPost('bill_country_id');
                $bill_state = $this->getRequest()->getPost('bill_state');
                $bill_postcode = $this->getRequest()->getPost('bill_postcode');
                $bill_telephone = $this->getRequest()->getPost('bill_telephone');
                $bill_fax = $this->getRequest()->getPost('bill_fax');

                $sameasbilling = $this->getRequest()->getPost('sameasbilling');


                $email = $this->getRequest()->getPost('email');
                $password = '';//$this->getRequest()->getPost('password');
                $fname = $ship_firstname;
                $lname = $ship_lastname;
                $address = $ship_street;
                $state = $ship_state;
                $city = $ship_city;
                $country = $ship_country_id;
                $zip = $ship_postcode;
                $card = $this->getRequest()->getPost('card');
                $expdate = $this->getRequest()->getPost('expmonth') . '/' . $this->getRequest()->getPost('expyear');
                $cvv = $this->getRequest()->getPost('cvv');


                $this->information = array($email, $password, $fname, $lname, $address, $city, $state, $zip, $card, $expdate, $cvv, $country);
                $this->shipinformation = array($ship_firstname, $ship_middlename, $ship_lastname, $ship_company, $ship_street, $ship_city, $ship_country_id, $ship_state, $ship_postcode, $ship_telephone, $ship_fax, $sameasbilling);
                $this->billinformation = array($bill_firstname, $bill_middlename, $bill_lastname, $bill_company, $bill_street, $bill_city, $bill_country_id, $bill_state, $bill_postcode, $bill_telephone, $bill_fax, $sameasbilling);


                Mage::getSingleton('core/session')->setInformation($this->information);
                Mage::getSingleton('core/session')->setShipinformation($this->shipinformation);
                Mage::getSingleton('core/session')->setBillinformation($this->billinformation);
            }
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }


    public function getRegionCollectionAction()
    {
        $countryCode = $_POST['countryCode'];
        $knock = $_POST['knock'];
        $regionCollection = Mage::getModel('directory/region_api')->items($countryCode);
        if ($knock == 'ship') {
            $startTag = '<select id="state" class="form-control required ship_state" name="ship_state" size="1">';
        } elseif ($knock == 'bill') {
            $startTag = '<select id="state" class="form-control required bill_state" name="bill_state" size="1">';
        } else {
            $startTag = '<select id="state" class="form-control required state" name="state" size="1">';
        }

        $tagContent = '';
        foreach ($regionCollection as $getRegionCollection) {
            $tagContent .= '<option value="' . $getRegionCollection['region_id'] . '">' . $getRegionCollection['name'] . '</option>';
        }
        $endTag = '</select>';
        return $this->getResponse()->setBody(json_encode($startTag . $tagContent . $endTag));
    }

    public function getRegionCollections($countryCode, $regionCode)
    {

        $regionCollection = Mage::getModel('directory/region_api')->items($countryCode);
        $getRegionName = '';
        foreach ($regionCollection as $getRegionCollection) {
            if ($getRegionCollection['region_id'] == $regionCode) {
                $getRegionName = $getRegionCollection['name'];
            }
        }
        return $getRegionName;
    }


    /*
    * ----------------------------------------------------------------
    * Confirmation step (Account Information + Payment)
    * for subscription process
    * ----------------------------------------------------------------
    */
    public function confirmationAction()
    {


        if (Mage::getSingleton('customer/session')->isLoggedIn()):
            if ($this->getRequest()->isPost()) {
                $user_info = Mage::getSingleton('core/session')->getInformation();
                $user_package = Mage::getSingleton('core/session')->getPackage();
                $user_style = Mage::getSingleton('core/session')->getStyle();
                $user_color = Mage::getSingleton('core/session')->getColor();
                $user_size = Mage::getSingleton('core/session')->getSize();

                $shipinformation = Mage::getSingleton('core/session')->getShipinformation();
                $billinformation = Mage::getSingleton('core/session')->getBillinformation();

                //print_r($user_info);
                if ($user_package == $this->packageA) {
                    $package = 'Package A';
                    $price = 75.00;
                } elseif ($user_package == $this->packageB) {
                    $package = 'Package B';
                    $price = 150.00;
                }
                $referId = rand(1000000, 1900000);
                $startDate = date('Y-m-d');
                $experDate = '2016-03';
                $strtotimes = explode("/", $user_info[9]);
                $expirationMonth = $strtotimes[0];
                $expirationYear = $strtotimes[1];

                //User data set
                $firstName = $user_info[2];
                $lastName = $user_info[3];
                $street1 = $billinformation[4];
                $city = $billinformation[5];
                $state = $billinformation[7];
                $postalCode = $billinformation[8];
                $country = $billinformation[6];
                $email = $user_info[0];
                $ipAddress = $this->get_client_ip(); //exp: '10.7.111.111'
                $accountNumber = $user_info[8];
                $expirationMonth = $expirationMonth;
                $expirationYear = $expirationYear;
                $cardType = $user_info[10];
                //$cardType = '001';
                $currency = "USD";
                $frequency = 'monthly';
                $amount = $price;
                $automaticRenew = 'false';
                $numberOfPayments = '4';
                $startDate = date('Ymd');

                $stateForP = $this->getRegionCollections($country, $state);
                $customer = Mage::getSingleton('customer/session')->getCustomer();


                //==============================create order script=====================
                $user_package = Mage::getSingleton('core/session')->getPackage();
                $customerData = array('email' => $email);


                /*
                 * --------------------------------
                 * Get selected Package to get
                 * package product
                 * --------------------------------
                 */

                if ($user_package == $this->packageA) {
                    $package = $this->packageALbl;
                    $price = $this->packageAPrice;
                    //4197 	Package A $75/month
                    $product_id = $this->PackageAPID;
                    $order_id = $this->createOrder($customerData, $product_id, $billinformation, $shipinformation);
                    $this->changeOrderStatus($order_id);
                    //Mage::log('Awesomebox Order id : ' . $order_id);


                    //get final product price after generating order
                    $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                    $OrderAmount = $order->getSubtotal() + $order->getTaxAmount();


                } elseif ($user_package == $this->packageB) {
                    $package = $this->packageBLbl;
                    $price = $this->packageBPrice;
                    //4198 	Package B $150/month
                    $product_id = $this->PackageBPID;
                    $order_id = $this->createOrder($customerData, $product_id, $billinformation, $shipinformation);
                    $this->changeOrderStatus($order_id);
                    //Mage::log('Awesomebox Order id : ' . $order_id);


                    //get final product price after generating order
                    $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                    $OrderAmount = $order->getSubtotal() + $order->getTaxAmount();


                }


                /*
                 * --------------------------------
                 * Send subscription request to
                 * the payment processor
                 * --------------------------------
                 */
                $createPaymentProfile = $this->createCyberSubscription($firstName, $lastName, $street1, $city, $stateForP, $postalCode, $country, $email, $ipAddress, $accountNumber, $expirationMonth, $expirationYear, $cardType, $currency, $frequency, $OrderAmount, $automaticRenew, $numberOfPayments, $startDate);
                //print_r($createPaymentProfile);


                /*
                 * --------------------------------
                 * Check payment processor response
                 * code so 100 means successful
                 * --------------------------------
                 */
                if ($createPaymentProfile['reason_code'] == 100) {


                    //Send Email
                    $to = $user_info[0];
                    $name = "AwesomeBox";
                    $subject = 'Awesome Registration Confirmation';

                    $content = $this->awesomeBoxSignUpMail($order_id);
                    $from = $this->adminEmail;
                    $this->sendAwesomeMail($to, $from, $subject, $name, $content);
                    $status = '';

                    if (Mage::getSingleton('customer/session')->isLoggedIn()) {

                        // Load the customer's data
                        $customer = Mage::getSingleton('customer/session')->getCustomer();

                        //Update customer info
                        $customer->setFirstname($user_info[2])
                            ->setLastname($user_info[3])
                            ->setEmail($user_info[0])
                            //->setPassword($user_info[1])
                            ->setTmpackage($user_package)
                            ->setTmstyle($user_style)
                            ->setTmcolor($user_color)
                            ->setTmshirtpolo($user_size[0])
                            ->setTmouterwear($user_size[1])
                            ->setTmpantshort($user_size[2])
                            ->setTmshoe($user_size[3])
                            ->setTmbelt($user_size[4])
                            ->setTmhat($user_size[5])
                            ->setGroupId(4);

                    }


                    try {
                        //save customer information
                        $customer->save();
                        $customer->setConfirmation(null);
                        $customer->save();


                        //update/save customer shipping and billing address
                        $address = Mage::getModel("customer/address");
                        $address->setCustomerId($customer->getId())
                            ->setFirstname($customer->getFirstname())
                            ->setLastname($customer->getLastname())
                            ->setPostcode($user_info[7])
                            ->setCity($user_info[5])
                            ->setStreet($user_info[4])
                            ->setRegionId($state)
                            ->setIsDefaultBilling('1')
                            ->setIsDefaultShipping('1')
                            ->setSaveInAddressBook('1');
                        $address->save();

                        //Save Subscription info
                        $customer_id = $customer->getId();
                        $subscriptionId = $createPaymentProfile['subscription_id'];
                        $config = Mage::getConfig()->getResourceConnectionConfig("default_setup");
                        //Config
                        $servername = $config->host;
                        $username = $config->username;
                        $password = $config->password;
                        $dbname = $config->dbname;

                        $susId = $createPaymentProfile['subscription_id'];
                        // Create connection
                        $conn = new mysqli($servername, $username, $password, $dbname);

                        $AuthInfoSet = $createPaymentProfile['auth_info'];
                        //Save authorization info
                        if ($AuthInfoSet['reason_code'] == 100) {

                            //Auth set insert
                            $requestID = $AuthInfoSet['requestID'];
                            $requestToken = $AuthInfoSet['requestToken'];
                            $authorizationCode = $AuthInfoSet['authorizationCode'];
                            $reconciliationID = $AuthInfoSet['reconciliationID'];
                            //echo "INSERT INTO tm_authinfo (id, customer_id, reason_code, request_id, requestToken, authorizationCode, reconciliation_id)  VALUES ('',$customer_id,'100',$requestID,$requestToken,$authorizationCode,$reconciliationID)";
                            $authSql = "INSERT INTO tm_authinfo (id, customer_id, reason_code, request_id, requestToken, authorizationCode, reconciliation_id)  VALUES ('','$customer_id','100','$requestID','$requestToken','$authorizationCode','$reconciliationID')";
                            $conn->query($authSql);

                            //Subscription set insert
                            $SubscriptionSql = "INSERT INTO tm_subscription (id, customer_id, subscription_id, auth_status) VALUES ('',$customer_id,$susId,'1')";
                            $conn->query($SubscriptionSql);
                            //die();
                        } else {
                            //Subscription set insert
                            $SubscriptionSql = "INSERT INTO tm_subscription (id, customer_id, subscription_id, auth_status) VALUES ('',$customer_id,$susId,'0')";
                            $conn->query($SubscriptionSql);
                        }


                        $msg = array('message' => 'Subscription successfully created.');
                        Mage::getSingleton('core/session')->setStatusmessage($msg);


                        $customer = Mage::getSingleton('customer/session')->getCustomer();
                        $address = Mage::getModel("customer/address");
//                        $address->setCustomerId($customer->getEntityId())
//                            ->setFirstname($customer->getFirstname())
//                            ->setMiddleName($customer->getLastname())
//                            ->setLastname($customer->getLastname())
//                            ->setCountryId($country)
//                            ->setRegionId($state)//state/province, only needed if the country is USA
//                            ->setPostcode($postalCode)
//                            ->setCity($city)
////                            ->setTelephone('0000000000000')
////                            ->setFax('')
////                            ->setCompany('')
//                            ->setStreet($street1)
//                            ->setIsDefaultBilling('1')
//                            ->setIsDefaultShipping('1')
//                            ->setSaveInAddressBook('1');
//                        $address->save();

                    } catch (Exception $e) {
                        $msg = array('message' => 'Failed to create subscription. <br/> <b>Reason:</b> ' . $e->getMessage());
                        Mage::getSingleton('core/session')->setStatusmessage($msg);
                    }
                } else {

                    /*
                     * ----------------------------------
                     * change order status to 'Canceled'
                     * ----------------------------------
                     */
                    $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                    if ($order->canCancel()) {
                        $order->cancel()->save();
                    }

                    $msg = array('message' => 'Failed to create subscription.');
                    Mage::getSingleton('core/session')->setStatusmessage($msg);
                }
            }


            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();
        else:
            $this->_redirect('awesomebox/index/authentication');
        endif;
    }

    public function ajaxAction()
    {
        $customer = Mage::getModel('customer/customer');
        $websiteId = Mage::app()->getWebsite()->getId();

        if (array_key_exists('email', $_POST)) {
            $email = $_POST['email'];
        } else {
            $this->getResponse()->setBody(false);
            return;
        }
        if ($websiteId) {
            $customer->setWebsiteId($websiteId);
        }
        $customer->loadByEmail($email);
        if ($customer->getId()) {
            $this->getResponse()->setBody(true);
            return;
        }
        $this->getResponse()->setBody(false);
        return;
    }

    /*
     * ----------------------------------------------------------------
     * Uses inside function :
     * Params : attribute code, input type, input label
     * $this->attrGenerator('myattrbutecode3','text','My Attr Label');
     * ----------------------------------------------------------------
     */

    public function attrGenerator($attCode, $inputType, $inputLabel)
    {
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        $entityTypeId = $setup->getEntityTypeId('customer');
        $attributeSetId = $setup->getDefaultAttributeSetId($entityTypeId);
        //Not using but please don't delete
        $attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
        //$helper = Mage::helper('awesomebox');

        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        $setup->startSetup();

        $setup->addAttribute("customer", "$attCode", array(
            "type" => "varchar",
            "backend" => "",
            "label" => "$inputLabel",
            "input" => "$inputType",
            "source" => "",
            "visible" => true,
            "required" => false,
            "default" => "",
            "frontend" => "",
            "unique" => false,
            "note" => ""
        ));

        $attribute = Mage::getSingleton("eav/config")->getAttribute("customer", "$attCode");
        $used_in_forms = array();

        $used_in_forms[] = "adminhtml_customer";

        $attribute->setData("used_in_forms", $used_in_forms)
            ->setData("is_used_for_customer_segment", true)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 1)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100);
        $attribute->save();
    }

    public function createCybersAuthorize()
    {

        // Before using this example, you can use your own reference code for the transaction.
        $referenceCode = '14344';
        $client = new CybsSoapClient();
        $request = $client->createRequest($referenceCode);

        $paySubscriptionCreateService = new stdClass();
        $paySubscriptionCreateService->run = 'true';
        $request->paySubscriptionCreateService = $paySubscriptionCreateService;

        $authorizeInfo = new stdClass();
        $authorizeInfo->amount = '25';
        $authorizeInfo->subscriptionId = 'false';


        $request->authorizeInfo = $authorizeInfo;
        $reply = $client->runTransaction($request);

        if ($reply->decision != 'ACCEPT') {
            $resultSetData = array(
                'reason_code' => $reply->reasonCode,
                'invalidField' => $reply->invalidField
            );
        } else {
            $paySubscriptionCreateReply = $reply->paySubscriptionCreateReply;
            $resultSetData = array(
                'reason_code' => $paySubscriptionCreateReply->reasonCode,
                'subscription_id' => $paySubscriptionCreateReply->subscriptionID,
            );
        }
        return $resultSetData;
    }


    //Credit Card Authorization
    public function tmAuthFollowOnCaptureProcess($firstName, $lastName, $street1, $city, $state, $postalCode, $country, $email, $accountNumber, $expirationMonth, $expirationYear, $amount)
    {
        $referenceCode = 14344;
        $client = new CybsSoapClient();
        $request = $client->createRequest($referenceCode);

        // This section contains a sample transaction request for the authorization
        // service with complete billing, payment card, and purchase (two items) information.
        $ccAuthService = new stdClass();
        $ccAuthService->run = 'true';
        $request->ccAuthService = $ccAuthService;

        $billTo = new stdClass();
        $billTo->firstName = $firstName;
        $billTo->lastName = $lastName;
        $billTo->street1 = $street1;
        $billTo->city = $city;
        $billTo->state = $state;
        $billTo->postalCode = $postalCode;
        $billTo->country = $country;
        $billTo->email = $email;
        $billTo->ipAddress = '10.7.111.111';
        $request->billTo = $billTo;

        $card = new stdClass();
        $card->accountNumber = $accountNumber;
        $card->expirationMonth = $expirationMonth;
        $card->expirationYear = $expirationYear;
        $request->card = $card;

        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = 'USD';
        $request->purchaseTotals = $purchaseTotals;

        $item0 = new stdClass();
        $item0->unitPrice = $amount;
        $item0->quantity = '1';
        $item0->id = '0';


        $request->item = array($item0);

        $reply = $client->runTransaction($request);

        if ($reply->decision != 'ACCEPT') {
            $resultSetData = array(
                'reason_code' => $reply->reasonCode
            );
        } else {
            //$paySubscriptionCreateReply = $reply->paySubscriptionCreateReply;
            $ccAuthReply = $reply->ccAuthReply;
            $resultSetData = array(
                'reason_code' => $reply->reasonCode,
                'requestID' => $reply->requestID,
                'requestToken' => $reply->requestToken,
                'authorizationCode' => $ccAuthReply->authorizationCode,
                'reconciliationID' => $ccAuthReply->reconciliationID
            );
        }

        return $resultSetData;

    }

    public function chargeToCustomer($authRequestIdGet, $unitPriceGet)
    {

        $referenceCode = 14344;
        $client = new CybsSoapClient();
        $request = $client->createRequest($referenceCode);


        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = 'USD';
        $request->purchaseTotals = $purchaseTotals;

        $item0 = new stdClass();
        $item0->unitPrice = $unitPriceGet;
        $item0->quantity = '1';
        $item0->id = '0';

        // Build a capture using the request ID in the response as the auth request ID
        $ccCaptureService = new stdClass();
        $ccCaptureService->run = 'true';
        $ccCaptureService->authRequestID = $authRequestIdGet;
        $captureRequest = $client->createRequest($referenceCode);
        $captureRequest->ccCaptureService = $ccCaptureService;
        $captureRequest->item = array($item0);
        $captureRequest->purchaseTotals = $purchaseTotals;

        $captureReply = $client->runTransaction($captureRequest);

        return $captureReply;
    }

    public function chargetocustomerAction()
    {

        $referenceCode = 14344;
        $client = new CybsSoapClient();
        $request = $client->createRequest($referenceCode);


        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = 'USD';
        $request->purchaseTotals = $purchaseTotals;

        $item0 = new stdClass();
        //$item0->unitPrice = $unitPriceGet;
        $item0->unitPrice = '75.00';
        $item0->quantity = '1';
        $item0->id = '0';

        // Build a capture using the request ID in the response as the auth request ID
        $ccCaptureService = new stdClass();
        $ccCaptureService->run = 'true';
        //$ccCaptureService->authRequestID = $authRequestIdGet;
        $ccCaptureService->authRequestID = '4357777222645000001515';
        $captureRequest = $client->createRequest($referenceCode);
        $captureRequest->ccCaptureService = $ccCaptureService;
        $captureRequest->item = array($item0);
        $captureRequest->purchaseTotals = $purchaseTotals;

        $captureReply = $client->runTransaction($captureRequest);

        return $captureReply;
    }

    public function createCyberSubscription($firstName, $lastName, $street1, $city, $state, $postalCode, $country, $email, $ipAddress, $accountNumber, $expirationMonth, $expirationYear, $cardType, $currency, $frequency, $amount, $automaticRenew, $numberOfPayments, $startDate)
    {
        $referenceCode = '14344';
        $client = new CybsSoapClient();
        $request = $client->createRequest($referenceCode);
        $paySubscriptionCreateService = new stdClass();
        $paySubscriptionCreateService->run = 'true';
        $request->paySubscriptionCreateService = $paySubscriptionCreateService;
        $billTo = new stdClass();
        $billTo->firstName = $firstName;
        $billTo->lastName = $lastName;
        $billTo->street1 = $street1;
        $billTo->city = $city;
        $billTo->state = $state;
        $billTo->postalCode = $postalCode;
        $billTo->country = 'US';
        $billTo->email = $email;
        $billTo->ipAddress = '27.131.14.6';
        $request->billTo = $billTo;


        $card = new stdClass();
        $card->accountNumber = $accountNumber;
        $card->expirationMonth = $expirationMonth;
        $card->expirationYear = $expirationYear;
        $card->cardType = '001';
        $request->card = $card;

        $purchaseTotals = new stdClass();
        $purchaseTotals->currency = 'USD';
        $request->purchaseTotals = $purchaseTotals;

        $recurringSubscriptionInfo = new stdClass();
        $recurringSubscriptionInfo->frequency = 'monthly';
        $recurringSubscriptionInfo->amount = $amount;
        $recurringSubscriptionInfo->automaticRenew = 'false';
        $recurringSubscriptionInfo->numberOfPayments = '4';
        $recurringSubscriptionInfo->startDate = $startDate;

        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        $reply = $client->runTransaction($request);


        if ($reply->decision != 'ACCEPT') {
            $this->tmAuthFollowOnCaptureProcess();
            $resultSetData = array(
                'reason_code' => $reply->reasonCode,
                'invalidField' => $reply->invalidField
            );
        } else {
            $paySubscriptionCreateReply = $reply->paySubscriptionCreateReply;
            $ccAuthReply = $reply->ccAuthReply;
            $createAuth = $this->tmAuthFollowOnCaptureProcess($firstName, $lastName, $street1, $city, $state,
                $postalCode, $country, $email, $accountNumber,
                $expirationMonth, $expirationYear, $amount);

            $authReason_code = $createAuth['reason_code'];
            if ($authReason_code == 100) {
                $resultSetData = array(
                    'reason_code' => $paySubscriptionCreateReply->reasonCode,
                    'subscription_id' => $paySubscriptionCreateReply->subscriptionID,
                    'requestID' => $reply->requestID,
                    'requestToken' => $reply->requestToken,
                    'authorizationCode' => $ccAuthReply->authorizationCode,
                    'reconciliationID' => $ccAuthReply->reconciliationID,
                    'auth_info' => array(
                        'reason_code' => $createAuth['reason_code'],
                        'requestID' => $createAuth['requestID'],
                        'requestToken' => $createAuth['requestToken'],
                        'authorizationCode' => $createAuth['authorizationCode'],
                        'reconciliationID' => $createAuth['reconciliationID']
                    )
                );
            } else {
                $resultSetData = array(
                    'reason_code' => $paySubscriptionCreateReply->reasonCode,
                    'subscription_id' => $paySubscriptionCreateReply->subscriptionID,
                    'requestID' => $reply->requestID,
                    'requestToken' => $reply->requestToken,
                    'authorizationCode' => $ccAuthReply->authorizationCode,
                    'reconciliationID' => $ccAuthReply->reconciliationID,
                    'auth_info' => array(
                        'reason_code' => $createAuth['reason_code']
                    )
                );
            }


        }
        return $resultSetData;
    }

    public function cancelSubscriptionAction()
    {

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $customer_id = $customerData->getId();
            //echo $customer_id;
            $core_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $core_read->beginTransaction();
            $customerSel = $core_read->select()
                ->from('tm_subscription', array('subscription_id'))
                ->where('customer_id=?', $customer_id);
            $customerData = $core_read->fetchRow($customerSel);
            $subscription_id = $customerData['subscription_id'];

            //die();
            $core_read->commit();

            // Before using this example, you can use your own reference code for the transaction.
            $referenceCode = '14344';
            $client = new CybsSoapClient();
            $request = $client->createRequest($referenceCode);
            // This section contains a sample transaction request for creating a subscription

            $paySubscriptionDeleteService = new stdClass();
            $paySubscriptionDeleteService->run = 'true';
            $request->paySubscriptionDeleteService = $paySubscriptionDeleteService;

            $recurringSubscriptionInfo = new stdClass();
            $recurringSubscriptionInfo->subscriptionID = $subscription_id;
            $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

            //print_r($request);
            $reply = $client->runTransaction($request);
            Mage::getSingleton('core/session')->unsCauthmessage();

            if ($reply->decision == 'ACCEPT') {
                //$msg = array('cancel_message' => 'Successfully deleted.');
                //Mage::getSingleton('core/session')->setCauthmessage($msg);

                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $customer->setGroupId(1);

                try {
                    $customer->save();
                    $customer->setConfirmation(null);
                    $customer->save();

                    $to = 'tmsys@mail.com';
                    $name = "AwesomeBox";
                    $subject = 'Awesome canceled subscription';

                    $content = 'subscription is canceled successfully';
                    $from = $this->adminEmail;
                    $this->sendAwesomeMail($to, $from, $subject, $name, $content);

                } catch (Exception $e) {
                    Mage::getSingleton('core/session')->setStatusmessage($e->getMessage());
                }
                Mage::getSingleton('core/session')->addSuccess('Successfully deleted.');
            } else {
                //$msg = array('cancel_message' => 'Failed to delete.');
                //Mage::getSingleton('core/session')->setCauthmessage($msg);
                Mage::getSingleton('core/session')->addError('Failed to delete.');
            }

            $this->_redirect('customer/account/');

        } else {
            echo "Please login your subscription account.";
        }
    }

    public function updateSubscriptionAction()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {

            if ($this->getRequest()->isPost()) {
                $cardNumber = $this->getRequest()->getPost('card');
                $expMonth = $this->getRequest()->getPost('expmonth');
                $expYear = $this->getRequest()->getPost('expyear');
                $cvv = $this->getRequest()->getPost('cvv');

                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $customer_id = $customerData->getId();
                //echo $customer_id;
                $core_read = Mage::getSingleton('core/resource')->getConnection('core_read');
                $core_read->beginTransaction();
                $customerSel = $core_read->select()
                    ->from('tm_subscription', array('subscription_id'))
                    ->where('customer_id=?', $customer_id);
                $customerData = $core_read->fetchRow($customerSel);
                $subscription_id = $customerData['subscription_id'];

                //die();
                $core_read->commit();

                // Before using this example, you can use your own reference code for the transaction.
                $referenceCode = '14344';

                $client = new CybsSoapClient();
                $request = $client->createRequest($referenceCode);

                // This section contains a sample transaction request for creating a subscription
                $paySubscriptionUpdateService = new stdClass();
                $paySubscriptionUpdateService->run = 'true';
                $request->paySubscriptionUpdateService = $paySubscriptionUpdateService;

                //die($cardNumber.' '.$expMonth.' '.$expYear.' '.$cvv);
                $card = new stdClass();
                //Get value from account update form
                $card->accountNumber = $cardNumber;
                $card->expirationMonth = $expMonth;
                $card->expirationYear = $expYear;
                //$card->cardType= $cvv;
                $card->cardType = '001';
                $request->card = $card;

                $recurringSubscriptionInfo = new stdClass();
                $recurringSubscriptionInfo->subscriptionID = $subscription_id;
                $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

                $reply = $client->runTransaction($request);

                Mage::getSingleton('core/session')->unsCauthmessage();

                if ($reply->decision == 'ACCEPT') {
                    Mage::getSingleton('core/session')->addSuccess('Successfully deleted.');
                } else {
                    Mage::getSingleton('core/session')->addError('Failed to delete.');
                }

                $this->_redirect('customer/account/');
            }

            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->renderLayout();

        } else {
            $this->_redirect('awesomebox/index/authentication');
        }
    }

    public function createTestOrderAction()
    {

        $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());
        $product = Mage::getModel('catalog/product')->load(3938); /* 6 => Some product ID */
        $buyInfo = array('qty' => 1);
        //$quote->addProduct($product, new Varien_Object($buyInfo));
        $quote->addProduct($product, new Varien_Object($buyInfo))->setOriginalCustomPrice(5);
        $billingAddress = array(
            'firstname' => 'Manoj',
            'lastname' => 'Kumar',
            'company' => 'Test',
            'email' => 'smartman348_33@live.com',
            'street' => array(
                '1295 Charleston Road'
            ),
            'city' => 'City',
            'region_id' => '12',
            'region' => 'State',
            'postcode' => '94043',
            'country_id' => 'US',
            'telephone' => '06063065831',
            'fax' => '123456987',
            'save_in_address_book' => '0',
            'use_for_shipping' => '1',
        );
        $quote->getBillingAddress()
            ->addData($billingAddress);
        $shippingAddress = $quote->getShippingAddress()->addData($billingAddress);


        $shippingAddress->setFreeShipping(false)
            ->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod('checkmo');

        $quote->setCustomerId(14681)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(false)
            ->setCustomerGroupId(4);
        $quote->getPayment()->importData(array('method' => 'checkmo'));
        $quote->save();
        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
    }


    public function getOrderInfoForCybersourceAction()
    {

        $sql = "SELECT * FROM sales_flat_order WHERE increment_id = '100015338';";
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $execute = $write->query($sql);
        $fetch = $execute->fetchAll();
        $count = $execute->rowCount();
        if ($count > 0) {
            $getcusInfo = '';
            foreach ($fetch as $cusinfo) {
                $getcusInfo = $cusinfo;
            }
            echo $customer_id = $getcusInfo['customer_id'] . '<br/>';
            echo $base_grand_total = $getcusInfo['base_grand_total'] . '<br/>';
        }
    }


    public function addProductQuantity($associateProductId)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($associateProductId);
        if ($stockItem->getId() > 0 and $stockItem->getManageStock()) {
            $qty = 1;
            $stockItem->setQty($qty);
            $stockItem->setIsInStock((int)($qty > 0));
            $stockItem->save();
        }
    }

    public function newcreateorder($customerId, $products, $products_price, $getBucketId)
    {

        $bucketId = $getBucketId;

        ini_set("display_errors", "1");
        error_reporting(E_ALL);

        $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());

        $id = $customerId; // get Customer Id
        $customer = Mage::getModel('customer/customer')->load($id);
        $storeId = $customer->getStoreId();


        //Get customer selected styles
        $customers = Mage::getModel("customer/customer")->load($id);
        $customers->setWebsiteId(Mage::app()->getWebsite()->getId());
        $tmstyle = $customers->getTmstyle();
        $tmcolor = $customers->getTmcolor();
        $tmshirtpolo = $customers->getTmshirtpolo();
        $tmouterwear = $customers->getTmouterwear();
        $tmpantshort = $customers->getTmpantshort();
        $tmshoe = $customers->getTmshoe();
        $tmbelt = $customers->getTmbelte();
        $tmhat = $customers->getTmhat();

        $getPrice = 0;


        foreach ($products as $productId) {


            $productChk = Mage::getModel('catalog/product')->load($productId);
            //echo $productChk->getTypeId().'<br/>';

            if ($productChk->getTypeId() == "configurable") {
                //echo $productId.'<br/>';
                //echo 'customerId= '.$customerId.'<br/>';
                $product = Mage::getModel('catalog/product')->load($productId);
                // Collect options applicable to the configurable product
                $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                $attributeOptions = array();
                foreach ($productAttributeOptions as $productAttribute) {
                    foreach ($productAttribute['values'] as $attribute) {
                        $attributeOptions[$productAttribute['label']][$attribute['value_index']] = $attribute['store_label'];
                    }
                }

                $optionsSet = $attributeOptions['Size'];
                //print_r($optionsSet);
                $getAttributeOptions = $this->reIndex(0, $optionsSet);
                //print_r($getAttributeOptions);

                $read = Mage::getSingleton('core/resource')->getConnection('core_read');
                //$bSelect = $read->select()->from('awesome_product_info', array('*'))->where('enable=?', '1');
                $bucketData = $read->fetchAll("select * from awesome_product_info where bucket_id = '$bucketId' AND product_id='$productId' limit 1");

                $_product = $product;
                $_configurable_model = Mage::getModel('catalog/product_type_configurable');
                $_child_products = $_configurable_model->getUsedProducts(null, $_product);
                $countP = 0;


                foreach ($_child_products as $simple_product) {

                    if ($bucketData[0]['type'] == 'shirts') {
                        //echo $bucketData[0]['type'].'<br/>';
                        if ($tmshirtpolo == $getAttributeOptions[$countP]) {
                            $associateProductId = $simple_product->getId();
                            $this->addProductQuantity($associateProductId);

                            $product_conf = Mage::getModel('catalog/product')->load($associateProductId);
                            $buyInfo = array('qty' => 1);
                            $quote->addProduct($product_conf, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);
                        }

                    } elseif ($bucketData[0]['type'] == 'outerwear') {
                        //echo $bucketData[0]['type'].'<br/>';
                        if ($tmouterwear == $getAttributeOptions[$countP]) {

                            $associateProductId = $simple_product->getId();
                            $this->addProductQuantity($associateProductId);
                            $product_conf = Mage::getModel('catalog/product')->load($associateProductId);
                            $buyInfo = array('qty' => 1);
                            $quote->addProduct($product_conf, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);
                        }

                    } elseif ($bucketData[0]['type'] == 'bottoms') {

                        if ($tmpantshort == $getAttributeOptions[$countP]) {
                            //echo $productId.'<br/>';
                            //echo $bucketData[0]['type'] .'=='. 'bottoms'.'<br/>';
                            $associateProductId = $simple_product->getId();
                            $this->addProductQuantity($associateProductId);
                            //echo 'bottom'.$associateProductId.'<br/>';
                            $product_conf = Mage::getModel('catalog/product')->load($associateProductId);
                            $buyInfo = array('qty' => 1);
                            $quote->addProduct($product_conf, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);
                        }

                    } elseif ($bucketData[0]['type'] == 'shoes') {

                        if ($tmshoe == $getAttributeOptions[$countP]) {
                            //echo $productId.'<br/>';
                            //echo $bucketData[0]['type'] .'=='. 'shoes'.'<br/>';
                            $associateProductId = $simple_product->getId();
                            $this->addProductQuantity($associateProductId);
                            //echo 'shoes'.$associateProductId.'<br/>';
                            $product_conf = Mage::getModel('catalog/product')->load($associateProductId);
                            $buyInfo = array('qty' => 1);
                            $quote->addProduct($product_conf, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);
                        }

                    } elseif ($bucketData[0]['type'] == 'belt') {

                        if ($tmbelt == $getAttributeOptions[$countP]) {
                            //echo $productId.'<br/>';
                            //echo $bucketData[0]['type'] .'=='. 'belt';
                            $associateProductId = $simple_product->getId();
                            $this->addProductQuantity($associateProductId);
                            //echo 'belt'.$associateProductId.'<br/>';
                            $product_conf = Mage::getModel('catalog/product')->load($associateProductId);
                            $buyInfo = array('qty' => 1);
                            $quote->addProduct($product_conf, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);
                        }

                    } elseif ($bucketData[0]['type'] == 'hat') {

                        if ($tmhat == $getAttributeOptions[$countP]) {

                            $associateProductId = $simple_product->getId();
                            $this->addProductQuantity($associateProductId);
                            //echo 'hat'.$associateProductId.'<br/>';
                            $product_conf = Mage::getModel('catalog/product')->load($associateProductId);
                            $buyInfo = array('qty' => 1);
                            $quote->addProduct($product_conf, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);
                        }

                    }

                    $countP++;
                }


            }

            if ($productChk->getTypeId() == "simple") {
                //echo 'simple='.$productId.'<br/>';
                $this->addProductQuantity($productId);
                $product_sim = Mage::getModel('catalog/product')->load($productId);
                $buyInfo = array('qty' => 1);
                $quote->addProduct($product_sim, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);
            }


            $getPrice++;

        }


        $billing = $customer->getDefaultBillingAddress();
        $shipping = $customer->getDefaultShippingAddress();

        $customerid = $id;
        $visitorData = Mage::getModel('customer/customer')->load($customerid);
        $billingaddress = Mage::getModel('customer/address')->load($visitorData->default_billing);
        $addressdata = $billingaddress->getData();


        if ($billing->getCountryId() == '' || $billing->getCountryId() == 0) {
            $billingCountryId = 'US';
        } else {
            $billingCountryId = $billing->getCountryId();
        }

        if ($shipping->getCountryId() == '' || $shipping->getCountryId() == 0) {
            $shippingCountryId = 'US';
        } else {
            $shippingCountryId = $shipping->getCountryId();

        }

        if ($billing->getRegionId() == '' || $billing->getRegionId() == 0) {
            $billingRegionId = '12';
        } else {
            $billingRegionId = $billing->getRegionId();
        }


        if ($shipping->getRegionId() == '' || $shipping->getRegionId() == 0) {
            $shippingRegionId = '12';
        } else {
            $shippingRegionId = $shipping->getRegionId();
        }

        $BillingAddressData = array(
            'firstname' => $billing->getFirstname(),
            'lastname' => $billing->getLastname(),
            'street' => $billing->getStreet(),
            'city' => $billing->getCity(),
            'postcode' => $billing->getPostcode(),
            'telephone' => '45445455',
            'country_id' => $billingCountryId,
            'region_id' => $billingRegionId, // id from directory_country_region table
        );

        $shippingAddressData = array(
            'firstname' => $shipping->getFirstname(),
            'lastname' => $shipping->getLastname(),
            'street' => $shipping->getStreet(),
            'city' => $shipping->getCity(),
            'postcode' => $shipping->getPostcode(),
            'telephone' => '4545454',
            'country_id' => $shippingCountryId,
            'region_id' => $shippingRegionId, // id from directory_country_region table
        );

        $quote->getBillingAddress()->addData($BillingAddressData);
        $shippingAddress = $quote->getShippingAddress()->addData($shippingAddressData);


        $shippingAddress->setFreeShipping(true)
            ->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod('checkmo');

        $quote->setCustomerId($id)
            ->setCustomerName($shipping->getFirstname())
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomer_email($customer->getEmail())
            ->setCustomerFirstname($customer->getFirstname())
            ->setCustomerLastname($customer->getLastname())
            ->setCustomerGroupId($customer->getGroupId())
            ->setStoreId($storeId);


        $quote->getPayment()->importData(array('method' => 'checkmo'));
        $quote->collectTotals()->save();

        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();

        echo 'Order ID: ' . $order->getIncrementId() . '<br/>';


        $reservedOrderId = $order->getIncrementId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($reservedOrderId);
        if ($order->getId()) {
            $productsId = implode(',', $products);
            $this->saveAwesomeMailInfo($customerId, $productsId);
            $config = Mage::getConfig()->getResourceConnectionConfig("default_setup");
            //Config
            $servername = $config->host;
            $username = $config->username;
            $password = $config->password;
            $dbname = $config->dbname;
            $conn = new mysqli($servername, $username, $password, $dbname);
            $thisMonth = date('m-Y');
            //save customer who get a order for this month
            $SubscriptionSql = "INSERT INTO awesome_customer_order (id, order_month, customer_id) VALUES ('','$thisMonth','$customerId')";
            $conn->query($SubscriptionSql);
        }

        $this->changeBucketOrderStatus($order->getIncrementId());


    }

    public function reIndex($start, $array)
    {
        /*** the end number of keys minus one ***/
        $end = ($start + count($array)) - 1;

        /*** the range of numbers to use as keys ***/
        $keys = range($start, $end);

        /*** combine the arrays with the new keys and values ***/
        return array_combine($keys, $array);
    }

    public function Original_newcreateorder($customerId, $products, $products_price, $getBucketId)
    {


        ini_set("display_errors", "1");
        error_reporting(E_ALL);

        $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());

        $id = $customerId; // get Customer Id
        $customer = Mage::getModel('customer/customer')->load($id);
        $storeId = $customer->getStoreId();


        $getPrice = 0;
        foreach ($products as $productId) {


            //increase stock
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            if ($stockItem->getId() > 0 and $stockItem->getManageStock()) {
                $qty = 1;
                $stockItem->setQty($qty);
                $stockItem->setIsInStock((int)($qty > 0));
                $stockItem->save();
            }

            $product = Mage::getModel('catalog/product')->load($productId);

            $buyInfo = array(
                'qty' => 1,
            );
            $quote->addProduct($product, new Varien_Object($buyInfo))->setOriginalCustomPrice($products_price[$getPrice]);

            $getPrice++;
        }


        $billing = $customer->getDefaultBillingAddress();
        $shipping = $customer->getDefaultShippingAddress();

        $customerid = $id;
        $visitorData = Mage::getModel('customer/customer')->load($customerid);
        $billingaddress = Mage::getModel('customer/address')->load($visitorData->default_billing);
        $addressdata = $billingaddress->getData();

        if ($billing->getCountryId() == '' || $billing->getCountryId() == 0) {
            $billingCountryId = 'US';
        } else {
            $billingCountryId = $billing->getCountryId();
        }

        if ($shipping->getCountryId() == '' || $shipping->getCountryId() == 0) {
            $shippingCountryId = 'US';
        } else {
            $shippingCountryId = $shipping->getCountryId();

        }

        if ($billing->getRegionId() == '' || $billing->getRegionId() == 0) {
            $billingRegionId = '12';
        } else {
            $billingRegionId = $billing->getRegionId();
        }


        if ($shipping->getRegionId() == '' || $shipping->getRegionId() == 0) {
            $shippingRegionId = '12';
        } else {
            $shippingRegionId = $shipping->getRegionId();
        }

        $BillingAddressData = array(
            'firstname' => $billing->getFirstname(),
            'lastname' => $billing->getLastname(),
            'street' => $billing->getStreet(),
            'city' => $billing->getCity(),
            'postcode' => $billing->getPostcode(),
            'telephone' => '45445455',
            'country_id' => $billingCountryId,
            'region_id' => $billingRegionId, // id from directory_country_region table
        );

        $shippingAddressData = array(
            'firstname' => $shipping->getFirstname(),
            'lastname' => $shipping->getLastname(),
            'street' => $shipping->getStreet(),
            'city' => $shipping->getCity(),
            'postcode' => $shipping->getPostcode(),
            'telephone' => '4545454',
            'country_id' => $shippingCountryId,
            'region_id' => $shippingRegionId, // id from directory_country_region table
        );

        $quote->getBillingAddress()->addData($BillingAddressData);
        $shippingAddress = $quote->getShippingAddress()->addData($shippingAddressData);


        $shippingAddress->setFreeShipping(true)
            ->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod('checkmo');

        $quote->setCustomerId($id)
            ->setCustomerName($shipping->getFirstname())
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomer_email($customer->getEmail())
            ->setCustomerFirstname($customer->getFirstname())
            ->setCustomerLastname($customer->getLastname())
            ->setCustomerGroupId($customer->getGroupId())
            ->setStoreId($storeId);


        $quote->getPayment()->importData(array('method' => 'checkmo'));
        $quote->collectTotals()->save();

        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $order = $service->getOrder();

        echo 'Order ID: ' . $order->getIncrementId() . '<br/>';
        $this->changeBucketOrderStatus($order->getIncrementId());

        //die();
    }

    public function bucketprocessorAction()
    {

        $config = Mage::getConfig()->getResourceConnectionConfig("default_setup");
        //Config
        $servername = $config->host;
        $username = $config->username;
        $password = $config->password;
        $dbname = $config->dbname;
        $conn = new mysqli($servername, $username, $password, $dbname);

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $bSelect = $read->select()
            ->from('awesome_new_bucket', array('*'))
            ->where('enable=?', '1');

        //Fetch all bucket which is enable
        $bucketData = $read->fetchAll($bSelect);


        //Get that bucket one by one
        foreach ($bucketData as $bucket) {

            //print_r($bucket);
            //Get bucket style,color,id
            $bucketId = $bucket['id'];
            $bucketStyle = $bucket['signup_style'];
            $bucketColor = $bucket['signup_color'];
            $signup_package_price = $bucket['signup_package_price'];

            //Get rules by bucket id
            $rSelect = $read->select()
                ->from('awesome_new_rule', array('*'))
                ->where('bucket_id=?', $bucketId);

            $ruleData = $read->fetchAll($rSelect);


            //Get that rules one by one which status is 0
            foreach ($ruleData as $rule) {

                $ruleStatus = $rule['rule_status'];

                if ($ruleStatus == '0' || $ruleStatus == '') {

                    $ruleDate = $rule['date_from'];
                    $originRuleDate = strtotime($ruleDate);
                    date_default_timezone_set('America/Los_Angeles');
                    $today = strtotime(date('Y-m-d'));

                    //echo $ruleDate .'=='. date('Y-m-d').'<br/>';
                    if ($ruleDate == date('m-Y')) {

                        $productsId = $rule['products_id'];
                        $productId = explode(',', $productsId);
                        $getProduct_price = $rule['products_price'];
                        $getBucketId = $rule['bucket_id'];

                        $products_price = explode(',', $getProduct_price);
                        $get_signup_package_price = '';
                        if ($signup_package_price == '75') {
                            $get_signup_package_price = "Package A $" . $signup_package_price . "/month";
                        } else {
                            $get_signup_package_price = "Package B $" . $signup_package_price . "/month";
                        }

                        $customerData = Mage::getModel('customer/customer')
                            ->getCollection()
                            ->addAttributeToSelect('*')
                            ->addFieldToFilter('group_id', 4)
                            ->addFieldToFilter('tmstyle', $bucketStyle)
                            ->addFieldToFilter('tmcolor', $bucketColor)
                            ->addFieldToFilter('tmpackage', $get_signup_package_price);

//                        echo "<pre>";
//                        print_r($customerData);
//                        echo $productId.'<br/>';
//                        echo $getProduct_price.'<br/>';

//                        die();


                        foreach ($customerData as $customer) {

                            $customerId = $customer['entity_id'];

                            $thisMonth = date('m-Y');
                            //save customer who get a order for this month
                            $SubscriptionSql = "select * from awesome_customer_order where order_month='$thisMonth' and customer_id='$customerId'";
                            $info = $conn->query($SubscriptionSql);
                            if ($info->num_rows == 0) {
                                $this->newcreateorder($customerId, $productId, $products_price, $getBucketId);
                            }
                        }

                        $__fields = array();
                        $__fields['rule_status'] = '1';
                        $__where = $write->quoteInto('date_from=?', $ruleDate);
                        $write->update('awesome_new_rule', $__fields, $__where);

                    } else {
                        echo 'No rule found to process.<br/>';
                    }

                } else {
                    echo 'No rule found to process.<br/>';
                }
            }
        }
        $write->commit();
    }

    public function changeBucketOrderStatus($orderId)
    {
        try {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $order->setData('state', "complete");
            $order->setStatus("subscription_complete");
            $order->save();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /*
     * ------------------------------------
     * Create recurring payment profile
     * Uses:
     * $createPaymentProfile = $this->createSubscription('873847348',10, 'Golden Package', 5, 'months', '2015-03-18', 5, 1, 0, 5105105105105100, '2016-03', 'jafor', 'iqbal');
     * print_r($createPaymentProfile);
     * ------------------------------------
     */

    public function createSubscription($refId, $amount, $name, $length, $unit, $startDate, $totalOccurrences, $trialOccurrences, $trialAmount, $cardNumber, $expirationDate, $firstName, $lastName)
    {


        //build xml to post
        $content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
            "<ARBCreateSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
            "<merchantAuthentication>" .
            "<name>" . $this->loginname . "</name>" .
            "<transactionKey>" . $this->transactionkey . "</transactionKey>" .
            "</merchantAuthentication>" .
            "<refId>" . $refId . "</refId>" .
            "<subscription>" .
            "<name>" . $name . "</name>" .
            "<paymentSchedule>" .
            "<interval>" .
            "<length>" . $length . "</length>" .
            "<unit>" . $unit . "</unit>" .
            "</interval>" .
            "<startDate>" . $startDate . "</startDate>" .
            "<totalOccurrences>" . $totalOccurrences . "</totalOccurrences>" .
            "<trialOccurrences>" . $trialOccurrences . "</trialOccurrences>" .
            "</paymentSchedule>" .
            "<amount>" . $amount . "</amount>" .
            "<trialAmount>" . $trialAmount . "</trialAmount>" .
            "<payment>" .
            "<creditCard>" .
            "<cardNumber>" . $cardNumber . "</cardNumber>" .
            "<expirationDate>" . $expirationDate . "</expirationDate>" .
            "</creditCard>" .
            "</payment>" .
            "<billTo>" .
            "<firstName>" . $firstName . "</firstName>" .
            "<lastName>" . $lastName . "</lastName>" .
            "</billTo>" .
            "</subscription>" .
            "</ARBCreateSubscriptionRequest>";


        //send the xml via curl
        $response = $this->send_request_via_curl($this->host, $this->path, $content);

        // if curl is unavilable you can try using fsockopen
        // $response = send_request_via_fsockopen($host,$path,$content);
        //if the connection and send worked $response holds the return from Authorize.net
        if ($response) {

            list ($refId, $resultCode, $code, $text, $subscriptionId) = $this->parse_return($response);

            $resultSetData = array(
                'response_code' => $resultCode,
                'response_reason_code' => $code,
                'response_text' => $text,
                'reference_id' => $refId,
                'subscription_id' => $subscriptionId,
                'loginname' => $this->loginname,
                'amount' => $amount
            );

            return $resultSetData;
        } else {
            $resultSetData = array(
                'response_code' => 'ERROR'
            );

            return $resultSetData;
        }
    }

    /*
     * ------------------------------------
     * Cancel recurring payment profile
     * ------------------------------------
     */

    public function oldcancelSubscription($subscriptionId)
    {

        //build xml to post
        $content = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
            "<ARBCancelSubscriptionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
            "<merchantAuthentication>" .
            "<name>" . $this->loginname . "</name>" .
            "<transactionKey>" . $this->transactionkey . "</transactionKey>" .
            "</merchantAuthentication>" .
            "<subscriptionId>" . $subscriptionId . "</subscriptionId>" .
            "</ARBCancelSubscriptionRequest>";

        //send the xml via curl
        $response = $this->send_request_via_curl($this->host, $this->path, $content);

        //if curl is unavilable you can try using fsockopen
        //$response = send_request_via_fsockopen($host,$path,$content);
        //if the connection and send worked $response holds the return from Authorize.net
        if ($response) {
            list ($resultCode, $code, $text, $subscriptionId) = parse_return($response);

            echo " Response Code: $resultCode <br>";
            echo " Response Reason Code: $code<br>";
            echo " Response Text: $text<br>";
            echo " Subscription Id: $subscriptionId <br><br>";
            echo " Data has been written to data.log<br><br>";
        } else {
            echo "Transaction Failed. <br>";
        }
    }

    /*
     * ------------------------------------
     * Payment integration methods
     * ------------------------------------
     */

    function send_request_via_fsockopen($host, $path, $content)
    {
        $posturl = "ssl://" . $host;
        $header = "Host: $host\r\n";
        $header .= "User-Agent: PHP Script\r\n";
        $header .= "Content-Type: text/xml\r\n";
        $header .= "Content-Length: " . strlen($content) . "\r\n";
        $header .= "Connection: close\r\n\r\n";
        $fp = fsockopen($posturl, 443, $errno, $errstr, 30);
        if (!$fp) {
            $response = false;
        } else {
            error_reporting(E_ERROR);
            fputs($fp, "POST $path  HTTP/1.1\r\n");
            fputs($fp, $header . $content);
            fwrite($fp, $out);
            $response = "";
            while (!feof($fp)) {
                $response = $response . fgets($fp, 128);
            }
            fclose($fp);
            error_reporting(E_ALL ^ E_NOTICE);
        }
        return $response;
    }

    /*
     * --------------------------------------
     * function to send xml request via curl
     * --------------------------------------
     */

    function send_request_via_curl($host, $path, $content)
    {
        $posturl = "https://" . $host . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $posturl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        return $response;
    }

    /*
     * -------------------------------------------
     * function to parse Authorize.net response
     * -------------------------------------------
     */

    function parse_return($content)
    {
        $refId = $this->substring_between($content, '<refId>', '</refId>');
        $resultCode = $this->substring_between($content, '<resultCode>', '</resultCode>');
        $code = $this->substring_between($content, '<code>', '</code>');
        $text = $this->substring_between($content, '<text>', '</text>');
        $subscriptionId = $this->substring_between($content, '<subscriptionId>', '</subscriptionId>');
        return array($refId, $resultCode, $code, $text, $subscriptionId);
    }

    /*
     * -------------------------------------------
     * helper function for parsing response
     * -------------------------------------------
     */

    function substring_between($haystack, $start, $end)
    {
        if (strpos($haystack, $start) === false || strpos($haystack, $end) === false) {
            return false;
        } else {
            $start_position = strpos($haystack, $start) + strlen($start);
            $end_position = strpos($haystack, $end);
            return substr($haystack, $start_position, $end_position - $start_position);
        }
    }

    //////////////////////////////////// This is for cybersource ////////////////////////////////////////
    public function addsubscriptionAction()
    {
        $helper = Mage::helper('awesomebox');
        $helper->addSubscription('Belt');

        $result = array(
            'card_number' => '4111111111111111',
            'card_month' => '12',
            'card_year' => '2020',
            'card_type' => 'visa',
            'card_csc' => '666',
            'billTo_firstName' => 'John',
            'billTo_lastName' => 'Doe',
            'billTo_street1' => '1295 Charleston Road',
            'billTo_city' => 'Mountain View',
            'billTo_state' => 'CA',
            'billTo_postalCode' => '94043',
            'billTo_country' => 'US',
            'billTo_email' => 'null@*****.com',
        );

        print_r($result);
    }

    // Function to get the client IP address
    public function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }


    private function saveAwesomeMailInfo($customerId, $productId)
    {

        $config = Mage::getConfig()->getResourceConnectionConfig("default_setup");
        //Config
        $servername = $config->host;
        $username = $config->username;
        $password = $config->password;
        $dbname = $config->dbname;
        $date = date('m-Y');

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        //Insert data into awesome mail
        $awesomeMailSql = "INSERT INTO awesome_mail (id, customer_id, product_ids, create_date, status)  VALUES ('',$customerId,'" . $productId . "','" . $date . "',1)";
        $conn->query($awesomeMailSql);
    }


    public function awesomeBoxSendBulkMailAction()
    {

        ini_set("display_errors", "1");
        error_reporting(E_ALL);

        $dataReader = Mage::getSingleton('core/resource')->getConnection('core_read');

        $mailSelect = $dataReader->select()
            ->from('awesome_mail', array('*'))
            ->where('status=?', '1');

        $mailData = $dataReader->fetchAll($mailSelect);

        foreach ($mailData as $mail) {
            $customer_id = $mail['customer_id'];
            $product_ids = explode(',', $mail['product_ids']);
            $customerData = Mage::getModel('customer/customer')->load($customer_id)->getData();
            $to = $customerData['email'];
            $name = "AwesomeBox";
            $subject = 'Awesome Order';

            $content = $this->awesomeBoxOrderMail($product_ids);
            $from = "onlineorders@travismathew.com";
            $this->sendAwesomeMail($to, $from, $subject, $name, $content);


        }
    }

    public function sendMeMailTmAction()
    {
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $bSelect = $read->select()
            ->from('awesome_new_rule', array('*'))
            ->where('bucket_id=?', '50');

        //Fetch all bucket which is enable
        $bucketData = $read->fetchAll($bSelect);
        $to = 'setadminemail@gmail.com';
        $name = "AwesomeBox";
        $subject = 'Awesome Registration Confirmation';

        $content = $this->awesomeBoxSignUpMail(12837455495);
        $from = $this->adminEmail;
        $this->sendAwesomeMail($to, $from, $subject, $name, $content);
    }

    public function sendAwesomeMail($to, $from, $subject, $name, $content)
    {
        //Create a new PHPMailer instance
        $mail = new PHPMailer;
        // Set PHPMailer to use the sendmail transport
        $mail->isSendmail();
        //Set who the message is to be sent from
        $mail->setFrom($from, $this->companyEmail);
        //Set an alternative reply-to address
        $mail->addReplyTo($this->replyto, '');
        //Set who the message is to be sent to
        $mail->addAddress($to, $name);
        //Set the subject line
        $mail->Subject = $subject;
        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body
        //$mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
        $mail->Body = $content;
        //Replace the plain text body with one created manually
        $mail->AltBody = '';
        //Attach an image file
        //$mail->addAttachment('images/phpmailer_mini.png');
        //send the message, check for errors
        if (!$mail->send()) {
            return 0;
        } else {
            return 1;
        }
    }


    private function awesomeBoxSignUpMail($orderNumber)
    {

        $string = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title>Travis Mathew</title>
                    </head>
                    <body style="margin: 0; padding: 0;" marginheight="0" marginwidth="0" topmargin="0" bgcolor="#ffffff">

                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">

                            <tr>
                              <td bgcolor="#ffffff" cellpadding="0" cellspacing="0">
                                <table style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;" width="650" border="0" align="center" cellpadding="0" cellspacing="0">

                                  <tr>
                                    <td><img src="http://i.travismathew.com/15/abs_02.gif" alt="TravisMathew - BeAwesome Box - Subscription" width="650" height="80" title="TravisMathew - BeAwesome Box - Subscription" border="0" style="display: block;" /></td>
                                    </tr>

                                  <tr>
                                    <td><img src="http://i.travismathew.com/15/abs_04.jpg" alt="Welcome to the Travismathew BeAwesome Box!" width="650" height="319" title="Welcome to the Travismathew BeAwesome Box!" border="0" style="display: block;" /></td>
                                  </tr>

                                  <tr>
                                    <td><img src="http://i.travismathew.com/15/abs_05.gif" alt="BeAwesome Box Order Confirmation" width="650" height="70" title="BeAwesome Box Order Confirmation" border="0" style="display: block;" /></td>
                                    </tr>

                                  <tr>
                                    <td><img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="650" height="3" border="0" style="display: block;" /></td>
                                    </tr>

                                  <tr>
                                    <td>
                                      <table width="580" border="0" align="center" cellpadding="0" cellspacing="0">
                                        <tr>
                                          <td><p style="margin: 0 0px 25px 0px; padding: 0px; text-align: left; line-height: 24px;"><font face="Lato, Trebuchet MS, Arial, Helvetica, sans-serif" size="0" color="#666666" style="font-family: Lato, ' . 'Trebuchet MS' . ', Arial, Helvetica, sans-serif; font-size: 13px; line-height: 24px; color: #727272; text-align: left;">Thank you for joining TravisMathew’s BeAwesome Box. We’ll go ahead and let you know you’ve made
                                            a great decision. Every TravisMathew BeAwesome Box shipment is hand-picked to match your preferences. Never worry about needing to get that new TravisMathew polo you know you’ll want. We’ll get it to you before you can ask!
                                            <br /><br />
                                                Your order confirmation number is (' . $orderNumber . ') & look for an email annoucing your first shipment on (' . date('Y-m-d') . ').</font></p></td>
                                          </tr>
                                        </table>
                                      </td>
                                    </tr>

                                  <tr>
                                    <td align="center">
                                      <a href="' . Mage::getBaseUrl() . 'customer/account/login/"><img src="http://i.travismathew.com/15/abs_08.gif" alt="Account Login" width="126" height="29" title="Account Login" border="0" /></a>
                                      <img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="TravisMathew" width="15" height="29" title="TravisMathew" border="0" />
                                      <a href="' . Mage::getBaseUrl() . 'contact/"><img src="http://i.travismathew.com/15/abs_10.gif" alt="Contact Us" width="126" height="29" title="Contact Us" border="0" /></a>
                                      </td>
                                    </tr>

                                  <tr>
                                    <td><img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="650" height="35" border="0" style="display: block;" /></td>
                                  </tr>

                                  <tr>
                                    <td><img src="http://i.travismathew.com/15/abs_14.gif" alt="TravisMathew" width="650" height="56" title="TravisMathew" usemap="#socialMap" border="0" style="display: block;" /></td>
                                  </tr>

                                  <tr>
                                    <td><p style="margin: 15px 0px 30px 0px; padding: 0px 0px 0px 0px; text-align: center;"><font face="Lato, Trebuchet MS, Arial, Helvetica, sans-serif" size="0" color="#828282" style="font-family: Lato, ' . 'Trebuchet MS' . ', Arial, Helvetica, sans-serif; font-size: 11px; line-height: 18px; color: #828282; text-align: center;">You are receiving this email because you signed up to the TravisMathew mailing list.<br />If you would like to unsubscribe, please &nbsp;<a href="*|UNSUB|*" style="color: #828282">click here</a></font><font face="Lato, Trebuchet MS, Arial, Helvetica, sans-serif" size="0" color="#828282" style="font-family: Lato, ' . 'Trebuchet MS' . ', Arial, Helvetica, sans-serif; font-size: 11px; line-height: 18px; color: #828282; text-align: center;">.</font></p></td>
                                  </tr>

                                  <tr align="center">
                                    <td bgcolor="#ccc"><img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="650" height="1" border="0" style="display: block;" /></td>
                                    </tr>

                                  </table>
                                </td>
                            </tr>

                            </table>

                                <map name="socialMap" id="socialMap">
                                <area shape="rect" coords="11,9,147,47" href="http://www.travismathew.com/" target="_blank" title="TravisMathew" alt="TravisMathew" />
                                <area shape="circle" coords="502,28,10" href="https://www.facebook.com/travismathew" target="_blank" alt="Facebook" />
                                <area shape="circle" coords="594,27,9" href="https://twitter.com/TRAVISMATHEW" target="_blank" alt="Twitter" />
                                <area shape="circle" coords="563,28,10" href="https://www.youtube.com/user/TheTRAVISMATHEW" target="_blank" alt="Instagram" />
                                <area shape="circle" coords="531,28,10" href="http://instagram.com/travismathew" target="_blank" alt="Instagram" />
                                </map>

                                <map name="MainNav" id="MainNav">
                                <area shape="rect" coords="99,80,140,102" href="http://www.travismathew.com/shop" target="_blank" title="Shop" alt="Shop" />
                                <area shape="rect" coords="166,80,216,102" href="http://www.travismathew.com/news/" target="_blank" title="News" alt="News" />
                                <area shape="rect" coords="234,82,291,103" href="http://www.travismathew.com/video/" target="_blank" title="Video" alt="Video" />
                                <area shape="rect" coords="318,79,386,102" href="http://www.travismathew.com/lookbook/" target="_blank" title="Lookbook" alt="Lookbook" />
                                <area shape="rect" coords="419,80,460,104" href="http://www.travismathew.com/team/" target="_blank" title="Team" alt="Team" />
                                <area shape="rect" coords="490,79,554,104" href="http://www.travismathew.com/discover/" target="_blank" title="Discover" alt="Discover" />
                                <area shape="rect" coords="184,23,458,67" href="http://www.travismathew.com/" target="_blank" title="TravisMathew" alt="TravisMathew" />
                                </map>

                    </body>
                    </html>';

        return $string;

    }


    private function awesomeBoxOrderMail($products)
    {


        $string = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title>Travis Mathew</title>
                    </head>
                    <body style="margin: 0; padding: 0;" marginheight="0" marginwidth="0" topmargin="0" bgcolor="#ffffff">

                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;">

                                    <tr>
                                      <td bgcolor="#ffffff" cellpadding="0" cellspacing="0">
                                        <table style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;" width="650" border="0" align="center" cellpadding="0" cellspacing="0">

                                          <tr>
                                            <td><img src="http://i.travismathew.com/15/abo_02.gif" alt="TravisMathew - BeAwesome Box - Subscription" width="650" height="84" title="TravisMathew - BeAwesome Box - Subscription" border="0" style="display: block;" /></td>
                                            </tr>

                                          <tr>
                                            <td><img src="http://i.travismathew.com/15/abo_04.jpg" alt="Upcoming - Arriving at your this month" width="650" height="315" title="Upcoming - Arriving at your this month" border="0" style="display: block;" /></td>
                                          </tr>

                                          <tr>
                                            <td><img src="http://i.travismathew.com/15/abs_05.gif" alt="BeAwesome Box Order Confirmation" width="650" height="70" title="BeAwesome Box Order Confirmation" border="0" style="display: block;" /></td>
                                            </tr>

                                          <tr>
                                            <td><img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="650" height="3" border="0" style="display: block;" /></td>
                                            </tr>

                                          <tr>
                                            <td>
                                              <table width="580" border="0" align="center" cellpadding="0" cellspacing="0">
                                                <tr>
                                                  <td><p style="margin: 0 0px 35px 0px; padding: 0px; text-align: left; line-height: 24px;"><font face="Lato, Trebuchet MS, Arial, Helvetica, sans-serif" size="0" color="#666666" style="font-family: Lato, ' . 'Trebuchet MS' . ', Arial, Helvetica, sans-serif; font-size: 13px; line-height: 24px; color: #727272; text-align: left;">It’s that time of the month. No not that time, but the time we announce your monthly shipment from TravisMathew’s BeAwesome Box. Below is what will be arriving at your door next week. If you would like to make any changes please contact us below.</font></p></td>
                                                  </tr>
                                                </table>
                                              </td>
                                            </tr>

                                          <tr>
                                            <td>
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>';


        foreach ($products as $theProduct) {
            $_product = Mage::getModel('catalog/product')->load($theProduct);
            $imageUrl = Mage::getModel('catalog/product_media_config')->getMediaUrl($_product->getThumbnail());

            $string .= '<td><img src="http://i.travismathew.com/15/abo_07.gif" alt="" width="55" height="218" border="0" style="display: block;" /></td>
                                                              <td>
                                                              <img src="' . $imageUrl . '" alt="TravisMathew" width="144" height="218" title="TravisMathew" border="0" style="display: block;" />
                                                              <a style="color:#727272; text-decoration:none;" href="' . $_product->getProductUrl() . '">' . $_product->getName() . '</a>
                                                              </td>';
        }

        $string .= '</tr>
                                                </table>
                                            </td>
                                          </tr>

                                          <tr>
                                            <td><img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="650" height="40" border="0" style="display: block;" /></td>
                                          </tr>

                                          <tr>
                                            <td align="center">
                                              <a href="' . Mage::getBaseUrl() . 'customer/account/login/"><img src="http://i.travismathew.com/15/abs_08.gif" alt="Account Login" width="126" height="29" title="Account Login" border="0" /></a>
                                              <img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="15" height="29" border="0" />
                                              <a href="' . Mage::getBaseUrl() . 'contact/"><img src="http://i.travismathew.com/15/abs_10.gif" alt="Contact Us" width="126" height="29" title="Contact Us" border="0" /></a>
                                              </td>
                                            </tr>

                                          <tr>
                                            <td><img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="650" height="35" border="0" style="display: block;" /></td>
                                          </tr>

                                          <tr>
                                            <td><img src="http://i.travismathew.com/15/abs_14.gif" alt="TravisMathew" width="650" height="56" title="TravisMathew" usemap="#socialMap" border="0" style="display: block;" /></td>
                                          </tr>

                                          <tr>
                                            <td><p style="margin: 15px 0px 30px 0px; padding: 0px 0px 0px 0px; text-align: center;"><font face="Lato, Trebuchet MS, Arial, Helvetica, sans-serif" size="0" color="#828282" style="font-family: Lato, ' . 'Trebuchet MS' . ', Arial, Helvetica, sans-serif; font-size: 11px; line-height: 18px; color: #828282; text-align: center;">
                                            You are receiving this email because you signed up to the TravisMathew mailing list.<br />If you would like to unsubscribe, please &nbsp;<a href="*|UNSUB|*" style="color: #828282">click here</a></font><font face="Lato, Trebuchet MS, Arial, Helvetica, sans-serif" size="0" color="#828282" style="font-family: Lato, ' . 'Trebuchet MS' . ', Arial, Helvetica, sans-serif; font-size: 11px; line-height: 18px; color: #828282; text-align: center;">.</font></p></td>
                                          </tr>

                                          <tr align="center">
                                            <td bgcolor="#ccc">
                                            <img src="http://i.travismathew.com/15/template-spacer-15.gif" alt="" width="650" height="1" border="0" style="display: block;" /></td>
                                            </tr>

                                          </table>
                                        </td>
                                    </tr>

                            </table>

                            <map name="socialMap" id="socialMap">
                            <area shape="rect" coords="11,9,147,47" href="http://www.travismathew.com/" target="_blank" title="TravisMathew" alt="TravisMathew" />
                            <area shape="circle" coords="502,28,10" href="https://www.facebook.com/travismathew" target="_blank" alt="Facebook" />
                            <area shape="circle" coords="594,27,9" href="https://twitter.com/TRAVISMATHEW" target="_blank" alt="Twitter" />
                            <area shape="circle" coords="563,28,10" href="https://www.youtube.com/user/TheTRAVISMATHEW" target="_blank" alt="Instagram" />
                            <area shape="circle" coords="531,28,10" href="http://instagram.com/travismathew" target="_blank" alt="Instagram" />
                            </map>

                            <map name="MainNav" id="MainNav">
                            <area shape="rect" coords="99,80,140,102" href="http://www.travismathew.com/shop" target="_blank" title="Shop" alt="Shop" />
                            <area shape="rect" coords="166,80,216,102" href="http://www.travismathew.com/news/" target="_blank" title="News" alt="News" />
                            <area shape="rect" coords="234,82,291,103" href="http://www.travismathew.com/video/" target="_blank" title="Video" alt="Video" />
                            <area shape="rect" coords="318,79,386,102" href="http://www.travismathew.com/lookbook/" target="_blank" title="Lookbook" alt="Lookbook" />
                            <area shape="rect" coords="419,80,460,104" href="http://www.travismathew.com/team/" target="_blank" title="Team" alt="Team" />
                            <area shape="rect" coords="490,79,554,104" href="http://www.travismathew.com/discover/" target="_blank" title="Discover" alt="Discover" />
                            <area shape="rect" coords="184,23,458,67" href="http://www.travismathew.com/" target="_blank" title="TravisMathew" alt="TravisMathew" />
                            </map>

                    </body>
                    </html>';

        return $string;

    }

}
