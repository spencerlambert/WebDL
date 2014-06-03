<?php
/**
 * This class helps give you a head start when
 * creating a DLM that works with a SOAP API.
 */

class WebDLMSoap extends WebDLMBase {

    protected $soap;    
 
    public function __construct($dlm_id) {
        // Add configuration items to the list.
        // These must be set in the DLMConfig table, or else...
        $this->config_list[] = "SOAP_URL";
        $this->config_list[] = "SOAP_USER";
        $this->config_list[] = "SOAP_PASS";
        parent::__construct($dlm_id);
    }

    public function is_ready() {
    	try {
            $this->soap = new SoapClient($this->config['SOAP_URL'],  array('login'    => $this->config['SOAP_USER'],
                                                                           'password' => $this->config['SOAP_PASS']));        
        } catch (Exception $e) {
            WebDLUserMessage::output('SOAP Connection failed: ' . $e->getMessage());
            return false;
        }
        return true;
    }
}
?>