<?php
/********
 * WebPB is for WebDL Page Block
 * HTML content and forms are created using XML that defines a Page Block.
 */
class WebDLPBBase {
    protected $html;
    protected $unique_id;

    public function __construct($unique_id) {
        $this->unique_id = $unique_id;
    }

    public function get_html() {
        return $this->html;
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