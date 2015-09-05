<?php
class Link {
    
    const person2person = "knows";
    const person2organization = "memberOf";
    const organization2person = "members";
    const person2events = "events";
    const person2projects = "projects";
    const event2person = "attendees";
    const project2person = "contributors";

	/**
	 * Add a member to an organization
	 * Create a link between the 2 actors. The link will be typed members and memberOf
	 * The memberOf should be an organization
	 * The member can be an organization or a person
	 * 2 entry will be added :
	 * - $memberOf.links.members["$memberId"]
	 * - $member.links.memberOf["$memberOfId"]
	 * @param type $memberOfId The Id memberOf (organization) where a member will be linked. 
	 * @param type $memberOfType The Type (should be organization) memberOf where a member will be linked. 
	 * @param type $memberId The member Id to add. It will be the member added to the memberOf
	 * @param type $memberType MemberType to add : could be an organization or a person
	 * @param type $userId The userId doing the action
     * @param type $userAdmin Boolean to set if the member is admin or not
	 * @return result array with the result of the operation
	 */ 
    public static function addMember($memberOfId, $memberOfType, $memberId, $memberType, $userId, $userAdmin = false, $userRole = "") {
        
        //TODO SBAR => Change the boolean userAdmin to a role (admin, contributor, moderator...)

        //0. Check if the $memberOfId and the $memberId exists
        $memberOf = Link::checkIdAndType($memberOfId, $memberOfType);
		$member = Link::checkIdAndType($memberId, $memberType);

        $setArrayMembers = array("links.members.".$memberId.".type" => $memberType);
        $setArrayMemberOf = array("links.memberOf.".$memberOfId.".type" => $memberOfType);
        
        //1. Check if the $userId can manage the $memberOf
        if (!Authorisation::isOrganizationAdmin($userId, $memberOfId)) {
            // Add a toBeValidated tag on the link
            $setArrayMembers["links.members.".$memberId.".toBeValidated"] = true;
            $setArrayMemberOf["links.memberOf.".$memberOfId.".toBeValidated"] = true;
        }
 
        if ($userAdmin) {
            // Add an admin flag 
            $setArrayMembers["links.members.".$memberId.".isAdmin"] = $userAdmin;
            $setArrayMemberOf["links.memberOf.".$memberOfId.".isAdmin"] = $userAdmin;
        }
        if ($userRole != ""){
        	$setArrayMembers["links.members.".$memberId.".roles"] = $userRole;
        	$setArrayMemberOf["links.memberOf.".$memberOfId.".roles"] = $userRole;
        }

        //2. Create the links
        PHDB::update( $memberOfType, 
                   array("_id" => $memberOf["_id"]) , 
                   array('$set' => $setArrayMembers));
        
        PHDB::update( $memberType, 
                   array("_id" => $member["_id"]) , 
                   array('$set' => $setArrayMemberOf));

        //3. Send Notifications
	    //TODO - Send email to the member

        return array("result"=>true, "msg"=>"The member has been added with success", "memberOfId"=>$memberOfId, "memberid"=>$memberId);
    }

    /**
     * Remove a member of an organization
     * Delete a link between the 2 actors.
     * The memberOf should be an organization
     * The member can be an organization or a person
     * 2 entry will be deleted :
     * - $memberOf.links.members["$memberId"]
     * - $member.links.memberOf["$memberOfId"]
     * @param type $memberOfId The Id memberOf (organization) where a member will be deleted. 
     * @param type $memberOfType The Type (should be organization) memberOf where a member will be deleted. 
     * @param type $memberId The member Id to remove. It will be the member removed from the memberOf
     * @param type $memberType MemberType to remove : could be an organization or a person
     * @param type $userId $userId The userId doing the action
     * @return result array with the result of the operation
     */
    public static function removeMember($memberOfId, $memberOfType, $memberId, $memberType, $userId) {
        
        //0. Check if the $memberOfId and the $memberId exists
        $memberOf = Link::checkIdAndType($memberOfId, $memberOfType);
        $member = Link::checkIdAndType($memberId, $memberType);
        
        //1.1 the $userId can manage the $memberOf (admin)
        // Or the user can remove himself from a member list of an organization
        if (!Authorisation::isOrganizationAdmin($userId, $memberOfId)) {
            if ($memberId != $userId) {
                throw new CTKException("You are not admin of the Organization : ".$memberOfId);
            }
        }

        //2. Remove the links
        PHDB::update( $memberOfType, 
                   array("_id" => $memberOf["_id"]) , 
                   array('$unset' => array( "links.members.".$memberId => "") ));
 
        PHDB::update( $memberType, 
                       array("_id" => $member["_id"]) , 
                       array('$unset' => array( "links.memberOf.".$memberOfId => "") ));

        //3. Send Notifications
        //TODO - Send email to the member

        return array("result"=>true, "msg"=>"The member has been removed with success", "memberOfid"=>$memberOfId, "memberid"=>$memberId);
    }

