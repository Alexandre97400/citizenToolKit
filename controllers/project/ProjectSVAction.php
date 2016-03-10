<?php
class ProjectSVAction extends CAction
{
    public function run(){
    	$controller=$this->getController();
    	$params = array();
    	
    	$params["countries"] = OpenData::getCountriesList();
    	$params["tags"] = json_encode(Tags::getActiveTags());
        if(Yii::app()->request->isAjaxRequest)
			echo $controller->renderPartial("projectSV", $params, true);
    }
}