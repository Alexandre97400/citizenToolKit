<?php

class DetailAction extends CAction
{
	/**
	* Dashboard Organization
	*/
    public function run($id) { 
    	$controller=$this->getController();
		$event = Event::getPublicData($id);

        
        $controller->title = (isset($event["name"])) ? $event["name"] : "";
        $controller->subTitle = (isset($event["description"])) ? $event["description"] : "";
        $controller->pageTitle = ucfirst($controller->module->id)." - ".Yii::t("event","Event's informations")." ".$controller->title;

        $contentKeyBase = $controller->id.".dashboard";
        $images = Document::getListDocumentsURLByContentKey((string)$event["_id"], $contentKeyBase, Document::DOC_TYPE_IMAGE);

        $organizer = array();

        $people = array();
        //$admins = array();
        $attending =array();
        $controller->toolbarMBZ = array();
        if(!empty($event)){
          $params = array();
          if(isset($event["links"])){
            foreach ($event["links"]["attendees"] as $uid => $e) {

              $citoyen = Person::getPublicData($uid);
              if(!empty($citoyen)){
                array_push($people, $citoyen);
                array_push($attending, $citoyen);

                if( $uid == Yii::app()->session['userId'] )
                    array_push($controller->toolbarMBZ, array('tooltip' => "Send a message to this Event","iconClass"=>"fa fa-envelope-o","href"=>"<a href='#' class='new-news' data-id='".$id."' data-type='".Event::COLLECTION."' data-name='".$event['name']."'") );
              }

              /*if(isset($e["isAdmin"]) && $e["isAdmin"]==true){
                array_push($admins, $e);
              }*/
            }
            if(isset($event["links"]["organizer"])){
              foreach ($event["links"]["organizer"] as $uid => $e) 
              {
	            $organizer["type"] = $e["type"];
	            if($organizer["type"] == Project::COLLECTION ){
	                $iconNav="fa-lightbulb-o";
	                $urlType="project";
	                $organizerInfo = Project::getById($uid);
	                $organizer["type"]=$urlType;
                }
                else{
	                $iconNav="fa-group";
	                $urlType="organization";	
	                $organizerInfo = Organization::getById($uid);  
					$organizer["type"]=$urlType;              
                }
                
                $organizer["id"] = $uid;

                $organizer["name"] = $organizerInfo["name"];
                array_push($controller->toolbarMBZ, array('tooltip' => "Back to ".$urlType,"iconClass"=>"fa ".$iconNav,"href"=>"<a href='".Yii::app()->createUrl("/".$controller->module->id."/".$urlType."/dashboard/id/".$uid)."'") );
              }
            }else if(isset($event["links"]["creator"]))
            {
                foreach ($event["links"]["creator"] as $uid => $e)
                {
                    $citoyen = Person::getById($uid);
                    $organizer["id"] = $uid;
                    $organizer["type"] = "person";
                    $organizer["name"] = $citoyen["name"];
                }
            }
          }
        }

        if(isset($event["_id"]) && isset(Yii::app()->session["userId"]) && Link::isLinked($event["_id"] , Event::COLLECTION , Yii::app()->session['userId']))
            array_push($controller->toolbarMBZ, array('tooltip' => "leave this Event", "parent"=>"span","parentId"=>"linkBtns","iconClass"=>"disconnectBtnIcon fa fa-unlink","href"=>"<a href='javascript:;' class='disconnectBtn text-red tooltips btn btn-default'  data-name='".$event["name"]."' data-id='".$event["_id"]."' data-type='".Event::COLLECTION."' data-member-id='".Yii::app()->session["userId"]."' data-ownerlink='".Link::person2events."' data-targetlink='".Link::event2person."'") );
		else
			array_push($controller->toolbarMBZ, array('tooltip' => "join this Event", "parent"=>"span","parentId"=>"linkBtns","iconClass"=>"connectBtnIcon fa fa-unlink","href"=>"<a href='javascript:;' class='connectBtn tooltips ' id='addKnowsRelation' data-placement='top' data-ownerlink='".Link::person2events."' data-targetlink='".Link::event2person."' ") );

        $params["images"] = $images;
        $params["contentKeyBase"] = $contentKeyBase;
        $params["attending"] = $attending;
        $params["event"] = $event;
        $params["organizer"] = $organizer;
        $params["people"] = $people;
        $params["countries"] = OpenData::getCountriesList();

        $list = Lists::get(array("eventTypes"));
        $params["eventTypes"] = $list["eventTypes"];
        
		$page = "detail";
		if(Yii::app()->request->isAjaxRequest)
            echo $controller->renderPartial($page,$params,true);
        else 
			$controller->render( $page , $params );
    }
}