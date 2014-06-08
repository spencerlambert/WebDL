<?php
/*******************
 * This class uses the FromRequest page block to build an HTML table using the
 * pushed columns of a DLM Request.
 */
class WebDLPBFromRequestTable extends WebDLPBFromRequest {
    
    protected $header = array();
    protected $css_class = '';
    
    public function __construct($unique_id) {
        parent::__construct($unique_id);
    }

    public function push_column($c_id, $c_name) {
        parent::push_column($c_id);
        $this->header[$c_id] = $c_name;
    }
    
    // Get both the AngularJS and the DIV of the table, and set the HTML var.
    protected function set_html() {
        $this->html = $this->get_angularjs();
        $this->html .= $this->get_div();
    }
    
    // Change the table type to things like table-striped, table-bordered, etc.  See Bootstrap documentation.
    public function set_bootstrap_table_type($css_class) {
        $this->css_class = ' '.$css_class;
    }
    
    // Get the DIV that contains the HTML table.
    protected function get_div() {
        $html = '
            <div class="table'.$this->css_class.'" ng-controller="'.$this->unique_id.'Ctrl">
                <table>
                    <thead>
                        <tr>';
                        foreach ($this->header as $c_id=>$header) {
                            $html .= '<th>'.$header.'</th>';
                        }
        $html .= '
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="row in data">';
                        foreach ($this->header as $c_id=>$header) {
                            $html .= '<td>{{row.'.$c_id.'}}</td>';
                        }
        $html .= '
                        </tr>
                    </tbody>
                </table>
            </div>
        ';
        
        return $html;
    }
    
    
    
}
?>