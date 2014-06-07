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


    
}

?>