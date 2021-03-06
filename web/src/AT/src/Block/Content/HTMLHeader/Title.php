<?php
namespace AT\Block\Content\HTMLHeader;

class Title extends \AsyncWeb\Frontend\Block {
    public function overRideOuterBlock() {
        return true;
    }
    public function initTemplate() {
        $this->template = '<title id="T_HeaderTitle">{{title}}</title>';
    }
    public function init() {
        $data = array("title" => "Prehled českých firem | CZ-FIN");
        $this->setData($data);
    }
}
