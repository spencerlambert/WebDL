<?php
class WebDLPBFromRequest extends WebDLPBBase {
    
    protected $request;
    protected $result;
    
    public function __construct($unique_id) {
        $this->request = new WebDLRequest();
        parent::__construct($unique_id);
    }
    
    public function push_column($c_id, $c_name) {
        $this->request->push_column($c_id);
        $this->header[$c_id] = $c_name;
    }

    public function push_match($c_id, $m_val, $type='AND') {
        $this->request->push_match($c_id, $m_val, $type); 
    }
        
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