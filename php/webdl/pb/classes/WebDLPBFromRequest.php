<?php
/****
 * This class is an abstract that can be used for Page Blocks that are built
 * from a DLM Request.  It includes the AngularJS for an AJAX call back to
 * update the data from based on the original DLM Request.
 */
abstract class WebDLPBFromRequest extends WebDLPBBase {
    
    protected $request;
    protected $result;
    
    public function __construct($unique_id) {
        // Create a DML Request to use.
        $this->request = new WebDLRequest();
        parent::__construct($unique_id);
    }
    
    // Add a column to the request
    public function push_column($c_id) {
        $this->request->push_column($c_id);
    }

    // Add a match value to the request
    public function push_match($c_id, $m_val, $type='AND') {
        $this->request->push_match($c_id, $m_val, $type); 
    }

    // When the desired request has been built, then call this to
    // fetch the data from the DLM(s) and set the HTML.
    public function finish() {
        $this->result = WebDLMController::dlm_request($this->request);
        $this->set_html();
    }

    abstract public function set_html();
    
    // The AngularJS code that creates the model and ajax call back function.
    private function get_angularjs() {
        $js = '
            <script>
                function '.$this->unique_id.'Ctrl($scope) {
                    $scope.data = ;
                    $scope.columns = ;
                    $scope.matches = ;
                    
                    $scope.reset_matches = function () {
                        $scope.matches = [];
                    }
                    
                    $scope.push_match = function (match_json) {
                        $scope.matches.push(match_json);
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