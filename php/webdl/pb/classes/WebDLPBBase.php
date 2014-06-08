<?php
/********
 * WebPB is for WebDL Page Block
 * HTML content and forms are created using XML that defines a Page Block.
 */
abstract class WebDLPBBase {
    protected $html;
    protected $unique_id;

    public function __construct($unique_id) {
        $this->unique_id = $unique_id;
    }

    // Return the HTML of the page block
    public function get_html() {
        return $this->html;
    }
    
    // This is a function that all page blocks need to implement.
    // It is commonly called prior to get_html().
    abstract protected function set_html();

    // This function is called after all changes have been applied and the HTML needs to be generated
    abstract public function finish();
    
}

?>