    private static function checkIdAndType($id, $type) {
		
		if ($type == Organization::COLLECTION) {
        	$res = Organization::getById($id); 
        } else if ($type == Person::COLLECTION) {
        	$res = Person::getById($id);
        } else if ($type== PHType::TYPE_EVENTS){
        	$res = Event:: getById($id);
        } else if ($type== PHType::TYPE_PROJECTS){
        	$res = Project:: getById($id);
        } else {
        	throw new CTKException("Can not manage this type of MemberOf : ".$type);
        }
        if (empty($res)) throw new CTKException("The actor (".$id." / ".$type.") is unknown");

        return $res;
    }

    /**
     * Connect 2 actors : organization or Person
	 * Create a link between the 2 actors. The link will be typed as knows
	 * 1 entry will be added :
	 * - $origin.links.knows["$target"]
     * @param type $originId The Id of actor who wants to create a link with the $target
     * @param type $originType The Type (Organization or Person) of actor who wants to create a link with the $target
     * @param type $targetId The actor that will be linked
     * @param type $targetType The Type (Organization or Person) that will be linked
     * @param type $userId The userId doing the action
     * @return result array with the result of the operation
     */
    public static function connect($originId, $originType, $targetId, $targetType, $userId, $connectType,$isAdmin=false) {
	    $links=array("links.".$connectType.".".$targetId.".type" => $targetType);
        if($isAdmin)
        	$links["links.".$connectType.".".$targetId.".isAdmin"]=$isAdmin;
        
        //0. Check if the $originId and the $targetId exists
        $origin = Link::checkIdAndType($originId, $originType);
		$target = Link::checkIdAndType($targetId, $targetType);

        //2. Create the links
        PHDB::update($originType, 
                       array("_id" => $origin["_id"]) , 
                       array('$set' => $links));
        
        //3. Send Notifications
	    //TODO - Send email to the member
		
        return array("result"=>true, "msg"=>"The link knows has been added with success", "originId"=>$originId, "targetId"=>$targetId);
    }

    /**
     * Disconnect 2 actors : organization or Person
     * Delete a link knows between the 2 actors.
     * 1 entry will be deleted :
     * - $origin.links.knows["$target"]
     * @param type $originId The Id of actor where a link with the $target will be deleted
     * @param type $originType The Type (Organization or Person) of actor where a link with the $target will be deleted
     * @param type $targetId The actor that will be unlinked
     * @param type $targetType The Type (Organization or Person) that will be unlinked
     * @param type $userId The userId doing the action
     * @return result array with the result of the operation
     */
    public static function disconnect($originId, $originType, $targetId, $targetType, $userId, $connectType) {
        
        //0. Check if the $originId and the $targetId exists
        $origin = Link::checkIdAndType($originId, $originType);
        $target = Link::checkIdAndType($targetId, $targetType);

        //2. Create the links
        PHDB::update( $originType, 
                       array("_id" => $origin["_id"]) , 
                       array('$unset' => array("links.".$connectType.".".$targetId => "") ));

        //3. Send Notifications
        //TODO - Send email to the member

        return array("result"=>true, "msg"=>"The link knows has been removed with success", "originId"=>$originId, "targetId"=>$targetId);
    }

    /**
     * Check if two actors are connected with a links knows
     * @param type $originId The Id of actor to check the link with the $target
     * @param type $originType The Type (Organization or Person) of actor to check the link with the $target
     * @param type $targetId The actor to check that is linked
     * @param type $targetType The Type (Organization or Person) to check that is linked
     * @return boolean : true if the actors are connected, false else
     */
    public static function isConnected($originId, $originType, $targetId, $targetType) {
        $res = false;
        $where = array(
                    "_id"=>new MongoId($originId),
                    "links.knows.".$targetId =>  array('$exists' => 1));

        $originLinksKnows = PHDB::findOne($originType, $where);
        
        $res = isset($originLinksKnows);     

        return $res;
    }

