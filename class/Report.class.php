<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
require_once ('CURLHandler.php');
require_once('config.php');

class Report{
    
    /*public function getList(){
        $res = $this->db->query("SELECT * FROM cupid_conf WHERE config_key = 'ADMIN_NAME' GROUP BY domain");
        #var_dump($res);
        while($row = mysql_fetch_object($res)){
            $ret[] = $row;
            echo "INSERT INTO customers SET contact_first_name='{$row->data}', created='{$row->created}', domain='{$row->domain}';\n";
        }
        return $ret;
    }*/
    
    public function getList($page=1, $ordering='created', $sort='DESC', $customer='', $date_from='', $date_to='', $status=''){
        #echo SALES_API_URL.'?api_key='. SALES_API_KEY .'&action=getCustomerList&page='.$page.'&ordering='.$ordering.'&sort='.$sort.'&customer='.$customer.'&date_from='.$date_from.'&date_to='.$date_to.'&status='.$status;
        ob_start();
        CURLHandler::Get(SALES_API_URL.'?api_key='. SALES_API_KEY .'&action=getCustomerList&page='.$page.'&ordering='.$ordering.'&sort='.$sort.'&customer='.$customer.'&date_from='.$date_from.'&date_to='.$date_to.'&status='.$status);
        $request = ob_get_contents();
        ob_end_clean();
        $result = json_decode($request);
        #var_dump($result->result);
        return $result->result;
    }
    
    public function getCustomerSelectbox($selected=0){
        $box = '<select name="customer">';
        $box .= '<option value="">-</option>';
        ob_start();
        CURLHandler::Get(SALES_API_URL.'?api_key='. SALES_API_KEY .'&action=getCustomerNames');
        $request = ob_get_contents();
        ob_end_clean();
        $result = json_decode($request);
        foreach($result->result as $item){
            if($item->id == $selected)
                $selected_prop = 'selected="selected"';
                
            $box .= sprintf('<option value="%s" %s>%s (%s %s)</option>', 
                            $item->id,
                            $selected_prop,
                            $item->company_name,
                            $item->contact_first_name, 
                            $item->contact_last_name);
            $selected_prop='';
        }
        $box .= '</select>';
        
        return $box;
    }
    
    public function generateCSVReport($ordering='payment_date', $sort='DESC', $customer='', $start_date='', $end_date='', $status=''){
        $page = 0;
        ob_start();
        CURLHandler::Get(SALES_API_URL.'?api_key='. SALES_API_KEY .'&action=getPaymentList&page='.$page.'&ordering='.$ordering.'&sort='.$sort.'&customer='.$customer.'&date_from='.$date_from.'&date_to='.$date_to.'&status='.$status);
        $request = ob_get_contents();
        ob_end_clean();
        $result = json_decode($request);
        #TODO get the data
        $list = array (
            'Domain,Amount,Date of Payment,Payer,Status,ID',
        );
        #var_dump($result->result);
        foreach($result->result as $line){
            $list[] = sprintf('"%s", "%s", "%s", "%s", "%s", "%s"', 
                                $line->domain, 
                                $line->payment_amount,
                                $line->payment_date, 
                                $line->company_name, 
                                $line->payment_status,
                                $line->paypal_token);
        }
        header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=payments_sales.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        foreach($list as $item)
            print $item."\n";
    }
    
    public function getCustomerInfo($customerId){
        ob_start();
        CURLHandler::Get(SALES_API_URL.'?api_key='. SALES_API_KEY .'&action=getCustomer&customer_id='.$customerId);
        $request = ob_get_contents();
        ob_end_clean();
        $result = json_decode($request);
        return $result->result;
    }
    
    public function getPaymentHistory($customerId){
        ob_start();
        CURLHandler::Get(SALES_API_URL.'?api_key='. SALES_API_KEY .'&action=getPaymentHistory&customer_id='.$customerId);
        $request = ob_get_contents();
        ob_end_clean();
        $result = json_decode($request);
        return $result->result;
    }
    
    public function getLastPaymentInfo($customerId){
        ob_start();
        CURLHandler::Get(SALES_API_URL.'?api_key='. SALES_API_KEY .'&action=getLastPayment&customer_id='.$customerId);
        $request = ob_get_contents();
        ob_end_clean();
        $result = json_decode($request);
        return $result->result;
    }
}

?>