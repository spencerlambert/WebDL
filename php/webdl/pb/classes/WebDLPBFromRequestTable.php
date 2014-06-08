<?php
/*******************
 * This class uses the FromRequest page block to build an HTML table using the
 * pushed columns of a DLM Request.
 */
class WebDLPBFromRequestTable extends WebDLPFromRequest {
    
    protected $header = array();
    
    public function __construct($unique_id) {
        parent::__construct($unique_id);
    }

    public function push_column($c_id, $c_name) {
        parent::push_column($c_id);
        $this->header[$c_id] = $c_name;
    }
    
    // Get both the AngularJS and the DIV of the table, and set the HTML var.
    private function set_html() {
        $this->html = $this->get_angularjs();
        $this->html .= $this->get_div();
    }
    
    // Get the DIV that contains the HTML table.
    private function get_div() {
        $html = '
            <div ng-controller="'.$this->unique_id.'Ctrl">
                <table>
                    <tr>';
                    foreach ($this->header as $c_id=>$header) {
                        $html .= '<th>'.$header.'</th>';
                    }
        $html .= '
                    </tr>
                    <tr ng-repeat="row in data">';
                    foreach ($this->header as $c_id=>$header) {
                        $html .= '<td>{{row.'.$c_id.'}}</td>';
                    }
        $html .= '
                    </tr>
                </table>
            </div>
        ';
        
        return $html;
    }
    
    
    
}
?>