    /** 
	 * 1 invitor invite a guest. The guest is not yet in the application
	 * Create a link between the invitor and the guest with the status toBeValidated
	 * The guest will receive a mail inviting him to create a ph account
	 * 1 entry will be added :
	 * - $invitor.links.knows["$guest"] = "status = toBeValidated"
	 * One Person or Organization will be created with basic information
	 * @param type $invitorId The actor Id who invite a guest
	 * @param type $invitorType The type (organization or person) who invite the guest
	 * @param type $guestId The actor Id that will invited
	 * @param type $guestType The type (organization or person) that will invited
	 * @param type $userId The userId doing the action
	 * @return result array with the result of the operation
	 */
    public static function invite($invitorId, $invitorType, $guestId, $guestType, $userId) {
 
        $result = array();
       
        return $result;
    }

    /**
	 * Add a organization to an event
	 * Create a link between the 2 actors. The link will be typed event and organizer
	 * @param type $organizerId The Id (organization) where an event will be linked. 
	 * @param type $eventId The Id (event) where an organization will be linked. 
	 * @param type $userId The user Id who try to link the organization to the event
	 * @return result array with the result of the operation
	 */
    public static function addOrganizer($organizationId, $eventId, $userId) {
		$res = array("result"=>false, "msg"=>"You can't add this event to this organization");
   		$isUserAdmin = Authorisation::isOrganizationAdmin($userId, $organizationId);
   		if($isUserAdmin){
   			PHDB::update(Organization::COLLECTION,
   						array("_id" => new MongoId($organizationId)),
   						array('$set' => array("links.events.".$eventId.".type" => PHType::TYPE_EVENTS))
   				);
   			PHDB::update(PHType::TYPE_EVENTS,
   						array("_id"=>new MongoId($eventId)),
   						array('$set'=> array("links.organizer.".$organizationId.".type"=>Organization::COLLECTION))
   				);
   			$res = array("result"=>true, "msg"=>"The event has been added with success");
   		};
   		return $res;
   }



    /**
	* Link a person to an event
	* Create a link between the 2 actors. The link will be typed event and organizer
	* @param type $eventId The Id (event) where a person will be linked. 
	* @param type $userId The user (person) Id who want to be link to the event
	* @param type $userAdmin (Boolean) to set if the member is admin or not
	* @return result array with the result of the operation
	*/
    public static function attendee($eventId, $userId, $isAdmin = false){

   		Link::addLink($userId, Person::COLLECTION, $eventId, PHType::TYPE_EVENTS, $userId, "events");
   		Link::addLink($eventId, PHType::TYPE_EVENTS, $userId, Person::COLLECTION, $userId, "attendees");

    	if($isAdmin){
    		PHDB::update(Person::COLLECTION, 
              		array("_id" => new MongoId($userId)), 
                    array('$set' => array("links.events.".$eventId.".isAdmin" => true))
            );

            PHDB::update( PHType::TYPE_EVENTS, 
              		array("_id" => new MongoId($eventId)), 
                    array('$set' => array("links.attendees.".$userId.".isAdmin" => true))
            );
    	}
    }


    /**
     * Connect 2 actors : Event, Person, Organization or Project
	 * Create a link between the 2 actors. The link will be typed as knows, attendee, event, project or contributor
	 * 1 entry will be added for example :
	 * - $origin.links.knows["$target"]
     * @param type $originId The Id of actor who wants to create a link with the $target
     * @param type $originType The Type (Organization, Person, Project or Event) of actor who wants to create a link with the $target
     * @param type $targetId The actor that will be linked
     * @param type $targetType The Type (Organization, Person, Project or Event) that will be linked
     * @param type $userId The userId doing the action (Optional)
     * @param type $connectType The link between the two actors
     * @return result array with the result of the operation
     */
    private static function addLink($originId, $originType, $targetId, $targetType, $userId= null, $connectType){

    	//0. Check if the $originId and the $targetId exists
        $origin = Link::checkIdAndType($originId, $originType);
        $target = Link::checkIdAndType($targetId, $targetType);

        //2. Create the links
        PHDB::update( $originType, 
                       array("_id" => $originId) , 
                       array('$unset' => array("links.".$connectType.".".$targetId => "") ));

        //3. Send Notifications
        //TODO - Send email to the member

        return array("result"=>true, "msg"=>"The link ".$connectType." has been added with success", "originId"=>$originId, "targetId"=>$targetId);
    }

    public static function isLinked($itemId, $itemType, $userId){
    	$res = false;
    	$item = PHDB::findOne( $itemType ,array("_id"=>new MongoId($itemId)));
    	if(isset($item["links"])){
    		foreach ($item["links"] as $key => $value) {
    			if(isset($value[$userId])){
    				$res= true;
    			}
    		}
    	}
    	return $res;
    }

