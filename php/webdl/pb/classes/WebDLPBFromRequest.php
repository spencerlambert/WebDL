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
        $m_list[] = $match;
    }

    // When the desired request has been built, then call this to
    // fetch the data from the DLM(s) and set the HTML.
    public function finish() {
        $this->result = WebDLMController::dlm_request($this->request);
        $this->set_html();
    }
    
    // The AngularJS code that creates the model and ajax call back function.
    // ToDo: What is a way to secure the AJAX call so that it a custom request can't be
    // made to fetch unauthorized data.  An ACL on columns and forced matches could solve
    // it, but that will be a chore to configure.  Right now this function is being used
    // on data that does not require security, but it will need to be made secure.
    protected function get_angularjs() {
        $json = json_encode($this->result->get_joined_data());
        $js = '
            <script>
                function '.$this->unique_id.'Ctrl($scope) {
                    $scope.data = '.$json.';
                    $scope.columns = '.json_encode($this->c_list).';
                    $scope.matches = '.json_encode($this->m_list).';
                    
                    $scope.reset_matches = function () {
                        $scope.matches = [];
                    }
                    
                    $scope.push_match = function (c_id, m_val, type) {
                        $scope.matches.push(JSON.stringify({c_id: c_id, m_val: m_val, type: type}));
                    }
                    
                    $scope.update = function () {
                        
                    }
                    
                }
            </script>
        ';
        return $js;
    }
    
    
    
}
?>