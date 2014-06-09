<?php
/****
 * This class is an abstract that can be used for Page Blocks that are built
 * from a DLM Request.  It includes the AngularJS for an AJAX call back to
 * update the data from based on the original DLM Request.
 */
abstract class WebDLPBFromRequest extends WebDLPBBase {
    
    protected $request;
    protected $result;
    protected $c_list = array();
    protected $m_list = array();
    protected $update_match = false;
    
    public function __construct($unique_id) {
        // Create a DML Request to use.
        $this->request = new WebDLMRequest();
        parent::__construct($unique_id);
    }
    
    // Add a column to the request
    public function push_column($c_id) {
        $this->request->push_column($c_id);
        // Save the list of columns to be used as params in the AJAX update
        $this->c_list[] = $c_id;
    }

    // Add a match value to the request
    public function push_match($c_id, $m_val, $type='AND') {
        $this->request->push_match($c_id, $m_val, $type);
        // Save the match in a list for the AngualarJS AJAX update
        $match = array();
        $match['c_id'] = $c_id;
        $match['m_val'] = $m_val;
        $match['type'] = $type;
        $this->m_list[] = $match;
    }

    // When the desired request has been built, then call this to
    // fetch the data from the DLM(s) and set the HTML.
    public function finish() {
        $this->result = WebDLMController::dlm_request($this->request);
        $this->register_ajax();
        $this->set_html();
    }
    // Sets if the match on item can be changed from the AJAX call back.
    // I may not be very secure turning this option on.
    public function can_update_match() {
        $this->update_match = true;
    }
    
    // Save the request in the session, so that it can be controlled when the
    // update JS function is called.
    protected function register_ajax() {
        $values = array();
        $values['update_match'] = $this->update_match;
        $values['c_list'] = $this->c_list;
        $values['m_list'] = $this->m_list;
        $_SESSION[$this->unique_id.'Ctrl'] = $values;
    }
    
    // Re-run the request and return fresh data
    static public function return_ajax() {
        if (!isset($_REQUEST['ajax_id'])) return WebDLAjax::json_empty_array();
        if (!isset($_SESSION[$_REQUEST['ajax_id']])) return WebDLAjax::json_empty_array();
        
        // Add the columns
        $request = new WebDLMRequest();
        foreach ($_SESSION[$_REQUEST['ajax_id']]['c_list'] as $c_id) {
            $request->push_column($c_id);
        }
        
        // Add the match on values
        if ($_SESSION[$_REQUEST['ajax_id']]['update_match']) {
            $m_list = json_decode($_REQUEST['ajax_matches']);
        } else {
            $m_list = $_SESSION[$_REQUEST['ajax_id']]['m_list'];
        }
        foreach ($m_list as $match) {
            $request->push_match($match->c_id, $match->m_val, $match->type);
        }
        
        // Send the request to the DLM controller
        $result = WebDLMController::dlm_request($request);
        
        // Return the json data
        return  json_encode($result->get_joined_data());
    }
    
    // The AngularJS code that creates the model and ajax call back function.
    // ToDo: What is a way to secure the AJAX call so that it a custom request can't be
    // made to fetch unauthorized data.  An ACL on columns and forced matches could solve
    // it, but that will be a chore to configure.  Right now this function is being used
    // on data that does not require security, but it will need to be made secure.
    // UPDATE: Might have a secure way of making the AJAX call backs, using a session value
    // to store the origial request, and add an option to allow updating on the match.
    protected function get_angularjs() {
        $json = json_encode($this->result->get_joined_data());
        $js = '
            <script>
                function '.$this->unique_id.'Ctrl($scope, $http) {
                    $scope.data = '.$json.';
                    $scope.ajax_id = "'.$this->unique_id.'Ctrl";
                    $scope.ajax_uri = "/webdl.php/ajax/WebDLPBFromRequest/return_ajax/";';
                    // Don't like triming the brackets.  Like to find a better way to get the json data to output
                    // in the $http request below and include the brackets when you reset_matches() and push_matches().
        $js .= '    $scope.ajax_matches = '.trim(json_encode($this->m_list), '[]').';
                    
                    $scope.reset_matches = function () {
                        $scope.ajax_matches = [];
                    }
                    
                    $scope.push_match = function (c_id, m_val, type) {
                        $scope.ajax_matches.push(JSON.stringify({c_id: c_id, m_val: m_val, type: type}));
                    }
                    
                    $scope.update = function () {
                        $http({
                            method: "POST",
                            url: $scope.ajax_uri,
                            data: "ajax_id=" + $scope.ajax_id + "&" + "ajax_matches=[" + $scope.ajax_matches + "]",
                            headers: {"Content-Type": "application/x-www-form-urlencoded"}
                        }).
                        success(function(data, status) {
                            $scope.data = data;
                        }).
                        error(function(data, status) {});
                    }
                    
                }
            </script>
        ';
        return $js;
    }
    
    
    
}
?>