    public static function removeEventLinks($eventId){
    	$events = Event::getById($eventId);
    	foreach ($events["links"] as $type => $item) {
			foreach ($item as $id => $itemInfo) {
				if($type == "organizer"){
					$res = PHDB::update( Organization::COLLECTION, 
                  			array("_id" => new MongoId($id)) , 
                  			array('$unset' => array( "links.events.".$eventId => "") ));
				}else{
					$res = PHDB::update( Person::COLLECTION, 
                  			array("_id" => new MongoId($id)) , 
                  			array('$unset' => array( "links.events.".$eventId => "") ));
				}
			}
    	}
    	return $res;
    }

    public static function removeRole($memberOfId, $memberOfType, $memberId, $memberType, $role, $userId) {
        
        //0. Check if the $memberOfId and the $memberId exists
        $memberOf = Link::checkIdAndType($memberOfId, $memberOfType);
        $member = Link::checkIdAndType($memberId, $memberType);
        
        //1.1 the $userId can manage the $memberOf (admin)
        // Or the user can remove himself from a member list of an organization
        if (!Authorisation::isOrganizationAdmin($userId, $memberOfId)) {
            if ($memberId != $userId) {
                throw new CTKException("You are not admin of the Organization : ".$memberOfId);
            }
        }

        //2. Remove the role
        PHDB::update( $memberOfType, 
                   array("_id" => $memberOf["_id"]) , 
                   array('$pull' => array( "links.members.".$memberId.".roles" => $role) ));
 
        //3. Remove the role
        PHDB::update($memberType,
        			array("_id"=> $member["_id"]),
        			array('$pull' => array("links.memberOf.".$memberOfId.".roles" => $role)) );
        
        return array("result"=>true, "msg"=>Yii::t("link","The member's role has been removed with success",null,Yii::app()->controller->module->id), "memberOfid"=>$memberOfId, "memberid"=>$memberId);
    }

    /**
     * Delete a link between the 2 actors.
     * @param $ownerId is the person who want to remowe a link
     * @param $targetId is the id of item we want to be unlink with
     * @param $ownerLink is the type of link between the owner and the target
     * @param $targetLink is the type of link between the target and the owner
     * @return result array with the result of the operation
     */
    public static function disconnectPerson($ownerId, $ownerType, $targetId, $targetType, $ownerLink, $targetLink = null) {
        
        //0. Check if the $owner and the $target exists
        $owner = Link::checkIdAndType($ownerId, $ownerType);
        $target = Link::checkIdAndType($targetId, $targetType);
       
        //1. Remove the links
        PHDB::update( $ownerType, 
                   array("_id" => new MongoId($ownerId)) , 
                   array('$unset' => array( "links.".$ownerLink.".".$targetId => "") ));
 
 		if(isset($targetLink) && $targetLink != null){
	        PHDB::update( $targetType, 
	                       array("_id" => new MongoId($targetId)) , 
	                       array('$unset' => array( "links.".$targetLink.".".$ownerId => "") ));
	    }

        //3. Send Notifications

        return array("result"=>true, "msg"=>"The link has been removed with success");
    }


     /**
     * Add a link between the 2 actors.
     * @param $ownerId is the person who want to add a link
     * @param $targetId is the id of item we want to be link with
     * @param $ownerLink is the type of link between the owner and the target
     * @param $targetLink is the type of link between the target and the owner
     * @return result array with the result of the operation
     */
    public static function connectPerson($ownerId, $ownerType, $targetId, $targetType, $ownerLink, $targetLink = null){
    	 //0. Check if the $owner and the $target exists
        $owner = Link::checkIdAndType($ownerId, $ownerType);
        $target = Link::checkIdAndType($targetId, $targetType);

        PHDB::update( $ownerType, 
           array("_id" => new MongoId($ownerId)) , 
           array('$set' => array( "links.".$ownerLink.".".$targetId.".type" => $targetType) ));

        //Mail::newConnection();

        if(isset($targetLink) && $targetLink != null){
         	$newObject = array('type' => $ownerType );
	        PHDB::update( $targetType, 
			               array("_id" => new MongoId($targetId)) , 
			               array('$set' => array( "links.".$targetLink.".".$ownerId => $newObject) ));
	    }

        return array("result"=>true, "msg"=>"The link has been added with success");
    }

} 
?>